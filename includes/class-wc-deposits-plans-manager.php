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
		if ( ! is_array( $default_payment_plans ) ) {
			$default_payment_plans = array();
		}
		return $default_payment_plans;
	}

	/**
	 * Get plan ids assigned to a product
	 * @param  int $product_id
	 * @param bool $inc_variations Merge variation plans with parent product?
	 * @return array of ids
	 */
	public static function get_plan_ids_for_product( $product_id, $inc_variations = false ) {
		$meta = get_post_meta( $product_id, '_wc_deposit_payment_plans', true );

		if ( ! is_array( $meta ) ) {
			$meta = array();
		}
		
		$map = array_map( 'absint', array_filter( $meta ) );

		// If using variations, merge child variations into array
		if ( $inc_variations ) {
			$children = get_children( array(
				'post_parent' => $product_id,
				'post_type' => 'product_variation',
			) );

			foreach ( $children as $child ) {
				$child_plans = self::get_plan_ids_for_product( $child->ID );
				$map = array_merge( $child_plans, $map );
			}
		}
		
		if ( count( $map ) <= 0 ) {
			$map = self::get_default_plan_ids();
		}
		return $map;
	}

	/**
	 * Get payment plans for a product
	 * @param  int $product_id
	 * @param bool $inc_variations Merge variation plans with parent product?
	 * @return array of WC_Deposits_Plan
	 */
	public static function get_plans_for_product( $product_id, $inc_variations = false ) {
		global $wpdb;

		$plans    = array();
		$product  = get_post( $product_id );
		$plan_ids = array_merge( array( 0 ), self::get_plan_ids_for_product( $product_id ) );

		$db_plans = $wpdb->get_results( "SELECT * FROM {$wpdb->wc_deposits_payment_plans} WHERE ID IN (" . implode( ',', $plan_ids ) . ")" );
		
		foreach ( $db_plans as $result ) {
			$plan = new WC_Deposits_Plan( $result );
			
			// additional details to distinguish variations
			$plan->item_ids = array( $product_id );
			$plan->item_type = $product->post_type;
			
			$plans[ $plan->get_id() ] = $plan;
		}
		
		// If using variations, merge child variations into array so there's no duplicate plans
		if ( $inc_variations ) {
			$children = get_children( array( 
				'post_parent' => $product_id, 
				'post_type' => 'product_variation',
			) );
			
			foreach ( $children as $child ) {
				$child_plans = self::get_plans_for_product( $child->ID );
				foreach ( $child_plans as $child_plan ) {
					if ( array_key_exists( $child_plan->get_id(), $plans ) ) {
						$plans[ $child_plan->get_id() ]->item_ids[] = $child_plan->item_ids[0];
					}
					else {
						$plans[ $child_plan->get_id() ] = $child_plan;
					}
				}
			}
		}
		
		return $plans;
	}

	/**
	 * @param $plan
	 * @param bool $echo
	 *
	 * @return string
	 */
	public static function output_plan_classes( $plan, $echo = true ) {
		$type = ( isset( $plan->item_type ) ) ? $plan->item_type : 'product';
		$items = ( isset( $plan->item_ids ) ) ? $plan->item_ids : array();
		$class = $type;
		foreach ( $items as $item ) {
			$class .= ' item-' . $item;
		}
		if ( $echo ) {
			echo $class;
		}
		return $class;
	}
}

