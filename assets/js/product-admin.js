jQuery( function( $ ){

	$( '#woocommerce-product-data' ).on( 'change', '._wc_deposit_enabled_field select', function () {
		
		var $panel = $(this).closest( '.woocommerce_deposits_panel' );
		
		$panel.find( '._wc_deposit_payment_plans_field' ).hide();
		$panel.find( '._wc_deposit_amount_field' ).hide();
		$panel.find( '._wc_deposit_multiple_cost_by_booking_persons_field' ).hide();
		$panel.find( '._wc_deposit_type_field' ).hide();

		if ( 'optional' === $(this).val() || 'forced' == $(this).val() ) {
			$panel.find( '._wc_deposit_type_field' ).show()
				.find( 'select' ).change();
		}

		if ( '' === $(this).val() && 'no' !== $( '._wc_deposits_default_enabled_field' ).val() ) {
			$panel.find( '._wc_deposit_type_field' ).show()
				.find( 'select' ).change();
		}
	} )

	.on( 'change', '._wc_deposit_type_field select', function() {
		
		var $panel = $(this).closest('.woocommerce_deposits_panel');

		$panel.find( '._wc_deposit_payment_plans_field' ).hide();
		$panel.find( '._wc_deposit_amount_field' ).hide();
		$panel.find( '._wc_deposit_multiple_cost_by_booking_persons_field' ).hide();

		if ( 'percent' === $(this).val() )  {
			
			$panel.find( '._wc_deposit_amount_field').find( 'input' ).attr( 'placeholder', $panel.find( '._wc_deposits_default_amount_field' ).val() )
				end().show();
			
		} else if ( 'fixed' === $(this).val() ) {
			
			$panel.find( '._wc_deposit_amount_field' ).show()
				.end().find( 'input' ).attr( 'placeholder', '0' );
			
		} else if ( 'plan' === $(this).val() ) {
			
			$panel.find( '._wc_deposit_payment_plans_field' ).show();
			
		} else if ( '' === $(this).val() ) {
			
			var default_type = $panel.find( '._wc_deposits_default_type_field' ).val();
			
			if ( 'plan' === default_type ) {
				$panel.find( '._wc_deposit_payment_plans_field' ).show();
				
			} else if ( 'percent' === default_type ) {
				$panel.find( '._wc_deposit_amount_field').find( 'input' ).attr( 'placeholder', $panel.find( '._wc_deposits_default_amount_field input' ).val() )
					.end().show();
				
			} else if ( 'fixed' === default_type ) {
				$panel.find( '._wc_deposit_amount_field' ).show()
					.end().find( 'input' ).attr( 'placeholder', '0' );
			}
			
		}

		if ( 'fixed' === $(this).val() && 'booking' === $( '#product-type' ).val() ) {
			$panel.find( '._wc_deposit_multiple_cost_by_booking_persons_field' ).show();
		}
	} )

	.on( 'woocommerce_variations_loaded', function(){
		console.log('Variations loaded');
		$( '._wc_deposit_type_field select' ).change();
		$( '._wc_deposit_enabled_field select' ).change();
	} )

	.find( '._wc_deposit_type_field select' ).change().end()
	.find( '._wc_deposit_enabled_field select' ).change();

} );