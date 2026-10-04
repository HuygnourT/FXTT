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
