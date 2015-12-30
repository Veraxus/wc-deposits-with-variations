<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Deposits_Product_Manager class
 */
class WC_Deposits_Product_Manager {

	/**
	 * Are deposits enabled for a specific product
	 * @param  int $product_id
	 * @return bool
	 */
	public static function deposits_enabled( $product_id, $check_variations = true ) {
		$product = wc_get_product( $product_id );

		if ( ! $product || $product->is_type( array( 'grouped', 'external' ) ) ) {
			return false;
		}

		$setting = get_post_meta( $product_id, '_wc_deposit_enabled', true );

		if ( $check_variations && empty( $setting ) ) {
			$children = get_children( array(
				'post_parent' => $product_id,
				'post_type' => 'product_variation',
			) );

			foreach ( $children as $child ) {
				$child_enabled = get_post_meta( $child->ID, '_wc_deposit_enabled', true );
				if ( $child_enabled ) {
					$setting = $child_enabled;
					break;
				}
			}
		}
		
		if ( empty( $setting ) ) {
			$setting = get_option( 'wc_deposits_default_enabled', 'no' );
		}

		if ( 'optional' === $setting || 'forced' === $setting ) {
			if ( 'plan' === self::get_deposit_type( $product_id ) && ! self::has_plans( $product_id ) ) {
				return false;
			}
			return true;
		}
		return false;
	}

	/**
	 * Are deposits forced for a specific product
	 * @param  int $product_id
	 * @return bool
	 */
	public static function deposits_forced( $product_id ) {
		$setting = get_post_meta( $product_id, '_wc_deposit_enabled', true );
		if ( empty( $setting ) ) {
			$setting = get_option( 'wc_deposits_default_enabled', 'no' );
		}
		return 'forced' === $setting;
	}

	/**
	 * Get desposit type
	 * @param  int $product_id
	 * @return string
	 */
	public static function get_deposit_type( $product_id ) {
		$setting = get_post_meta( $product_id, '_wc_deposit_type', true );
		if ( ! $setting ) {
			$setting = get_option( 'wc_deposits_default_type', 'percent' );
		}
		return $setting;
	}

	/**
	 * Does the product have plans?
	 * @param  int  $product_id
	 * @return int
	 */
	public static function has_plans( $product_id, $inc_variations = true ) {
		$plans = count( array_map( 'absint', array_filter( (array) get_post_meta( $product_id, '_wc_deposit_payment_plans', true ) ) ) );

		// If using variations, merge child variations into array
		if ( $inc_variations ) {
			$children = get_children( array(
				'post_parent' => $product_id,
				'post_type' => 'product_variation',
			) );

			foreach ( $children as $child ) {
				$plans = +count( array_map( 'absint', array_filter( (array) get_post_meta( $child->ID, '_wc_deposit_payment_plans', true ) ) ) );
			}
		}
		
		if ( $plans <= 0 ) {
			$default_payment_plans = get_option( 'wc_deposits_default_plans', array() );
			if ( empty( $default_payment_plans ) ) {
				return 0;
			}
			return count( $default_payment_plans );
		}
		return $plans;
	}

	/**
	 * Formatted deposit amount for a product based on fixed or %
	 * @param  int $product_id
	 * @return string
	 */
	public static function get_formatted_deposit_amount( $product_id ) {
		$product = wc_get_product( $product_id );

		if ( $amount = self::get_deposit_amount_for_display( $product ) ) {
			$type   = self::get_deposit_type( $product_id );

			if ( $product->is_type( 'booking' ) && 'yes' === get_post_meta( $product_id, '_wc_deposit_multiple_cost_by_booking_persons', true ) ) {
				$item = __( 'person', 'woocommerce-deposits' );
			} else {
				$item = __( 'item', 'woocommerce-deposits' );
			}

			if ( 'percent' === $type ) {
				return sprintf( __( 'Pay a %1$s deposit per %2$s', 'woocommerce-deposits' ), '<span class="wc-deposits-amount">' . $amount . '</span>', $item );
			} else {
				return sprintf( __( 'Pay a deposit of %1$s per %2$s', 'woocommerce-deposits' ), '<span class="wc-deposits-amount">' . $amount . '</span>', $item );
			}
		}
		return '';
	}

	/**
	 * Deposit amount for a product based on fixed or %
	 * @param  WC_Product|int $product
	 * @param  int $plan_id
	 * @return float|bool
	 */
	public static function get_deposit_amount_for_display( $product, $plan_id = 0 ) {
		self::get_deposit_amount( $product, $plan_id );
	}

	/**
	 * Deposit amount for a product based on fixed or % using actual prices
	 * @param  WC_Product|int $product
	 * @param  int $plan_id
	 * @param  string $context of display Valid values display or order
	 * @param  float $product_price If the price differs from that set in the product
	 * @return float|bool
	 */
	public static function get_deposit_amount( $product, $plan_id = 0, $context = 'display', $product_price = null ) {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}
		
		$item_id = ( $product->variation_id ) ? $product->variation_id : $product->id;
		
		$type       = self::get_deposit_type( $item_id );
		$percentage = false;

		if ( in_array( $type, array( 'fixed', 'percent' ) ) ) {
			$amount = get_post_meta( $item_id, '_wc_deposit_amount', true );

			if ( ! $amount ) {
				$amount = get_option( 'wc_deposits_default_amount' );
			}

			if ( ! $amount ) {
				return false;
			}

			if ( 'percent' === $type ) {
				$percentage = true;
			}
		} else {
			if ( ! $plan_id ) {
				return false;
			}

			$plan          = new WC_Deposits_Plan( $plan_id );
			$schedule      = $plan->get_schedule();
			$first_payment = current( $schedule );
			$amount        = $first_payment->amount;
			$percentage    = ( 'percentage' === $plan->get_type() );
		}

		if ( $percentage ) {
			$product_price = is_null( $product_price ) ? $product->get_price() : $product_price;
			$amount        = ( $product_price / 100 ) * $amount;
		}

		if ( 'display' === $context ) {
			$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
			$price            = $tax_display_mode == 'incl' ? $product->get_price_including_tax( 1, $amount ) : $product->get_price_excluding_tax( 1, $amount );
		} else {
			$price            = $amount;
		}

		return wc_format_decimal( $price );
	}
}
