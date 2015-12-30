<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Deposits_Plans_Admin class
 */
class WC_Deposits_Plans_Manager {

	/**
	 * Get a payment plan by ID
	 * @param  int $plan_id
	 * @return WC_Deposits_Plan
	 */
	public static function get_plan( $plan_id ) {
		global $wpdb;
		return new WC_Deposits_Plan( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->wc_deposits_payment_plans} WHERE ID = %d", absint( $plan_id ) ) ) );
	}

	/**
	 * Get All plans
	 * @return array of WC_Deposits_Plan
	 */
	public static function get_plans() {
		global $wpdb;

		$plans = array();

		foreach ( $wpdb->get_results( "SELECT * FROM {$wpdb->wc_deposits_payment_plans}" ) as $result ) {
			$plans[] = new WC_Deposits_Plan( $result );
		}

		return $plans;
	}

	/**
	 * Get all plan ids and names
	 * @return array of ID name value pairs
	 */
	public static function get_plan_ids() {
		$plans    = self::get_plans();
		$plan_ids = array();

		foreach ( $plans as $plan ) {
			$plan_ids[ $plan->get_id() ] = $plan->get_name();
		}

		return $plan_ids;
	}

	/**
	 * Get the default plan IDs
	 */
	public static function get_default_plan_ids() {
		$default_payment_plans = get_option( 'wc_deposits_default_plans', array() );
		return $default_payment_plans;
	}

	/**
	 * Get plan ids assigned to a product
	 * @param  int $product_id
	 * @return array of ids
	 */
	public static function get_plan_ids_for_product( $product_id ) {
		$map = array_map( 'absint', array_filter( (array) get_post_meta( $product_id, '_wc_deposit_payment_plans', true ) ) );
		if ( count( $map ) <= 0 ) {
			$map = self::get_default_plan_ids();
		}
		return $map;
	}

	/**
	 * Get payment plans for a product
	 * @param  int $product_id
	 * @return array of WC_Deposits_Plan
	 */
	public static function get_plans_for_product( $product_id ) {
		global $wpdb;

		$plans    = array();
		$plan_ids = array_merge( array( 0 ), self::get_plan_ids_for_product( $product_id ) );

		foreach ( $wpdb->get_results( "SELECT * FROM {$wpdb->wc_deposits_payment_plans} WHERE ID IN (" . implode( ',', $plan_ids ) . ")" ) as $result ) {
			$plans[] = new WC_Deposits_Plan( $result );
		}
		return $plans;
	}
}

