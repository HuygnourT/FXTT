/**
 * Admin field helpers: repeaters and media pickers.
 *
 * Repeater rows keep their original index in the input names; PHP reads them
 * in DOM order, so moving a row only moves its element.
 */
( function () {
	'use strict';

	let counter = Date.now();

	function addRow( repeater ) {
		const template = repeater.querySelector( ':scope > [data-fxt-template]' );
		const rows = repeater.querySelector( ':scope > [data-fxt-rows]' );
		if ( ! template || ! rows ) {
			return;
		}
		const index = 'n' + ( counter++ );
		const html = template.innerHTML.split( '__INDEX__' ).join( index );
		const wrapper = document.createElement( 'div' );
		wrapper.innerHTML = html.trim();
		const row = wrapper.firstElementChild;
		rows.appendChild( row );
		const first = row.querySelector( 'input:not([type="hidden"]), select, textarea' );
		if ( first ) {
			first.focus();
		}
	}

	document.addEventListener( 'click', ( event ) => {
		const target = event.target;

		const add = target.closest( '[data-fxt-add]' );
		if ( add ) {
			event.preventDefault();
			addRow( add.closest( '[data-fxt-repeater]' ) );
			return;
		}

		const row = target.closest( '[data-fxt-row]' );
		if ( row ) {
			if ( target.closest( '[data-fxt-remove]' ) ) {
				event.preventDefault();
				const next = row.nextElementSibling || row.previousElementSibling;
				row.remove();
				const focus = next ? next.querySelector( 'input, select, textarea' ) : null;
				if ( focus ) {
					focus.focus();
				}
				return;
			}
			if ( target.closest( '[data-fxt-up]' ) && row.previousElementSibling ) {
				event.preventDefault();
				row.parentNode.insertBefore( row, row.previousElementSibling );
				target.focus();
				return;
			}
			if ( target.closest( '[data-fxt-down]' ) && row.nextElementSibling ) {
				event.preventDefault();
				row.parentNode.insertBefore( row.nextElementSibling, row );
				target.focus();
				return;
			}
		}

		const media = target.closest( '[data-fxt-media]' );
		if ( ! media ) {
			return;
		}
		const input = media.querySelector( 'input[type="hidden"]' );
		const preview = media.querySelector( 'img' );

		if ( target.closest( '[data-fxt-media-clear]' ) ) {
			event.preventDefault();
			input.value = '';
			preview.hidden = true;
			preview.removeAttribute( 'src' );
			return;
		}

		if ( target.closest( '[data-fxt-media-select]' ) && window.wp && window.wp.media ) {
			event.preventDefault();
			const frame = window.wp.media( { library: { type: 'image' }, multiple: false } );
			frame.on( 'select', () => {
				const attachment = frame.state().get( 'selection' ).first().toJSON();
				input.value = attachment.id;
				const sizes = attachment.sizes || {};
				preview.src = ( sizes.thumbnail || sizes.full || attachment ).url;
				preview.hidden = false;
			} );
			frame.open();
		}
	} );

	// Market map: add / remove a country row.
	document.addEventListener( 'click', ( event ) => {
		const add = event.target.closest( '[data-fxt-market-add]' );
		const remove = event.target.closest( '[data-fxt-market-remove]' );
		if ( add ) {
			event.preventDefault();
			const map = add.closest( '[data-fxt-market-map]' );
			const select = map.querySelector( '[data-fxt-market-select]' );
			const option = select.options[ select.selectedIndex ];
			if ( ! option || ! option.value ) {
				select.focus();
				return;
			}
			const code = option.value;
			const name = option.getAttribute( 'data-name' ) || code;
			const escapeHtml = ( text ) => text.replace( /[&<>"]/g, ( c ) => ( { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ c ] ) );
			const html = map.querySelector( '[data-fxt-market-template]' ).innerHTML
				.split( '__CODE__' ).join( code )
				.split( '__code__' ).join( code.toLowerCase() )
				.split( '__NAME__' ).join( escapeHtml( name ) );
			const wrapper = document.createElement( 'div' );
			wrapper.innerHTML = html.trim();
			const row = wrapper.firstElementChild;
			map.querySelector( '[data-fxt-market-rows]' ).appendChild( row );
			option.disabled = true;
			select.value = '';
			const first = row.querySelector( 'select, input' );
			if ( first ) {
				first.focus();
			}
		} else if ( remove ) {
			event.preventDefault();
			const row = remove.closest( '[data-fxt-market]' );
			const map = remove.closest( '[data-fxt-market-map]' );
			const option = map.querySelector( '[data-fxt-market-select] option[value="' + row.getAttribute( 'data-fxt-market' ) + '"]' );
			if ( option ) {
				option.disabled = false;
			}
			row.remove();
		}
	} );

	// Market rows: reflect the availability choice in the collapsed summary.
	document.addEventListener( 'change', ( event ) => {
		const select = event.target;
		if ( ! select.matches( '.fxt-market select[name$="[availability]"]' ) ) {
			return;
		}
		const state = select.closest( '.fxt-market' ).querySelector( '.fxt-market__state' );
		if ( state ) {
			state.textContent = select.options[ select.selectedIndex ].text;
		}
	} );
}() );

/**
 * Settings: live total of score weights (must be 100).
 */
( function () {
	'use strict';

	const selector = 'input[name^="fxt_settings[score_categories]"][name$="[weight]"]';

	function update() {
		const inputs = Array.from( document.querySelectorAll( selector ) ).filter( ( i ) => ! i.closest( 'template' ) );
		if ( ! inputs.length ) {
			return;
		}
		const repeater = inputs[ 0 ].closest( '[data-fxt-repeater]' );
		let total = repeater.parentNode.querySelector( '[data-fxt-weight-total]' );
		if ( ! total ) {
			total = document.createElement( 'p' );
			total.setAttribute( 'data-fxt-weight-total', '' );
			total.setAttribute( 'aria-live', 'polite' );
			repeater.parentNode.insertBefore( total, repeater.nextSibling );
		}
		const sum = inputs.reduce( ( acc, i ) => acc + ( parseInt( i.value, 10 ) || 0 ), 0 );
		total.textContent = 'Total: ' + sum + '%' + ( 100 === sum ? '' : ' (must be 100%)' );
		total.className = 100 === sum ? 'description' : 'description fxt-weight-total--bad';
	}

	document.addEventListener( 'input', ( e ) => {
		if ( e.target.matches( selector ) ) {
			update();
		}
	} );
	document.addEventListener( 'click', ( e ) => {
		if ( e.target.closest( '[data-fxt-remove], [data-fxt-add]' ) ) {
			window.setTimeout( update, 0 );
		}
	} );
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', update );
	} else {
		update();
	}
}() );
