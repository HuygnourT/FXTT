/**
 * Trading cost calculator: spread cost and commission = value per 1.00 lot
 * x LOT, total = spread cost + commission, for every account type and
 * instrument, recalculated as the visitor types (no reload).
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

	// Cents for value-per-lot x hundredths of a lot, or null.
	function cents( value, hundredths ) {
		if ( null === value || '' === value ) {
			return null;
		}
		return Math.round( Number( ( Number( value ) * hundredths ).toFixed( 6 ) ) );
	}

	function render( box, hundredths ) {
		const na = box.getAttribute( 'data-na' ) || 'N/A';
		box.querySelectorAll( '[data-fxt-cost-row]' ).forEach( ( row ) => {
			const insts = {};
			row.querySelectorAll( '[data-inst]' ).forEach( ( cell ) => {
				const inst = cell.getAttribute( 'data-inst' );
				insts[ inst ] = insts[ inst ] || {};
				insts[ inst ][ cell.getAttribute( 'data-part' ) ] = cell;
			} );
			Object.keys( insts ).forEach( ( inst ) => {
				const cells = insts[ inst ];
				const spread = cells.spread ? cents( cells.spread.getAttribute( 'data-value' ), hundredths ) : null;
				const comm = cells.comm ? cents( cells.comm.getAttribute( 'data-value' ), hundredths ) : null;
				// Total = spread cost + commission, each rounded to cents first.
				const values = { spread, comm, total: null !== spread && null !== comm ? spread + comm : null };
				Object.keys( values ).forEach( ( part ) => {
					const cell = cells[ part ];
					if ( cell ) {
						cell.textContent = null === values[ part ] ? na : money( values[ part ] / 100, cell.getAttribute( 'data-unit' ) );
					}
				} );
			} );
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
