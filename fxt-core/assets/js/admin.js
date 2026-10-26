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
		// Each nesting level has its own placeholder (__INDEX__, __INDEX1__...).
		const placeholder = template.getAttribute( 'data-fxt-placeholder' ) || '__INDEX__';
		const html = template.innerHTML.split( placeholder ).join( index );
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

		if ( target.closest( '[data-fxt-media-select]' ) ) {
			event.preventDefault();
			const i18n = window.fxtCoreAdmin || {};
			if ( ! window.wp || ! window.wp.media ) {
				// eslint-disable-next-line no-alert
				window.alert( i18n.noMedia || 'The media library could not be loaded.' );
				return;
			}
			// Native media uploader: upload a new file or pick one from the library.
			const frame = window.wp.media( {
				title: i18n.chooseImage || 'Choose image',
				button: { text: i18n.useImage || 'Use this image' },
				library: { type: 'image' },
				multiple: false,
			} );
			frame.on( 'open', () => {
				// Pre-select the current image when replacing it.
				if ( input.value && window.wp.media.attachment ) {
					const current = window.wp.media.attachment( input.value );
					current.fetch();
					frame.state().get( 'selection' ).reset( [ current ] );
				}
			} );
			frame.on( 'select', () => {
				const attachment = frame.state().get( 'selection' ).first().toJSON();
				input.value = attachment.id;
				input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
				const sizes = attachment.sizes || {};
				preview.src = ( sizes.thumbnail || sizes.medium || sizes.full || attachment ).url;
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

	// Tested residents rows: reflect the deposit test status in the collapsed summary.
	document.addEventListener( 'change', ( event ) => {
		const select = event.target;
		if ( ! select.matches( '.fxt-market select[name$="[deposit]"]' ) ) {
			return;
		}
		const state = select.closest( '.fxt-market' ).querySelector( '.fxt-market__state' );
		if ( state ) {
			state.textContent = state.textContent.replace( /:.*$/, ': ' + select.options[ select.selectedIndex ].text );
		}
	} );

	// Accepting residents: country checklist (search, only selected, select/clear shown, count).
	document.querySelectorAll( '[data-fxt-checklist]' ).forEach( ( box ) => {
		const search = box.querySelector( '[data-fxt-checklist-search]' );
		const only = box.querySelector( '[data-fxt-checklist-only]' );
		const count = box.querySelector( '[data-fxt-checklist-count]' );
		const items = Array.from( box.querySelectorAll( 'li[data-name]' ) );
		const normalize = ( t ) => t.toLowerCase().normalize( 'NFD' ).replace( /[\u0300-\u036f]/g, '' ).trim();
		function apply() {
			const term = normalize( search.value );
			items.forEach( ( li ) => {
				const checked = li.querySelector( 'input' ).checked;
				li.hidden = ( term && li.getAttribute( 'data-name' ).indexOf( term ) === -1 ) || ( only.checked && ! checked );
			} );
			const n = items.filter( ( li ) => li.querySelector( 'input' ).checked ).length;
			if ( count ) {
				count.textContent = ( 1 === n ? count.getAttribute( 'data-one' ) : count.getAttribute( 'data-many' ) ).replace( '%s', String( n ) );
			}
		}
		search.addEventListener( 'input', apply );
		only.addEventListener( 'change', apply );
		box.addEventListener( 'change', ( e ) => {
			if ( e.target.matches( 'li input' ) ) {
				apply();
			}
		} );
		box.querySelector( '[data-fxt-checklist-all]' ).addEventListener( 'click', () => {
			items.filter( ( li ) => ! li.hidden ).forEach( ( li ) => { li.querySelector( 'input' ).checked = true; } );
			apply();
		} );
		box.querySelector( '[data-fxt-checklist-none]' ).addEventListener( 'click', () => {
			items.filter( ( li ) => ! li.hidden ).forEach( ( li ) => { li.querySelector( 'input' ).checked = false; } );
			apply();
		} );
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

/**
 * Broker data: account types. Fields can be added, removed, renamed and
 * reordered; each field has a stable internal ID (new ones get a random ID),
 * so renaming never touches values. Every change is mirrored in the account
 * rows (inputs) and in the display options box (columns).
 */
( function () {
	'use strict';

	const box = document.querySelector( '[data-fxt-acc]' );
	if ( ! box ) {
		return;
	}
	const defs = box.querySelector( '[data-fxt-acc-defs]' );
	const cols = document.querySelector( '[data-fxt-acc-cols]' );
	const esc = ( text ) => String( text ).replace( /[&<>"]/g, ( c ) => ( { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ c ] ) );

	function fromTemplate( template, map ) {
		let html = template.innerHTML;
		Object.keys( map ).forEach( ( key ) => {
			html = html.split( key ).join( map[ key ] );
		} );
		html = html.trim();
		// Table rows must be parsed inside a table body.
		const wrapper = document.createElement( 0 === html.indexOf( '<tr' ) ? 'tbody' : 'div' );
		wrapper.innerHTML = html;
		return wrapper.firstElementChild;
	}

	// Account rows on the page plus the row template (index placeholder kept).
	function rows() {
		const list = Array.from( box.querySelectorAll( '[data-fxt-acc-rows] > [data-fxt-acc-row]' ) ).map( ( row ) => ( { row, index: row.getAttribute( 'data-fxt-acc-row' ) } ) );
		const template = box.querySelector( '[data-fxt-template]' );
		const tplRow = template && template.content.querySelector( '[data-fxt-acc-row]' );
		if ( tplRow ) {
			list.push( { row: tplRow, index: '__INDEX__' } );
		}
		return list;
	}

	function groupOf( row ) {
		return row.querySelector( ':scope > .fxt-group' );
	}

	function eachField( id, fn ) {
		rows().forEach( ( item ) => {
			const field = groupOf( item.row ).querySelector( ':scope > [data-fxt-acc-field="' + id + '"]' );
			if ( field ) {
				fn( field );
			}
		} );
	}

	function reorderInputs() {
		const ids = Array.from( defs.querySelectorAll( '[data-fxt-acc-def]' ) ).map( ( tr ) => tr.getAttribute( 'data-fxt-acc-def' ) );
		rows().forEach( ( item ) => {
			const group = groupOf( item.row );
			// Field inputs come before the calculator costs and the row source.
			const more = group.querySelector( ':scope > [data-fxt-acc-costs]' ) || group.querySelector( ':scope > [data-fxt-acc-more]' );
			ids.forEach( ( id ) => {
				const field = group.querySelector( ':scope > [data-fxt-acc-field="' + id + '"]' );
				if ( field ) {
					group.insertBefore( field, more );
				}
			} );
		} );
	}

	function addField() {
		const labelInput = box.querySelector( '[data-fxt-acc-newlabel]' );
		const typeSelect = box.querySelector( '[data-fxt-acc-newtype]' );
		const label = labelInput.value.trim();
		if ( ! label ) {
			labelInput.focus();
			return;
		}
		const type = typeSelect.value;
		let id;
		do {
			id = 'f_' + Date.now().toString( 36 ) + Math.random().toString( 36 ).slice( 2, 5 );
		} while ( defs.querySelector( '[data-fxt-acc-def="' + id + '"]' ) );

		const def = fromTemplate( box.querySelector( '[data-fxt-acc-def-template]' ), { __FIELD__: id, __LABEL__: esc( label ), __TYPE__: type } );
		def.querySelector( '[data-fxt-acc-typelabel]' ).textContent = typeSelect.options[ typeSelect.selectedIndex ].text;
		defs.appendChild( def );

		const input = box.querySelector( '[data-fxt-acc-input="' + type + '"]' );
		rows().forEach( ( item ) => {
			const field = fromTemplate( input, { __INDEX__: item.index, __FIELD__: id, __LABEL__: esc( label ) } );
			const group = groupOf( item.row );
			group.insertBefore( field, group.querySelector( ':scope > [data-fxt-acc-costs]' ) || group.querySelector( ':scope > [data-fxt-acc-more]' ) );
		} );

		if ( cols ) {
			cols.appendChild( fromTemplate( document.querySelector( '[data-fxt-acc-col-template]' ), { __FIELD__: id, __LABEL__: esc( label ) } ) );
		}
		labelInput.value = '';
		def.querySelector( '[data-fxt-acc-label]' ).focus();
	}

	box.addEventListener( 'click', ( event ) => {
		const target = event.target;
		if ( target.closest( '[data-fxt-acc-addfield]' ) ) {
			event.preventDefault();
			addField();
			return;
		}
		const def = target.closest( '[data-fxt-acc-def]' );
		if ( ! def ) {
			return;
		}
		const id = def.getAttribute( 'data-fxt-acc-def' );
		if ( target.closest( '[data-fxt-acc-remove]' ) ) {
			event.preventDefault();
			const label = def.querySelector( '[data-fxt-acc-label]' ).value;
			// eslint-disable-next-line no-alert
			if ( ! window.confirm( 'Remove the field "' + label + '"? Its values are deleted from every account type when the broker is saved.' ) ) {
				return;
			}
			def.remove();
			eachField( id, ( field ) => field.remove() );
			const col = cols && cols.querySelector( '[data-fxt-acc-col="' + id + '"]' );
			if ( col ) {
				col.remove();
			}
		} else if ( target.closest( '[data-fxt-acc-up]' ) && def.previousElementSibling ) {
			event.preventDefault();
			def.parentNode.insertBefore( def, def.previousElementSibling );
			reorderInputs();
			target.focus();
		} else if ( target.closest( '[data-fxt-acc-down]' ) && def.nextElementSibling ) {
			event.preventDefault();
			def.parentNode.insertBefore( def.nextElementSibling, def );
			reorderInputs();
			target.focus();
		}
	} );

	// Live rename: account rows and display options follow the label.
	box.addEventListener( 'input', ( event ) => {
		if ( ! event.target.matches( '[data-fxt-acc-label]' ) ) {
			return;
		}
		const id = event.target.closest( '[data-fxt-acc-def]' ).getAttribute( 'data-fxt-acc-def' );
		const label = event.target.value;
		eachField( id, ( field ) => {
			field.querySelectorAll( '[data-fxt-acc-fieldlabel]' ).forEach( ( span ) => {
				span.textContent = label;
			} );
		} );
		const col = cols && cols.querySelector( '[data-fxt-acc-col="' + id + '"] [data-fxt-acc-collabel]' );
		if ( col ) {
			col.textContent = label;
		}
	} );

	// Enter in the "new field" input adds the field instead of submitting.
	box.addEventListener( 'keydown', ( event ) => {
		if ( 'Enter' === event.key && event.target.matches( '[data-fxt-acc-newlabel]' ) ) {
			event.preventDefault();
			addField();
		}
	} );

	// Display options: order of the frontend columns.
	if ( cols ) {
		cols.addEventListener( 'click', ( event ) => {
			const item = event.target.closest( '[data-fxt-acc-col]' );
			if ( ! item ) {
				return;
			}
			if ( event.target.closest( '[data-fxt-acc-colup]' ) && item.previousElementSibling ) {
				event.preventDefault();
				item.parentNode.insertBefore( item, item.previousElementSibling );
				event.target.focus();
			} else if ( event.target.closest( '[data-fxt-acc-coldown]' ) && item.nextElementSibling ) {
				event.preventDefault();
				item.parentNode.insertBefore( item.nextElementSibling, item );
				event.target.focus();
			}
		} );
	}
}() );

/**
 * Image galleries (evidence images): add several images with the native
 * media uploader, remove any of them. Order = order of selection.
 */
( function () {
	'use strict';

	const i18n = window.fxtCoreAdmin || {};
	const esc = ( text ) => String( text ).replace( /[&<>"]/g, ( c ) => ( { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ c ] ) );

	document.addEventListener( 'click', ( event ) => {
		const remove = event.target.closest( '[data-fxt-gallery-remove]' );
		if ( remove ) {
			event.preventDefault();
			remove.closest( '.fxt-gallery__item' ).remove();
			return;
		}
		// Reorder: the saved order is the order of the items.
		const prev = event.target.closest( '[data-fxt-gallery-prev]' );
		const next = event.target.closest( '[data-fxt-gallery-next]' );
		if ( prev || next ) {
			event.preventDefault();
			const item = ( prev || next ).closest( '.fxt-gallery__item' );
			if ( prev && item.previousElementSibling ) {
				item.parentNode.insertBefore( item, item.previousElementSibling );
			} else if ( next && item.nextElementSibling ) {
				item.parentNode.insertBefore( item.nextElementSibling, item );
			}
			( prev || next ).focus();
			return;
		}
		// Replace one image, keeping its place.
		const replace = event.target.closest( '[data-fxt-gallery-replace]' );
		if ( replace ) {
			event.preventDefault();
			if ( ! window.wp || ! window.wp.media ) {
				// eslint-disable-next-line no-alert
				window.alert( i18n.noMedia || 'The media library could not be loaded.' );
				return;
			}
			const item = replace.closest( '.fxt-gallery__item' );
			const frame = window.wp.media( { title: i18n.chooseImage || 'Choose image', button: { text: i18n.useImage || 'Use this image' }, library: { type: 'image' }, multiple: false } );
			frame.on( 'select', () => {
				const attachment = frame.state().get( 'selection' ).first().toJSON();
				const sizes = attachment.sizes || {};
				item.setAttribute( 'data-id', attachment.id );
				item.querySelector( 'img' ).src = ( sizes.thumbnail || sizes.medium || sizes.full || attachment ).url;
				item.querySelector( 'input' ).value = attachment.id;
			} );
			frame.open();
			return;
		}
		const add = event.target.closest( '[data-fxt-gallery-add]' );
		if ( ! add ) {
			return;
		}
		event.preventDefault();
		if ( ! window.wp || ! window.wp.media ) {
			// eslint-disable-next-line no-alert
			window.alert( i18n.noMedia || 'The media library could not be loaded.' );
			return;
		}
		const gallery = add.closest( '[data-fxt-gallery]' );
		const list = gallery.querySelector( '[data-fxt-gallery-items]' );
		const frame = window.wp.media( {
			title: i18n.chooseImages || 'Choose images',
			button: { text: i18n.useImages || 'Add' },
			library: { type: 'image' },
			multiple: 'add',
		} );
		frame.on( 'select', () => {
			const have = Array.from( list.querySelectorAll( '[data-id]' ) ).map( ( li ) => li.getAttribute( 'data-id' ) );
			frame.state().get( 'selection' ).toJSON().forEach( ( attachment ) => {
				if ( have.indexOf( String( attachment.id ) ) !== -1 ) {
					return;
				}
				const sizes = attachment.sizes || {};
				const src = ( sizes.thumbnail || sizes.medium || sizes.full || attachment ).url;
				const li = document.createElement( 'li' );
				li.className = 'fxt-gallery__item';
				li.setAttribute( 'data-id', attachment.id );
				li.innerHTML = '<img src="' + esc( src ) + '" alt=""><input type="hidden" name="' + esc( gallery.getAttribute( 'data-name' ) ) + '" value="' + esc( attachment.id ) + '"><span class="fxt-gallery__tools"><button type="button" class="button-link" data-fxt-gallery-prev aria-label="' + esc( i18n.moveEarlier || 'Move earlier' ) + '">&larr;</button><button type="button" class="button-link" data-fxt-gallery-next aria-label="' + esc( i18n.moveLater || 'Move later' ) + '">&rarr;</button><button type="button" class="button-link" data-fxt-gallery-replace>' + esc( i18n.replace || 'Replace' ) + '</button><button type="button" class="button-link button-link-delete" data-fxt-gallery-remove>' + esc( i18n.remove || 'Remove' ) + '</button></span>';
				list.appendChild( li );
			} );
		} );
		frame.open();
	} );
}() );
