/**
 * Broker directory enhancement: live filtering.
 *
 * The filter panel is a plain GET form. With JavaScript, changes refresh the
 * results region through the render endpoint and the URL is kept shareable.
 */
( function () {
	'use strict';

	const core = window.fxtCore || {};
	const FILTERS = [ 'q', 'availability', 'regulator', 'platform', 'deposit', 'account', 'status', 'sort' ];

	function init( root ) {
		const form = root.querySelector( '[data-dir-form]' );
		const results = root.querySelector( '[data-dir-results]' );
		const count = root.querySelector( '[data-dir-count]' );
		if ( ! form || ! results || ! core.render ) {
			return;
		}
		const base = root.getAttribute( 'data-base' ) || window.location.href.split( /[?#]/ )[ 0 ];
		const submit = form.querySelector( '[data-dir-submit]' );
		if ( submit ) {
			submit.hidden = true;
		}

		if ( '#search' === window.location.hash ) {
			const field = form.querySelector( '[name="q"]' );
			if ( field ) {
				// After the browser's own hash scrolling settles.
				window.requestAnimationFrame( () => field.focus() );
			}
		}

		let timer = 0;
		let request = 0;

		function values() {
			const data = new FormData( form );
			const out = {};
			FILTERS.forEach( ( key ) => {
				const value = data.get( key );
				if ( value ) {
					out[ key ] = String( value );
				}
			} );
			return out;
		}

		function setField( name, value ) {
			const field = form.elements.namedItem( name ) || document.querySelector( '[name="' + name + '"][form="' + form.id + '"]' );
			if ( field ) {
				field.value = value;
			}
		}

		function refresh() {
			const filters = values();
			const id = ++request;
			results.setAttribute( 'aria-busy', 'true' );
			core.render( 'directory-results', filters, base )
				.then( ( html ) => {
					if ( id !== request ) {
						return;
					}
					results.innerHTML = html;
					const text = results.querySelector( '[data-dir-count-text]' );
					if ( text && count ) {
						count.textContent = text.textContent.trim();
					}
					const url = new URL( window.location.href );
					FILTERS.concat( [ 'country' ] ).forEach( ( key ) => url.searchParams.delete( key ) );
					Object.keys( filters ).forEach( ( key ) => url.searchParams.set( key, filters[ key ] ) );
					window.history.replaceState( null, '', url.toString() );
				} )
				.catch( () => form.submit() )
				.finally( () => {
					if ( id === request ) {
						results.removeAttribute( 'aria-busy' );
					}
				} );
		}

		function schedule( delay ) {
			window.clearTimeout( timer );
			timer = window.setTimeout( refresh, delay );
		}

		form.addEventListener( 'submit', ( event ) => {
			event.preventDefault();
			schedule( 0 );
		} );

		// The country select stores the preference and reloads (whole page depends on it).
		root.addEventListener( 'change', ( event ) => {
			if ( event.target.matches( '[data-dir-country]' ) ) {
				core.setCountry( event.target.value );
				return;
			}
			if ( event.target.name && FILTERS.indexOf( event.target.name ) !== -1 ) {
				schedule( 0 );
			}
		} );

		form.addEventListener( 'input', ( event ) => {
			if ( 'q' === event.target.name ) {
				schedule( 250 );
			}
		} );

		root.addEventListener( 'click', ( event ) => {
			const remove = event.target.closest( '[data-dir-remove]' );
			const clear = event.target.closest( '[data-dir-clear]' );
			if ( remove ) {
				event.preventDefault();
				setField( remove.getAttribute( 'data-dir-remove' ), '' );
				schedule( 0 );
			} else if ( clear ) {
				event.preventDefault();
				FILTERS.forEach( ( key ) => setField( key, 'sort' === key ? 'score' : '' ) );
				schedule( 0 );
				const search = form.querySelector( '[name="q"]' );
				if ( search ) {
					search.focus();
				}
			}
		} );
	}

	function boot() {
		document.querySelectorAll( '[data-fxt-directory]' ).forEach( init );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
