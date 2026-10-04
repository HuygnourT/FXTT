/**
 * Country preference: picker dialog, cookie and region refresh.
 *
 * The country is a visitor preference stored in a cookie. No location data is
 * read. When it changes, regions marked [data-fxt-region] are re-rendered by
 * the REST endpoint (the same PHP views as the first render). Pages whose
 * whole body depends on the country reload instead.
 *
 * Exposes window.fxtCore.render() and window.fxtCore.setCountry() for the
 * compare and directory scripts.
 */
( function () {
	'use strict';

	const config = window.fxtCore || {};
	if ( ! config.renderUrl ) {
		return;
	}

	/**
	 * Fetch a region's HTML from the render endpoint.
	 *
	 * @param {string} view View name.
	 * @param {Object} args View arguments.
	 * @param {string} base Base URL for links inside the region.
	 * @return {Promise<string>} Rendered HTML.
	 */
	function render( view, args, base ) {
		const url = new URL( config.renderUrl, window.location.href );
		url.searchParams.set( 'view', view );
		url.searchParams.set( 'args', JSON.stringify( args || {} ) );
		url.searchParams.set( 'base', base || window.location.href.split( /[?#]/ )[ 0 ] );
		url.searchParams.set( 'country', config.current || '' );
		return fetch( url.toString(), { credentials: 'same-origin', headers: { Accept: 'application/json' } } )
			.then( ( response ) => {
				if ( ! response.ok ) {
					throw new Error( 'Render failed: ' + response.status );
				}
				return response.json();
			} )
			.then( ( data ) => String( data.html || '' ) );
	}

	function writeCookie( code ) {
		const secure = 'https:' === window.location.protocol ? '; Secure' : '';
		document.cookie = config.cookie + '=' + encodeURIComponent( code ) + '; Max-Age=31536000; Path=/; SameSite=Lax' + secure;
	}

	function reloadWithout( param ) {
		const url = new URL( window.location.href );
		url.searchParams.delete( param );
		window.location.assign( url.toString() );
	}

	function refreshRegions() {
		const regions = document.querySelectorAll( '[data-fxt-region]' );
		regions.forEach( ( region ) => {
			let args = {};
			try {
				args = JSON.parse( region.getAttribute( 'data-fxt-args' ) || '{}' );
			} catch ( e ) {
				args = {};
			}
			region.setAttribute( 'aria-busy', 'true' );
			render( region.getAttribute( 'data-fxt-region' ), args )
				.then( ( html ) => {
					region.innerHTML = html;
				} )
				.catch( () => reloadWithout( 'country' ) )
				.finally( () => region.removeAttribute( 'aria-busy' ) );
		} );
	}

	/**
	 * Store a new country and update the page.
	 *
	 * @param {string} code ISO code.
	 */
	function setCountry( code ) {
		code = String( code || '' ).toUpperCase();
		if ( ! /^[A-Z]{2}$/.test( code ) ) {
			return;
		}
		writeCookie( code );
		const changed = code !== config.current;
		config.current = code;

		// The URL parameter outranks the cookie, so it has to go.
		const hasParam = new URL( window.location.href ).searchParams.has( 'country' );
		if ( config.reload || hasParam || document.querySelector( '[data-fxt-directory]' ) ) {
			if ( changed || hasParam ) {
				reloadWithout( 'country' );
			}
			return;
		}
		if ( ! changed ) {
			return;
		}

		document.querySelectorAll( '[data-country-code]' ).forEach( ( el ) => {
			el.textContent = code;
		} );
		syncDialog( code );
		refreshRegions();
		document.dispatchEvent( new CustomEvent( 'fxt:country', { detail: { code } } ) );
	}

	/* ---------------------------------------------------------------------
	 * Dialog
	 * ------------------------------------------------------------------ */

	const dialog = document.querySelector( '[data-country-dialog]' );
	let opener = null;

	function syncDialog( code ) {
		if ( ! dialog ) {
			return;
		}
		dialog.querySelectorAll( '[data-country-pick]' ).forEach( ( button ) => {
			const active = button.getAttribute( 'data-country-pick' ) === code;
			button.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
			const check = button.querySelector( '.country-option__check' );
			if ( check && ! active ) {
				check.remove();
				const target = dialog.querySelector( '[data-country-pick="' + code + '"]' );
				if ( target ) {
					target.appendChild( check );
				}
			}
		} );
	}

	function filterList( query ) {
		const term = query.trim().toLowerCase();
		let visible = 0;
		dialog.querySelectorAll( '[data-country-item]' ).forEach( ( item ) => {
			const match = ! term || ( item.getAttribute( 'data-name' ) || '' ).indexOf( term ) !== -1;
			item.hidden = ! match;
			visible += match ? 1 : 0;
		} );
		const empty = dialog.querySelector( '[data-country-empty]' );
		if ( empty ) {
			empty.hidden = visible > 0;
		}
	}

	function openDialog( trigger ) {
		if ( ! dialog || dialog.open ) {
			return;
		}
		opener = trigger;
		const search = dialog.querySelector( '[data-country-search]' );
		if ( search ) {
			search.value = '';
			filterList( '' );
		}
		dialog.showModal();
		const current = dialog.querySelector( '[aria-pressed="true"]' );
		( current || search || dialog ).focus();
	}

	function closeDialog() {
		if ( dialog && dialog.open ) {
			dialog.close();
		}
	}

	document.addEventListener( 'click', ( event ) => {
		const trigger = event.target.closest( '[data-open-country], a[href$="#fxt-country"]' );
		if ( trigger ) {
			event.preventDefault();
			openDialog( trigger );
		}
	} );

	if ( dialog ) {
		dialog.addEventListener( 'click', ( event ) => {
			const pick = event.target.closest( '[data-country-pick]' );
			if ( pick ) {
				closeDialog();
				setCountry( pick.getAttribute( 'data-country-pick' ) );
				return;
			}
			// Close on the close button or a click on the backdrop.
			if ( event.target.closest( '[data-country-close]' ) || event.target === dialog ) {
				closeDialog();
			}
		} );
		const search = dialog.querySelector( '[data-country-search]' );
		if ( search ) {
			search.addEventListener( 'input', () => filterList( search.value ) );
		}
		dialog.addEventListener( 'close', () => {
			if ( opener && document.contains( opener ) ) {
				opener.focus();
			}
			opener = null;
		} );
	}

	config.render = render;
	config.setCountry = setCountry;
	config.writeCookie = writeCookie;
	window.fxtCore = config;
}() );
