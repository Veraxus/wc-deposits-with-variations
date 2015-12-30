<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Deposits_Cart_Manager class
 */
class WC_Deposits_Cart_Manager {

	/** @var object Class Instance */
	private static $instance;

	/**
	 * Get the class instance
	 */
	public static function get_instance() {
		return null === self::$instance ? ( self::$instance = new self ) : self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'deposits_form_output' ), 99 );
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add_cart_item' ), 10, 4 );
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 3 );
		add_filter( 'woocommerce_add_cart_item', array( $this, 'add_cart_item' ), 99, 1 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 99, 3 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'get_item_data' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_price', array( $this, 'display_item_price' ), 10, 3 );
		add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'display_item_subtotal' ), 10, 3 );
		add_action( 'woocommerce_cart_totals_before_order_total', array( $this, 'display_cart_totals_before' ), 99 );
		add_action( 'woocommerce_review_order_before_order_total', array( $this, 'display_cart_totals_before' ), 99 );
		add_action( 'woocommerce_cart_totals_after_order_total', array( $this, 'display_cart_totals_after' ), 1 );
		add_action( 'woocommerce_review_order_after_order_total', array( $this, 'display_cart_totals_after' ), 1 );
		add_action( 'woocommerce_add_order_item_meta', array( $this, 'add_order_item_meta' ), 50, 2 );
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disable_gateways' ) );

		// Change button/cart URLs
		add_filter( 'add_to_cart_text', array( $this, 'add_to_cart_text'), 15 );
		add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'add_to_cart_text'), 15 );
		add_filter( 'woocommerce_add_to_cart_url', array( $this, 'add_to_cart_url' ), 10, 1 );
		add_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'add_to_cart_url' ), 10, 1 );
	}

	/**
	 * Scripts and styles
	 */
	public function wp_enqueue_scripts() {
		wp_enqueue_style( 'wc-deposits-frontend', WC_DEPOSITS_PLUGIN_URL . '/assets/css/frontend.css', null, WC_DEPOSITS_VERSION );
		wp_register_script( 'wc-deposits-frontend', WC_DEPOSITS_PLUGIN_URL . '/assets/js/frontend.min.js', array( 'jquery' ), WC_DEPOSITS_VERSION, true );
	}

	/**
	 * Show deposits form
	 */
	public function deposits_form_output() {
		if ( WC_Deposits_Product_Manager::deposits_enabled( $GLOBALS['post']->ID ) ) {
			wp_enqueue_script( 'wc-deposits-frontend' );
			wc_get_template( 'deposit-form.php', array( 'post' => $GLOBALS['post'] ), 'woocommerce-deposits', WC_DEPOSITS_TEMPLATE_PATH );
		}
	}

	/**
	 * Does the cart contain a deposit
	 * @return boolean
	 */
	public function has_deposit() {
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( ! empty( $cart_item['is_deposit'] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * See how much credit the user is giving the customer (for payment plans)
	 * @return float
	 */
	public function get_future_payments_amount() {
		return $this->get_deposit_remaining_amount() + $this->get_credit_amount();
	}

	/**
	 * See whats left to pay after deposits
	 * @return float
	 */
	public function get_deposit_remaining_amount() {
		$credit_amount = 0;

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( ! empty( $cart_item['is_deposit'] ) && empty( $cart_item['payment_plan'] ) ) {
				$_product = $cart_item['data'];
				$quantity = $cart_item['quantity'];

				if ( 'excl' === WC()->cart->tax_display_cart ) {
					$credit_amount += $_product->get_price_excluding_tax( $quantity, $cart_item['full_amount'] - $cart_item['deposit_amount'] );
				} else {
					$credit_amount += $_product->get_price_including_tax( $quantity, $cart_item['full_amount'] - $cart_item['deposit_amount'] );
				}
			}
		}

		return $credit_amount;
	}

	/**
	 * See how much credit the user is giving the customer (for payment plans)
	 * @return float
	 */
	public function get_credit_amount() {
		$credit_amount = 0;

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( ! empty( $cart_item['is_deposit'] ) && ! empty( $cart_item['payment_plan'] ) ) {
				$_product = $cart_item['data'];
				$quantity = $cart_item['quantity'];

				if ( 'excl' === WC()->cart->tax_display_cart ) {
					$credit_amount += $_product->get_price_excluding_tax( $quantity, $cart_item['full_amount'] - $cart_item['deposit_amount'] );
				} else {
					$credit_amount += $_product->get_price_including_tax( $quantity, $cart_item['full_amount'] - $cart_item['deposit_amount'] );
				}
			}
		}

		return $credit_amount;
	}

	/**
	 * When a booking is added to the cart, validate it
	 *
	 * @param mixed $passed
	 * @param mixed $product_id
	 * @param mixed $qty
	 * @return bool
	 */
	public function validate_add_cart_item( $passed, $product_id, $qty, $variation_id ) {
		if ( ! WC_Deposits_Product_Manager::deposits_enabled( $product_id ) ) {
			return $passed;
		}

		$wc_deposit_option       = isset( $_POST['wc_deposit_option'] ) ? sanitize_text_field( $_POST['wc_deposit_option'] ) : false;
		$wc_deposit_payment_plan = isset( $_POST['wc_deposit_payment_plan'] ) ? sanitize_text_field( $_POST['wc_deposit_payment_plan'] ) : false;

		// Validate chosen plan
		if ( ( 'yes' === $wc_deposit_option || WC_Deposits_Product_Manager::deposits_forced( $product_id ) ) && 'plan' === WC_Deposits_Product_Manager::get_deposit_type( $product_id ) ) {
			
			$plans = WC_Deposits_Plans_Manager::get_plan_ids_for_product( $product_id );
			
			if ( $variation_id ) {
				$plans = array_merge( $plans, WC_Deposits_Plans_Manager::get_plan_ids_for_product( $variation_id ) );
			}
			
			if ( ! in_array( $wc_deposit_payment_plan, $plans ) ) {
				wc_add_notice( __( 'Please select a valid payment plan', 'woocommerce-deposits' ), 'error' );
				return false;
			}
		}
		return $passed;
	}

	/**
	 * Add posted data to the cart item
	 * @param mixed $cart_item_meta
	 * @param mixed $product_id
	 * @return array
	 */
	public function add_cart_item_data( $cart_item_meta, $product_id, $variation_id ) {
		
		$item_id = ( $variation_id ) ? $variation_id : $product_id;
		
		if ( ! WC_Deposits_Product_Manager::deposits_enabled( $item_id ) ) {
			return $cart_item_meta;
		}

		$wc_deposit_option       = isset( $_POST['wc_deposit_option'] ) ? sanitize_text_field( $_POST['wc_deposit_option'] ) : false;
		$wc_deposit_payment_plan = isset( $_POST['wc_deposit_payment_plan'] ) ? sanitize_text_field( $_POST['wc_deposit_payment_plan'] ) : false;

		if ( 'yes' === $wc_deposit_option || WC_Deposits_Product_Manager::deposits_forced( $item_id ) ) {
			$cart_item_meta['is_deposit'] = true;

			if ( 'plan' === WC_Deposits_Product_Manager::get_deposit_type( $item_id ) ) {
				$cart_item_meta['payment_plan'] = $wc_deposit_payment_plan;
			} else {
				$cart_item_meta['payment_plan'] = 0;
			}
		}

		return $cart_item_meta;
	}

	/**
	 * Get data from the session and add to the cart item's meta
	 * @param mixed $cart_item
	 * @param mixed $values
	 * @return array cart item
	 */
	public function get_cart_item_from_session( $cart_item, $values, $cart_item_key ) {
		$cart_item['is_deposit'] = ! empty( $values['is_deposit'] );
		$cart_item['payment_plan'] = ! empty( $values['payment_plan'] ) ? absint( $values['payment_plan'] ) : 0;
		return $this->add_cart_item( $cart_item );
	}

	/**
	 * Adjust the price of the product based on deposits
	 *
	 * @param mixed $cart_item
	 * @return array cart item
	 */
	public function add_cart_item( $cart_item ) {
		if ( ! empty( $cart_item['is_deposit'] ) ) {
			$deposit_amount = WC_Deposits_Product_Manager::get_deposit_amount( $cart_item['data'], ! empty( $cart_item['payment_plan'] ) ? $cart_item['payment_plan'] : 0 );

			if ( false !== $deposit_amount ) {
				$cart_item['deposit_amount'] = $deposit_amount;

				// Bookings support
				if ( isset( $cart_item['booking']['_persons'] ) && 'yes' === get_post_meta( $cart_item['data']->id, '_wc_deposit_multiple_cost_by_booking_persons', true ) ) {
					$cart_item['deposit_amount'] = $cart_item['deposit_amount'] * absint( is_array( $cart_item['booking']['_persons'] ) ? array_sum( $cart_item['booking']['_persons'] ) : $cart_item['booking']['_persons'] );
				}

				// Work out %
				if ( ! empty( $cart_item['payment_plan'] ) ) {
					$plan          = WC_Deposits_Plans_Manager::get_plan( $cart_item['payment_plan'] );
					$total_percent = $plan->get_total_percent();
					$cart_item['full_amount'] = ( 'percentage' === $plan->get_type() ) 
						? ( ( $cart_item['data']->get_price() / 100 ) * $total_percent ) 
						: $cart_item['data']->get_price();
				} else {
					$cart_item['full_amount'] = $cart_item['data']->get_price();
				}

				$cart_item['data']->set_price( $cart_item['deposit_amount'] );
			}
		}
		return $cart_item;
	}

	/**
	 * Put meta data into format which can be displayed
	 *
	 * @param mixed $other_data
	 * @param mixed $cart_item
	 * @return array meta
	 */
	public function get_item_data( $other_data, $cart_item ) {
		if ( ! empty( $cart_item['payment_plan'] ) ) {
			$plan         = WC_Deposits_Plans_Manager::get_plan( $cart_item['payment_plan'] );
			$other_data[] = array(
				'name'    => __( 'Payment Plan', 'woocommerce-deposits' ),
				'value'   => $plan->get_name(),
				'display' => ''
			);
		}
		return $other_data;
	}

	/**
	 * Show the correct item price
	 */
	public function display_item_price( $output, $cart_item, $cart_item_key ) {
		if ( ! empty( $cart_item['is_deposit'] ) ) {
			$_product = $cart_item['data'];
			if ( 'excl' === WC()->cart->tax_display_cart ) {
				$amount = $_product->get_price_excluding_tax( 1, $cart_item['full_amount'] );
			} else {
				$amount = $_product->get_price_including_tax( 1, $cart_item['full_amount'] );
			}
			$output = wc_price( $amount );
		}
		return $output;
	}

	/**
	 * Adjust the subtotal display in the cart
	 */
	public function display_item_subtotal( $output, $cart_item, $cart_item_key ) {
		if ( ! empty( $cart_item['is_deposit'] ) ) {
			$_product = $cart_item['data'];
			$quantity = $cart_item['quantity'];

			if ( 'excl' === WC()->cart->tax_display_cart ) {
				$full_amount    = $_product->get_price_excluding_tax( $quantity, $cart_item['full_amount'] );
				$deposit_amount = $_product->get_price_excluding_tax( $quantity, $cart_item['deposit_amount'] );
			} else {
				$full_amount    = $_product->get_price_including_tax( $quantity, $cart_item['full_amount'] );
				$deposit_amount = $_product->get_price_including_tax( $quantity, $cart_item['deposit_amount'] );
			}

			if ( ! empty( $cart_item['payment_plan'] ) ) {
				$plan = new WC_Deposits_Plan( $cart_item['payment_plan'] );
				$output .= '<br/><small>' . $plan->get_formatted_schedule( $full_amount ) . '</small>';
			} else {
				$output .= '<br/><small>' . sprintf( __( '%s payable in total', 'woocommerce-deposits' ), wc_price( $full_amount ) ) . '</small>';
			}
		}
		return $output;
	}

	/**
	 * Before the main total
	 */
	public function display_cart_totals_before() {
		if ( self::get_future_payments_amount() > 0 ) {
			ob_start();
		}
	}

	/**
	 * After the main total
	 */
	public function display_cart_totals_after() {
		$future_payment_amount = self::get_future_payments_amount();

		if ( 0 >= $future_payment_amount ) {
			return;
		}

		ob_end_clean(); ?>
		<tr class="order-total">
			<th><?php _e( 'Due Today', 'woocommerce-deposits' ); ?></th>
			<td><?php wc_cart_totals_order_total_html(); ?></td>
		</tr>
		<tr class="order-total">
			<th><?php _e( 'Future&nbsp;Payments&nbsp;', 'woocommerce-deposits' ); ?></th>
			<td><strong><?php echo wc_price( $future_payment_amount ); ?></strong></td>
		</tr><?php
	}

	/**
	 * Store cart info inside new orders
	 *
	 * @param mixed $item_id
	 * @param mixed $cart_item
	 */
	public function add_order_item_meta( $item_id, $cart_item ) {
		if ( ! empty( $cart_item['is_deposit'] ) ) {
			woocommerce_add_order_item_meta( $item_id, '_is_deposit', 'yes' );
			woocommerce_add_order_item_meta( $item_id, '_deposit_full_amount', $cart_item['data']->get_price_including_tax( $cart_item['quantity'], $cart_item['full_amount'] ) );
			woocommerce_add_order_item_meta( $item_id, '_deposit_full_amount_ex_tax', $cart_item['data']->get_price_excluding_tax( $cart_item['quantity'], $cart_item['full_amount'] ) );

			if ( ! empty( $cart_item['payment_plan'] ) ) {
				woocommerce_add_order_item_meta( $item_id, '_payment_plan', $cart_item['payment_plan'] );
			}
		}
	}

	/**
	 * Disable gateways when using deposits
	 * @param  array  $gateways
	 * @return array
	 */
	public function disable_gateways( $gateways = array() ) {
		if ( is_admin() ) {
			return $gateways;
		}
		$disabled = get_option( 'wc_deposits_disabled_gateways', array() );
		if ( $this->has_deposit() && ! empty( $disabled ) && is_array( $disabled ) ) {
			return array_diff_key( $gateways, array_combine( $disabled, $disabled ) );
		}
		return $gateways;
	}

	/**
	 * Add to cart text
	 */
	public function add_to_cart_text( $text ) {
		global $product;

		if ( is_single( $product->id ) ) {
			return $text;
		}

		if ( ! WC_Deposits_Product_Manager::deposits_enabled( $product->id ) ) {
			return $text;
		}

		$deposit_type = WC_Deposits_Product_Manager::get_deposit_type( $product->id );
		if ( WC_Deposits_Product_Manager::deposits_forced( $product->id ) ) {
			if ( 'plan' !== $deposit_type ) {
				return $text;
			}
		}

		$text = apply_filters( 'woocommerce_deposits_add_to_cart_text', __( 'Select options', 'woocommerce-deposits' ) );
		return $text;
	}

	/**
	 * Add to cart URL
	 */
	public function add_to_cart_url( $url ) {
		global $product;

		if ( is_single( $product->id ) ) {
			return $url;
		}

		if ( ! WC_Deposits_Product_Manager::deposits_enabled( $product->id ) ) {
			return $url;
		}

		$deposit_type = WC_Deposits_Product_Manager::get_deposit_type( $product->id );
		if ( WC_Deposits_Product_Manager::deposits_forced( $product->id ) ) {
			if ( 'plan' !== $deposit_type ) {
				return $url;
			}
		}

		$product->product_type = 'deposit';
		$url = apply_filters( 'woocoommerce_deposits_add_to_cart_url', get_permalink( $product->id ) );
		return $url;
	}

}

WC_Deposits_Cart_Manager::get_instance();
