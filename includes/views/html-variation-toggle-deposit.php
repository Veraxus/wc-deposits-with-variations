<label>
	<input type="checkbox" class="checkbox variation_is_deposit" name="variation_is_deposit[<?php echo $loop; ?>]" <?php checked( $allow_deposit, 'yes' ); ?> />
	<?php _e( 'Deposit', 'depfix'); ?> <a class="tips" data-tip="<?php _e( 'Customers can make a deposit toward this variation.', 'depfix'); ?>" href="#">[?]</a>
</label>