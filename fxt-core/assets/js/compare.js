/**
 * Comparison widget enhancement.
 *
 * Without JavaScript the widget is a GET form. With it, every change asks the
 * render endpoint for the new widget and swaps it in place, keeping the URL in
 * sync when the block opts in (full comparison page).
 */
( function () {
	'use strict';

	const core = window.fxtCore || {};

	function state( inner ) {
		return {
			mode: inner.getAttribute( 'data-mode' ) || 'preview',
			slots: parseInt( inner.getAttribute( 'data-slots' ), 10 ) || 3,
			brokers: ( inner.getAttribute( 'data-selected' ) || '' ).split( ',' ).filter( Boolean ),
			all: '1' === inner.getAttribute( 'data-all' ),
			sync: '1' === inner.getAttribute( 'data-sync' ),
			base: inner.getAttribute( 'data-base' ) || '',
			rows: inner.getAttribute( 'data-rows' ) || '',
			groups: inner.getAttribute( 'data-groups' ) || '',
		};
	}

	function syncUrl( s ) {
		const url = new URL( window.location.href );
		[ 'brokers', 'brokers[]', 'remove', 'all' ].forEach( ( key ) => url.searchParams.delete( key ) );
		if ( s.brokers.length ) {
			url.searchParams.set( 'brokers', s.brokers.join( ',' ) );
		}
		if ( s.all ) {
			url.searchParams.set( 'all', '1' );
		}
		window.history.replaceState( null, '', url.toString() );
	}

	/**
	 * Re-render a widget with a new state.
	 *
	 * @param {Element} inner     Current .cmp-widget__inner.
	 * @param {Object}  s         New state.
	 * @param {string}  focusSel  Selector to focus after the swap.
	 */
	function update( inner, s, focusSel ) {
		if ( ! core.render ) {
			return;
		}
		const widget = inner.parentElement;
		widget.setAttribute( 'aria-busy', 'true' );
		core.render( 'compare', { mode: s.mode, slots: s.slots, brokers: s.brokers.join( ',' ), all: s.all ? 1 : 0, sync: s.sync ? 1 : 0, rows: s.rows, groups: s.groups }, s.base )
			.then( ( html ) => {
				inner.outerHTML = html;
				const fresh = widget.querySelector( '[data-fxt-compare]' );
				enhance( fresh );
				if ( s.sync ) {
					syncUrl( s );
				}
				if ( focusSel ) {
					const target = fresh.querySelector( focusSel );
					if ( target ) {
						target.focus();
					}
				}
			} )
			.catch( () => {
				// Fall back to the no-JS behaviour.
				const form = inner.querySelector( '[data-cmp-form]' );
				if ( form ) {
					form.submit();
				}
			} )
			.finally( () => widget.removeAttribute( 'aria-busy' ) );
	}

	function toggleGroup( inner, button, expand ) {
		const id = button.getAttribute( 'data-toggle-group' );
		const open = 'boolean' === typeof expand ? expand : 'true' !== button.getAttribute( 'aria-expanded' );
		button.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		inner.querySelectorAll( '[data-group="' + id + '"] tr:not(.cmp__group-row)' ).forEach( ( row ) => {
			row.hidden = ! open;
		} );
	}

	function enhance( inner ) {
		if ( ! inner || inner.hasAttribute( 'data-enhanced' ) ) {
			return;
		}
		inner.setAttribute( 'data-enhanced', '' );

		const submit = inner.querySelector( '[data-cmp-submit]' );
		if ( submit ) {
			submit.hidden = true;
		}
		inner.querySelectorAll( '[data-cmp-expand], [data-cmp-collapse]' ).forEach( ( button ) => {
			button.hidden = false;
		} );

		inner.addEventListener( 'change', ( event ) => {
			const select = event.target.closest( 'select[data-slot]' );
			if ( ! select ) {
				return;
			}
			const s = state( inner );
			const index = parseInt( select.getAttribute( 'data-slot' ), 10 );
			if ( select.value ) {
				s.brokers[ index ] = select.value;
			}
			s.brokers = s.brokers.filter( Boolean );
			update( inner, s, 'select[data-slot="' + Math.min( index, s.brokers.length ) + '"]' );
		} );

		inner.addEventListener( 'click', ( event ) => {
			const remove = event.target.closest( '[data-remove]' );
			const toggle = event.target.closest( '[data-toggle-group]' );
			const all = event.target.closest( '[data-cmp-all]' );
			const back = event.target.closest( '[data-cmp-selection]' );
			const expand = event.target.closest( '[data-cmp-expand]' );
			const collapse = event.target.closest( '[data-cmp-collapse]' );

			if ( remove ) {
				event.preventDefault();
				const s = state( inner );
				s.brokers.splice( parseInt( remove.getAttribute( 'data-remove' ), 10 ), 1 );
				update( inner, s, 'select[data-slot]' );
			} else if ( toggle ) {
				toggleGroup( inner, toggle );
			} else if ( expand || collapse ) {
				inner.querySelectorAll( '[data-toggle-group]' ).forEach( ( button ) => toggleGroup( inner, button, !! expand ) );
			} else if ( ( all || back ) && state( inner ).mode === 'full' ) {
				event.preventDefault();
				const s = state( inner );
				s.all = !! all;
				update( inner, s, all ? '[data-cmp-selection]' : '[data-cmp-all]' );
			}
		} );
	}

	function init() {
		document.querySelectorAll( '[data-fxt-compare]' ).forEach( enhance );
	}

	// Country change: re-render every widget for the new market.
	document.addEventListener( 'fxt:country', () => {
		document.querySelectorAll( '[data-fxt-compare]' ).forEach( ( inner ) => update( inner, state( inner ) ) );
	} );

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
