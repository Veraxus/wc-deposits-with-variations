<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Deposits_Scheduled_Order_Manager class
 *
 * Handles scheduled orders, e.g. emailing users when they are due for payment.
 */
class WC_Deposits_Scheduled_Order_Manager {

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
		add_action( 'woocommerce_invoice_scheduled_orders', array( __CLASS__, 'invoice_scheduled_orders' ) );
		add_action( 'wp_trash_post', array( __CLASS__, 'trash_post' ) );
		add_action( 'untrash_post', array( __CLASS__, 'untrash_post' ) );
		add_action( 'before_delete_post', array( __CLASS__, 'before_delete_post' ) );
	}

	/**
	 * Schedule all orders for a payment plan.
	 * This is important because the tax point is when the order is placed.
	 * @param  WC_Deposits_Plan $payment_plan
	 * @param  int $original_order_id
	 */
	public static function schedule_orders_for_plan( $payment_plan, $original_order_id, $item ) {
		$schedule          = $payment_plan->get_schedule();
		$current_timestamp = current_time( 'timestamp' );
		$payment_number    = 2;
		$line_price        = self::_get_normalized_price_before_plan( $payment_plan, $item );

		// Skip first payment - that was taken already
		array_shift( $schedule );

		foreach ( $schedule as $schedule_row ) {
			// Work out relative timestamp
			$current_timestamp = strtotime( "+{$schedule_row->interval_amount} {$schedule_row->interval_unit}", $current_timestamp );

			// Work out how much the payment will be for
			$item['amount'] = ( $line_price / 100 ) * $schedule_row->amount;

			// Create order
			WC_Deposits_Order_Manager::create_order( $current_timestamp, $original_order_id, $payment_number, $item, 'scheduled-payment' );
			$payment_number ++;
		}
	}

	/**
	 * Get normalized price before plan.
	 *
	 * The price_excluding_tax in order item is calculated with total percents
	 * from payment plan. This method normalize the price again.
	 *
	 * @param WC_Deposits_Plan $plan Plan
	 * @param array            $item Order item
	 *
	 * @return float Line price
	 */
	private static function _get_normalized_price_before_plan( $plan, $item ) {
		$total_percent    = $plan->get_total_percent();
		$price_after_plan = ! empty( $item['price_excluding_tax'] ) ? $item['price_excluding_tax'] : $item['product']->get_price_excluding_tax( $item['qty'] );

		$line_price = ( $price_after_plan * 100 ) / $total_percent;

		return $line_price;
	}

	/**
	 * Send an invoice for a scheduled order when the post date passes the current date
	 */
	public static function invoice_scheduled_orders() {
		global $wpdb;

		$mailer     = WC_Emails::instance();
		$date       = date( "Y-m-d H:i:s", current_time( 'timestamp' ) );
		$due_orders = $wpdb->get_col( $wpdb->prepare( "
			SELECT 	posts.ID
			FROM 	{$wpdb->posts} AS posts
			WHERE 	posts.post_type = 'shop_order'
			AND 	posts.post_status = 'wc-scheduled-payment'
			AND 	posts.post_date < %s
		", $date ) );

		if ( $due_orders ) {
			foreach ( $due_orders as $due_order ) {
				$order = wc_get_order( $due_order );
				$order->update_status( 'pending', __( 'Scheduled order ready for payment.', 'woocommerce-deposits' ) );
				$mailer->customer_invoice( $order );
			}
		}
	}

	/**
	 * Get related orders created by deposits for an order ID
	 * @param  int $order_id
	 * @return array
	 */
	public static function get_related_orders( $order_id ) {
		global $wpdb;

		$order_ids    = array();
		$found_orders = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d AND post_status NOT IN ( 'draft', 'autodraft', 'trash' );", $order_id ) );
		foreach ( $found_orders as $found_order ) {
			if ( 'wc_deposits' === get_post_meta( $found_order, '_created_via', true ) ) {
				$order_ids[] = $found_order;
			}
		}
		return $order_ids;
	}

	/**
	 * When a post is trashed, if its an order, sync scheduled payments
	 * @param  int $id
	 */
	public static function trash_post( $id ) {
		if ( in_array( get_post_type( $id ), wc_get_order_types() ) ) {
			foreach ( self::get_related_orders( $id ) as $order_id ) {
				wp_trash_post( $order_id );
			}
		}
	}

	/**
	 * When a post is trashed, if its an order, sync scheduled payments
	 * @param  int $id
	 */
	public static function untrash_post( $id ) {
		if ( in_array( get_post_type( $id ), wc_get_order_types() ) ) {
			foreach ( self::get_related_orders( $id ) as $order_id ) {
				wp_untrash_post( $order_id );
			}
		}
	}

	/**
	 * When a post is deleted, if its an order, sync scheduled payments
	 * @param  int $id
	 */
	public static function before_delete_post( $id ) {
		if ( in_array( get_post_type( $id ), wc_get_order_types() ) ) {
			foreach ( self::get_related_orders( $id ) as $order_id ) {
				wp_delete_post( $order_id, true );
			}
		}
	}
}
WC_Deposits_Scheduled_Order_Manager::get_instance();
