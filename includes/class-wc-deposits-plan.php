<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Deposits_Plans_Admin class
 */
class WC_Deposits_Plan {

	private $id;
	private $name;
	private $description;
	private $schedule;

	/**
	 * Plan Constructor
	 * @param object $plan Queried plan
	 */
	public function __construct( $plan ) {
		if ( is_numeric( $plan ) ) {
			global $wpdb;
			$plan = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->wc_deposits_payment_plans} WHERE ID = %d", absint( $plan ) ) );
		}
		$this->id          = $plan->ID;
		$this->name        = $plan->name;
		$this->description = $plan->description;
	}

	/**
	 * Get ID of the plan
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get name of the plan
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Get description of the plan
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Get the schedule for this plan
	 * Do not rely on in the "index" from the results array. Instead use "schedule_index"
	 * @return array
	 */
	public function get_schedule() {
		if ( ! $this->schedule ) {
			global $wpdb;
			$this->schedule = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->wc_deposits_payment_plans_schedule} WHERE plan_id = %d;", $this->get_id() ) );
		}

		// order by time limit
		usort( $this->schedule, array( $this, 'sort' ) );

		return $this->schedule;
	}

	/**
	 * Sorts a schedule so it lists intervals from smallest to highest
	 * Example: Immediately, 1 month, 2 weeks becomes Immediately, 2 weeks, 1 month
	 */
	private function sort( $schedule1, $schedule2 ) {
		$schedule1_interval = $schedule1->interval_amount * $this->get_unit_number_weight( $schedule1->interval_unit );
		$schedule2_interval = $schedule2->interval_amount * $this->get_unit_number_weight( $schedule2->interval_unit );

		if ( $schedule1_interval == $schedule2_interval ) {
			return 0;
		}

		return ( $schedule1_interval > $schedule2_interval ) ? 1 : -1;
	}

	/**
	 * When ordering our schedule, we need a 'weight' so that
	 * we can take the 'interval_amount' and get a meaningful metric
	 * to order by. It's OK that a year and month are not exact, since
	 * this function is only used for ordering and not calculation
	 *
	 * @param  string $unit Unit (week, year, month, day)
	 * @return int       Weight (7, 365, 30, 1)
	 */
	private function get_unit_number_weight( $unit ) {
		switch( $unit ) {
			case 'week':
				return 7;
				break;
			case 'year':
				return 365;
				break;
			case 'month':
				return 30;
				break;
			default:
				return 1;
		}
	}

	/**
	 * Get the total percent of the original cost for this plan.
	 * @return string
	 */
	public function get_total_percent() {
		$schedule      = $this->get_schedule();
		$total_percent = 0;

		foreach ( $schedule as $schedule_row ) {
			$total_percent += $schedule_row->amount;
		}

		return $total_percent;
	}

	/**
	 * Format the payment schedule for display
	 * @param  string $amount Optionaly define the amount being paid (if used when displaying a product)
	 * @return string
	 */
	public function get_formatted_schedule( $amount = '' ) {
		$schedule      = $this->get_schedule();
		$total_percent = $this->get_total_percent();
		$total_days    = 0;
		$total_years   = 0;
		$total_months  = 0;
		$duration      = array();

		foreach ( $schedule as $schedule_row ) {
			switch ( $schedule_row->interval_unit ) {
				case 'day' :
					$total_days += $schedule_row->interval_amount;
				break;
				case 'week' :
					$total_days += ( 7 * $schedule_row->interval_amount );
				break;
				case 'year' :
					$total_years += $schedule_row->interval_amount;
				break;
				case 'month' :
					$total_months += $schedule_row->interval_amount;
				break;
			}
		}

		if ( ! $amount ) {
			$amount = $total_percent . '%';
		} else {
			$amount = wc_price( $amount );
		}

		if ( $total_years ) {
			$duration[] = sprintf( _n( '%s year', '%s years', $total_years, 'woocommerce-deposits' ), $total_years );
		}
		if ( $total_months ) {
			$duration[] = sprintf( _n( '%s month', '%s months', $total_months, 'woocommerce-deposits' ), $total_months );
		}
		if ( $total_days ) {
			$duration[] = sprintf( _n( '%s day', '%s days', $total_days, 'woocommerce-deposits' ), $total_days );
		}

		if ( $duration ) {
			return sprintf( __( '%1$s payable over %2$s', 'woocommerce-deposits' ), $amount, implode( ', ', $duration ) );
		} else {
			return sprintf( __( '%1$s total payable', 'woocommerce-deposits' ), $amount );
		}
	}
}

