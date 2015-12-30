jQuery( function( $ ){

	$( 'body' ).on( 'change', 'select#_wc_deposit_enabled', function () {
		$( '._wc_deposit_payment_plans_field' ).hide();
		$( '._wc_deposit_amount_field' ).hide();
		$( '._wc_deposit_multiple_cost_by_booking_persons_field' ).hide();
		$( '._wc_deposit_type_field' ).hide();

		if ( 'optional' === $(this).val() || 'forced' == $(this).val() ) {
			$( '._wc_deposit_type_field' ).show();
			$( 'select#_wc_deposit_type' ).change();
		}

		if ( '' === $(this).val() && 'no' !== $( '._wc_deposits_default_enabled_field' ).val() ) {
			$( '._wc_deposit_type_field' ).show();
			$( 'select#_wc_deposit_type' ).change();
		}
	} );

	$( 'body' ).on( 'change', 'select#_wc_deposit_type', function() {
		$( '._wc_deposit_payment_plans_field' ).hide();
		$( '._wc_deposit_amount_field' ).hide();
		$( '._wc_deposit_multiple_cost_by_booking_persons_field' ).hide();

		if ( 'percent' === $(this).val() )  {
			$( '#_wc_deposit_amount' ).attr( 'placeholder', $( '._wc_deposits_default_amount_field' ).val() );
			$( '._wc_deposit_amount_field' ).show();
		} else if ( 'fixed' === $(this).val() ) {
			$( '._wc_deposit_amount_field' ).show();
			$( '#_wc_deposit_amount' ).attr( 'placeholder', '0' );
		} else if ( 'plan' === $(this).val() ) {
			$( '._wc_deposit_payment_plans_field' ).show();
		} else if ( '' === $(this).val() ) {
			var default_type = $( '._wc_deposits_default_type_field' ).val();
			if ( 'plan' === default_type ) {
				$( '._wc_deposit_payment_plans_field' ).show();
			} else if ( 'percent' === default_type ) {
				$( '#_wc_deposit_amount' ).attr( 'placeholder', $( '._wc_deposits_default_amount_field' ).val() );
				$( '._wc_deposit_amount_field' ).show();
			} else if ( 'fixed' === default_type ) {
				$( '._wc_deposit_amount_field' ).show();
				$( '#_wc_deposit_amount' ).attr( 'placeholder', '0' );
			}
		}

		if ( 'fixed' === $(this).val() && 'booking' === $( '#product-type' ).val() ) {
			$( '._wc_deposit_multiple_cost_by_booking_persons_field' ).show();
		}
	} );

	$( 'select#_wc_deposit_type' ).change();
	$( 'select#_wc_deposit_enabled' ).change();

} );