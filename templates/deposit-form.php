<div class="wc-deposits-wrapper <?php echo WC_Deposits_Product_Manager::deposits_forced( $post->ID ) ? 'wc-deposits-forced' : 'wc-deposits-optional'; ?>">
	<?php if ( ! WC_Deposits_Product_Manager::deposits_forced( $post->ID ) ) : ?>
		<ul class="wc-deposits-option">
			<li><input type="radio" name="wc_deposit_option" value="yes" id="wc-option-pay-deposit" /><label for="wc-option-pay-deposit">Pay Deposit</label></li>
			<li><input type="radio" name="wc_deposit_option" value="no" id="wc-option-pay-full" /><label for="wc-option-pay-full">Pay in Full</label></li>
		</ul>
	<?php endif; ?>

	<?php if ( 'plan' === WC_Deposits_Product_Manager::get_deposit_type( $post->ID ) ) : ?>
		<ul class="wc-deposits-payment-plans">
			<?php foreach( WC_Deposits_Plans_Manager::get_plans_for_product( $post->ID ) as $key => $plan ) : ?>
				<li class="wc-deposits-payment-plan">
					<input type="radio" name="wc_deposit_payment_plan" <?php checked( $key, 0 ); ?> value="<?php echo esc_attr( $plan->get_id() ); ?>" id="wc-deposits-payment-plan-<?php echo esc_attr( $plan->get_id() ); ?>" /><label for="wc-deposits-payment-plan-<?php echo esc_attr( $plan->get_id() ); ?>">
						<strong class="wc-deposits-payment-plan-name"><?php echo esc_html( $plan->get_name() ); ?></strong>
						<small class="wc-deposits-payment-plan-description"><?php echo wp_kses_post( $plan->get_description() ); ?></small>
					</label>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<div class="wc-deposits-payment-description">
			<?php echo WC_Deposits_Product_Manager::get_formatted_deposit_amount( $post->ID ); ?>
		</div>
	<?php endif; ?>
</div>