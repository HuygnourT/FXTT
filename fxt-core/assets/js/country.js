/**
 * Country preference: picker dialog, cookie and region refresh.
 *
 * The country is a visitor preference stored in a cookie. No location data is
 * read. When it changes, regions marked [data-fxt-region] are re-rendered by
 * the REST endpoint (the same PHP views as the first render), on every page
 * type, without a reload. A region with data-fxt-keep keeps the children
 * matching that selector (e.g. the author box under a review).
 *
 * Page caches: a cached page may have been rendered for another country. On
 * load, when the saved country differs from the one the page was rendered
 * for, the regions are refreshed, so the choice is kept across pages.
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

	function readCookie() {
		const match = document.cookie.match( new RegExp( '(?:^|; )' + config.cookie + '=([^;]*)' ) );
		return match ? decodeURIComponent( match[ 1 ] ).toUpperCase() : '';
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

	/**
	 * Replace a region's content. Children matching data-fxt-keep stay.
	 *
	 * @param {Element} region Region element.
	 * @param {string}  html   New inner HTML.
	 */
	function swap( region, html ) {
		const keepSelector = region.getAttribute( 'data-fxt-keep' );
		if ( ! keepSelector ) {
			region.innerHTML = html;
			return;
		}
		const keep = Array.from( region.children ).find( ( child ) => child.matches( keepSelector ) );
		Array.from( region.childNodes ).forEach( ( node ) => {
			if ( node !== keep ) {
				node.remove();
			}
		} );
		const template = document.createElement( 'template' );
		template.innerHTML = html;
		region.insertBefore( template.content, keep || null );
	}

	function refreshRegions() {
		const regions = Array.from( document.querySelectorAll( '[data-fxt-region]' ) );
		const jobs = regions.map( ( region ) => {
			let args = {};
			try {
				args = JSON.parse( region.getAttribute( 'data-fxt-args' ) || '{}' );
			} catch ( e ) {
				args = {};
			}
			region.setAttribute( 'aria-busy', 'true' );
			return render( region.getAttribute( 'data-fxt-region' ), args )
				.then( ( html ) => swap( region, html ) )
				.finally( () => region.removeAttribute( 'aria-busy' ) );
		} );
		return Promise.all( jobs )
			.then( () => document.dispatchEvent( new CustomEvent( 'fxt:regions', { detail: { code: config.current } } ) ) )
			.catch( () => reloadWithout( 'country' ) );
	}

	function showCode( code ) {
		document.querySelectorAll( '[data-country-code]' ).forEach( ( el ) => {
			el.textContent = code;
		} );
		syncDialog( code );
	}

	/**
	 * A country the dialog offers (guards against stale or edited cookies).
	 *
	 * @param {string} code ISO code.
	 * @return {boolean} Known country.
	 */
	function isKnown( code ) {
		return /^[A-Z]{2}$/.test( code ) && ( ! dialog || !! dialog.querySelector( '[data-country-pick="' + code + '"]' ) );
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

		// The URL parameter outranks the cookie, so it is dropped from the
		// address (without a reload) once the visitor picks a country.
		const url = new URL( window.location.href );
		if ( url.searchParams.has( 'country' ) ) {
			url.searchParams.delete( 'country' );
			window.history.replaceState( window.history.state, '', url.toString() );
		}
		if ( ! changed ) {
			return;
		}

		showCode( code );
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
		const term = query.trim().toLowerCase().normalize( 'NFD' ).replace( /[\u0300-\u036f]/g, '' );
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

	// Keep the saved country across pages, also when a page cache served
	// this page as rendered for another country.
	( function syncOnLoad() {
		const saved = readCookie();
		const hasParam = new URL( window.location.href ).searchParams.has( 'country' );
		if ( hasParam || ! saved || saved === config.current || ! isKnown( saved ) ) {
			return;
		}
		config.current = saved;
		showCode( saved );
		if ( document.querySelector( '[data-fxt-region]' ) ) {
			refreshRegions();
		}
	}() );

	config.render = render;
	config.setCountry = setCountry;
	config.writeCookie = writeCookie;
	window.fxtCore = config;
}() );
