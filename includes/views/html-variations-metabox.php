<div class="show_if_variation_deposit" style="clear:both;border-top:1px solid #585858;<?php self::toggle_show_style( $allow_deposit, 'yes' ) ?>">
	<p style="font-size:1.1rem;font-weight:bold;margin-bottom:0;"><?php _e("Deposit/Preregistration Details",'nvLangScope'); ?></p>
	<p class="form-row form-row-first">
		<label><?php echo __( 'Initial Deposit:', 'wc_name_your_price' ) . ' ('.get_woocommerce_currency_symbol().')'; ?> 
			<a class="tips" data-tip="<?php _e( 'The amount the customer must deposit.', 'depfix'); ?>" href="#">[?]</a></label>
		<input type="text" size="5" class="wc_price_input" name="variation_deposit_amount[<?php echo $loop; ?>]" value="<?php echo esc_attr( $deposit_amt ); ?>" />
	</p>
	<p class="form-row form-row-last">
		<label for="variation_deposit_invoice_remainder[<?php echo $loop; ?>]"><?php echo __( 'Invoice Deposit Remainder:', 'wc_name_your_price' ); ?> 
			<a class="tips" data-tip="<?php _e( 'Should the remainder be invoiced immediately on checkout? Customers can then pay the remainder at their convenience.', 'depfix'); ?>" href="#">[?]</a></label>
		<label><input type="checkbox" id="variation_deposit_invoice_remainder[<?php echo $loop; ?>]" name="variation_deposit_invoice_remainder[<?php echo $loop; ?>]" <?php checked( $deposit_remainder, 'yes' ) ?> /> <em><?php _e("Invoice remainder immediately.",'depfix'); ?></em></label>
	</p>
	<div style="clear:both;"></div>
	<p class="form-row form-row-first">
		<label><?php echo __( 'Remainder Discount:', 'wc_name_your_price' ) . ' ('.get_woocommerce_currency_symbol().')'; ?>
			<a class="tips" data-tip="<?php _e( 'The discount to apply to preregistrations when paying the remainder.', 'depfix'); ?>" href="#">[?]</a></label>
		<input type="text" size="5" class="wc_price_input" name="variation_deposit_remainder_discount[<?php echo $loop; ?>]" value="<?php echo esc_attr( $remainder_discount ); ?>" />
	</p>
	<div style="clear:both;"></div>
</div>