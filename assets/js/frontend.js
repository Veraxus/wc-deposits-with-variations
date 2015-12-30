jQuery(function($){
	
	var $wrapper = $('.wc-deposits-wrapper'),
		$plan_ul = $('.wc-deposits-payment-plans'),
		$plans = $plan_ul.find('.wc-deposits-payment-plan');

	$wrapper
		.on( 'init', function() {
			$(this).find( '.wc-deposits-option' ).find( 'input:eq(0)' ).click();
		})
		.on( 'change', 'input[name="wc_deposit_option"]', function() {
			var $this = $(this),
				$deposits = $this.closest('.wc-deposits-wrapper'),
				$description = $deposits.find('.wc-deposits-payment-plans, .wc-deposits-payment-description');
			if ( 'yes' === $this.val() ) {
				$description.slideDown( 200 );
			} else {
				$description.slideUp( 200 );
			}
		})
		.trigger( 'init' );
	
	$('form.variations_form')
		.ready(function(){
			$wrapper.hide();
			$plans.filter('.product_variation').hide();
			$(this).find('input.variation_id').change();
		})
		.on( 'change', 'input.variation_id', function(){
			var value = $(this).val(),
				$matches = $plans.filter('.item-'+value);
			console.log( value );
			if ( '' !== value ) {
				$plans.filter('.product_variation').hide();
				$matches.show();
				$wrapper.slideDown();
			}
			else {
				$plans.filter('.product_variation').hide();
				$wrapper.slideUp();
			}
		});

});