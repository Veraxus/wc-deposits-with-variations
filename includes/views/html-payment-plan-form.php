<div class="form-wrap">
	<form id="deposit-plan-form" method="post">
		<div class="form-field">
			<label for="plan_name"><?php _e( 'Plan Name', 'woocommerce-deposits' ); ?>:</label>
			<input type="text" name="plan_name" id="plan_name" class="input-text" placeholder="<?php _e( 'Payment Plan', 'woocommerce-deposits' ); ?>" value="<?php echo esc_attr( $plan_name ); ?>" />
		</div>
		<div class="form-field">
			<label for="plan_name"><?php _e( 'Plan Description', 'woocommerce-deposits' ); ?>:</label>
			<textarea name="plan_description" id="plan_description" cols="5" rows="2" placeholder="<?php _e( 'Describe this plan to the customer', 'woocommerce-deposits' ); ?>" class="input-text"><?php echo esc_textarea( $plan_description ); ?></textarea>
		</div>
		<div class="form-field">
			<?php
			$interval_units = '
				<option value="day">' . __( 'Days', 'woocommerce-deposits' ) . '</option>
				<option value="week">' . __( 'Weeks', 'woocommerce-deposits' ) . '</option>
				<option value="month">' . __( 'Months', 'woocommerce-deposits' ) . '</option>
				<option value="year">' . __( 'Years', 'woocommerce-deposits' ) . '</option>
				';
			$row = '<tr>
					<td class="cell-amount"><input type="number" placeholder="0" step="0.01" min="0" name="plan_amount[]" class="plan_amount" /></td>
					<td class="cell-percent">%</td>
					<td class="cell-after">' . __( 'After', 'woocommerce-deposits' ) . '</td>
					<td class="cell-interval-amount"><input type="number" name="plan_interval_amount[]" class="plan_interval_amount" min="0" value="1" step="1" /></td>
					<td class="cell-interval-unit"><select name="plan_interval_unit[]" class="plan_interval_unit">' . $interval_units . '</select></td>
					<td class="cell-actions"><a href="#" class="button add-row">+</a><a href="#" class="button remove-row">-</a></td>
				</tr>';
			?>
			<label><?php _e( 'Payment Schedule', 'woocommerce-deposits' ); ?>:</label>
			<table class="wc-deposits-plan" cellspacing="0" data-row="<?php echo esc_attr( $row ); ?>">
				<thead>
					<th colspan="2"><?php _e( 'Payment Amount', 'woocommerce-deposits' ); ?> <span class="tips" data-tip="<?php _e( 'This is the amount (in percent) based on the full product price.', 'woocommerce-deposits' ); ?>">[?]</span></th>
					<th colspan="3"><?php _e( 'Interval', 'woocommerce-deposits' ); ?> <span class="tips" data-tip="<?php _e( 'This is the interval between each payment.', 'woocommerce-deposits' ); ?>">[?]</span></th>
					<th>&nbsp;</th>
				</thead>
				<tfoot>
					<th colspan="2"><?php _e( 'Total:', 'woocommerce-deposits' ); ?> <span class="total_percent"></span>%</th>
					<th colspan="3"><?php _e( 'Total Duration:', 'woocommerce-deposits' ); ?> <span class="total_duration" data-days="<?php _e( 'Days', 'woocommerce-deposits' ); ?>" data-months="<?php _e( 'Months', 'woocommerce-deposits' ); ?>" data-years="<?php _e( 'Years', 'woocommerce-deposits' ); ?>"></span></th>
					<th></th>
				</tfoot>
				<tbody>
					<?php foreach ( $payment_schedule as $schedule ) :
						if ( ! $editing || empty( $schedule->schedule_index ) ) {
							$index = 0;
						} else {
							$index = $schedule->schedule_index;
						} ?>
						<tr>
							<td class="cell-amount"><input type="number" placeholder="0" step="0.01" min="0" name="plan_amount[<?php echo intval( $index ); ?>]" class="plan_amount" value="<?php echo esc_attr( $schedule->amount ); ?>" /></td>
							<td class="cell-percent">%</td>
							<?php if ( 0 === $index ) : ?>
								<td colspan="3">
									<?php _e( 'Immediately', 'woocommerce-deposits' ); ?>
									<input type="hidden" name="plan_interval_amount[<?php echo intval( $index ); ?>]" class="plan_interval_amount" value="0" />
									<input type="hidden" name="plan_interval_unit[<?php echo intval( $index ); ?>]" class="plan_interval_unit" value="0" />
								</td></td>
							<?php else : ?>
								<td class="cell-after"><?php _e( 'After', 'woocommerce-deposits' ); ?></td>
								<td class="cell-interval-amount"><input type="number" name="plan_interval_amount[<?php echo intval( $index ); ?>]" class="plan_interval_amount" min="0" value="<?php echo esc_attr( $schedule->interval_amount ); ?>" step="1" /></td>
								<td class="cell-interval-unit"><select name="plan_interval_unit[<?php echo intval( $index ); ?>]" class="plan_interval_unit">
									<option value="day" <?php selected( 'day', $schedule->interval_unit ); ?>><?php _e( 'Days', 'woocommerce-deposits' ); ?></option>
									<option value="week" <?php selected( 'week', $schedule->interval_unit ); ?>><?php _e( 'Weeks', 'woocommerce-deposits' ); ?></option>
									<option value="month" <?php selected( 'month', $schedule->interval_unit ); ?>><?php _e( 'Months', 'woocommerce-deposits' ); ?></option>
									<option value="year" <?php selected( 'year', $schedule->interval_unit ); ?>><?php _e( 'Years', 'woocommerce-deposits' ); ?></option>
								</select></td>
							<?php endif; ?>
							<td class="cell-actions"><a href="#" class="button add-row">+</a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<p class="submit"><input type="submit" class="button button-primary" name="save_plan" value="<?php _e( 'Save Payment Plan', 'woocommerce-deposits' ); ?>" /></p>
		<?php wp_nonce_field( 'woocommerce_save_plan', 'woocommerce_save_plan_nonce' ); ?>
	</form>
</div>