<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Deposits_Plans_Product_Admin class
 */
class WC_Deposits_Plans_Product_Admin {

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
		add_action( 'admin_enqueue_scripts', array( $this, 'styles_and_scripts' ) );
		add_action( 'woocommerce_process_product_meta', array( $this,'save_product_data' ), 20 );
		add_action( 'woocommerce_product_write_panel_tabs', array( $this, 'add_tab' ), 5 );
		add_action( 'woocommerce_product_write_panels', array( $this, 'deposit_panel' ) );

		add_action( 'woocommerce_variation_options', array( $this, 'variations_options' ), 10, 3 );
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'variation_deposit_panel'), 10, 3 );

		add_action( 'woocommerce_save_product_variation', array( $this, 'save_product_variation_data' ), 20, 2 );
		add_action( 'woocommerce_save_product_variation-subscription', array( $this, 'save_product_variation_data' ), 20, 2 );
	}

	/**
	 * Scripts
	 */
	public function styles_and_scripts() {
		wp_register_script( 'woocommerce-deposits-product-admin', WC_DEPOSITS_PLUGIN_URL . '/assets/js/product-admin.js', array( 'jquery' ), WC_DEPOSITS_VERSION, true );
	}

	/**
	 * Show the deposits tab
	 */
	public function add_tab() {
		include( 'views/html-deposits-tab.php' );
	}

	/**
	 * Show the deposits panel
	 */
	public function deposit_panel() {
		wp_enqueue_script( 'woocommerce-deposits-product-admin' );
		include( 'views/html-deposit-data.php' );
	}
	
	/**
	 * Add Desposits options to Variations
	 *
	 * @param $loop
	 * @param $variation_data
	 * @param $variation
	 */
	public static function variation_deposit_panel( $loop, $variation_data, $variation ){
		wp_enqueue_script( 'woocommerce-deposits-product-admin' );
		include( 'views/html-deposit-data.php' );
	}

	/**
	 * Add Deposits option to variations
	 *
	 * @param $loop
	 * @param $variation_data
	 * @param $variation
	 */
	public static function variations_options( $loop, $variation_data, $variation ){
		$allow_deposit = get_post_meta( $variation->ID, '_allow_deposit', true );
		include( 'views/html-variation-toggle-deposit.php' );
	}
	
	/**
	 * Save data
	 * @param  int $post_id
	 */
	public function save_product_data( $post_id ) {
		$meta_to_save = array(
			'_wc_deposit_enabled'                          => '',
			'_wc_deposit_type'                             => '',
			'_wc_deposit_amount'                           => 'float',
			'_wc_deposit_payment_plans'                    => 'int',
			'_wc_deposit_multiple_cost_by_booking_persons' => 'issetyesno'
		);
		foreach ( $meta_to_save as $meta_key => $sanitize ) {
			$value = ! empty( $_POST[ $meta_key ] ) ? $_POST[ $meta_key ] : '';
			switch ( $sanitize ) {
				case 'int' :
					$value = $value ? ( is_array( $value ) ? array_map( 'absint', $value ) : absint( $value ) ) : '';
					break;
				case 'float' :
					$value = $value ? ( is_array( $value ) ? array_map( 'floatval', $value ) : floatval( $value ) ) : '';
					break;
				case 'yesno' :
					$value = $value == 'yes' ? 'yes' : 'no';
					break;
				case 'issetyesno' :
					$value = $value ? 'yes' : 'no';
					break;
				default :
					$value = is_array( $value ) ? array_map( 'sanitize_text_field', $value ) : sanitize_text_field( $value );
			}
			update_post_meta( $post_id, $meta_key, $value );
		}
	}
	
	/**
	 * Save data for variations
	 * @param  int $post_id
	 */
	public function save_product_variation_data( $variation_id, $loop  ) {
		
		$meta_to_save = array(
			'_wc_deposit_enabled'                          => '',
			'_wc_deposit_type'                             => '',
			'_wc_deposit_amount'                           => 'float',
			'_wc_deposit_payment_plans'                    => 'int',
			'_wc_deposit_multiple_cost_by_booking_persons' => 'issetyesno'
		);
		foreach ( $meta_to_save as $meta_key => $sanitize ) {
			
			$value = ! empty( $_POST[ $meta_key ][ $loop ] ) ? $_POST[ $meta_key ][ $loop ] : '';
			
			switch ( $sanitize ) {
				case 'int' :
					$value = $value ? ( is_array( $value ) ? array_map( 'absint', $value ) : absint( $value ) ) : '';
					break;
				case 'float' :
					$value = $value ? ( is_array( $value ) ? array_map( 'floatval', $value ) : floatval( $value ) ) : '';
					break;
				case 'yesno' :
					$value = $value == 'yes' ? 'yes' : 'no';
					break;
				case 'issetyesno' :
					$value = $value ? 'yes' : 'no';
					break;
				default :
					$value = is_array( $value ) ? array_map( 'sanitize_text_field', $value ) : sanitize_text_field( $value );
			}
			update_post_meta( $variation_id, $meta_key, $value );
			
			// Tell parent that deposits are enabled
			if ( $meta_key === '_wc_deposit_enabled' && $value === 'yes' && isset( $_POST['product_id'] ) ) {
				update_post_meta( $_POST['product_id'], $meta_key, $value );
			}
		}
	}
}
WC_Deposits_Plans_Product_Admin::get_instance();
