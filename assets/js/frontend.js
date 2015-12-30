jQuery(function($){

	$('.wc-deposits-wrapper')
		.on( 'init', function() {
			$(this).find( '.wc-deposits-option' ).find( 'input:eq(0)' ).click();
		})
		.on( 'change', 'input[name="wc_deposit_option"]', function() {
			$deposits = $(this).closest('.wc-deposits-wrapper');
			if ( 'yes' === $(this).val() ) {
				$deposits.find('.wc-deposits-payment-plans, .wc-deposits-payment-description').slideDown( 200 );
			} else {
				$deposits.find('.wc-deposits-payment-plans, .wc-deposits-payment-description').slideUp( 200 );
			}
		});

	$('.wc-deposits-wrapper').trigger( 'init' );
});