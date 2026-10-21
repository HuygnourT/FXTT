/**
 * Trading cost calculator: total = cost per 1.00 lot x LOT, for every
 * account type, recalculated as the visitor types (no reload).
 *
 * LOT: positive, minimum 0.01, steps of 0.01. Work is done in hundredths of
 * a lot so 0.1 + 0.2 style float errors never reach the result. Listeners
 * are delegated, so calculators refreshed by the country selector work too.
 */
( function () {
	'use strict';

	const format = new Intl.NumberFormat( 'en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 } );

	function money( value, unit ) {
		unit = ( unit || '' ).trim() || '$';
		const number = format.format( value );
		// Letter codes go after the number ("8.00 USD"), symbols before ("$8.00").
		return /^\p{L}{2,}$/u.test( unit ) ? number + ' ' + unit : unit + number;
	}

	// Hundredths of a lot, or null when the value is not a valid lot size.
	function parseLot( raw ) {
		const text = String( raw ).trim().replace( ',', '.' );
		if ( ! /^(\d+(\.\d*)?|\.\d+)$/.test( text ) ) {
			return null;
		}
		const value = Number( text );
		const hundredths = Math.round( value * 100 );
		if ( ! isFinite( value ) || hundredths < 1 || Math.abs( value * 100 - hundredths ) > 1e-6 ) {
			return null;
		}
		return hundredths;
	}

	function render( box, hundredths ) {
		const na = box.getAttribute( 'data-na' ) || 'N/A';
		box.querySelectorAll( '[data-cost]' ).forEach( ( cell ) => {
			const cost = cell.getAttribute( 'data-cost' );
			if ( '' === cost ) {
				cell.textContent = na;
				return;
			}
			// cost x hundredths = total in cents.
			const cents = Math.round( Number( ( Number( cost ) * hundredths ).toFixed( 6 ) ) );
			cell.textContent = money( cents / 100, cell.getAttribute( 'data-unit' ) );
		} );
		box.querySelectorAll( '[data-fxt-lot-value]' ).forEach( ( el ) => {
			el.textContent = format.format( hundredths / 100 );
		} );
		box.setAttribute( 'data-lot', String( hundredths / 100 ) );
	}

	function setError( input, on ) {
		const help = input.closest( '[data-fxt-cost-calc]' ).querySelector( '[data-fxt-lot-help]' );
		if ( on ) {
			input.setAttribute( 'aria-invalid', 'true' );
		} else {
			input.removeAttribute( 'aria-invalid' );
		}
		if ( help ) {
			if ( ! help.hasAttribute( 'data-hint' ) ) {
				help.setAttribute( 'data-hint', help.textContent );
			}
			help.textContent = on ? help.getAttribute( 'data-error' ) : help.getAttribute( 'data-hint' );
			help.classList.toggle( 'cost-calc__help--error', on );
		}
	}

	// While typing: recalculate on every valid value, keep the last result otherwise.
	document.addEventListener( 'input', ( event ) => {
		const input = event.target;
		if ( ! input.matches || ! input.matches( '[data-fxt-lot]' ) ) {
			return;
		}
		const hundredths = parseLot( input.value );
		setError( input, null === hundredths && '' !== input.value.trim() );
		if ( null !== hundredths ) {
			render( input.closest( '[data-fxt-cost-calc]' ), hundredths );
		}
	} );

	// On leaving the field: show the value used, rounded to 0.01 (min 0.01).
	document.addEventListener( 'change', ( event ) => {
		const input = event.target;
		if ( ! input.matches || ! input.matches( '[data-fxt-lot]' ) ) {
			return;
		}
		const box = input.closest( '[data-fxt-cost-calc]' );
		let hundredths = parseLot( input.value );
		if ( null === hundredths ) {
			const value = Number( String( input.value ).replace( ',', '.' ) );
			hundredths = isFinite( value ) && value > 0 ? Math.max( 1, Math.round( value * 100 ) ) : Math.round( Number( box.getAttribute( 'data-lot' ) || 1 ) * 100 );
		}
		input.value = ( hundredths / 100 ).toFixed( 2 );
		setError( input, false );
		render( box, hundredths );
	} );
}() );
