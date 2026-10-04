/**
 * Table of contents: built from the H2 headings of the content (headings
 * without an anchor get one), then a scroll-spy marks the current section
 * with aria-current="true".
 */
( function () {
	'use strict';

	function slugify( text ) {
		return text.toLowerCase().normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' ).replace( /[^a-z0-9]+/g, '-' ).replace( /^-|-$/g, '' ) || 'section';
	}

	function init( toc ) {
		const content = toc.parentElement.querySelector( '.doc-content' );
		if ( ! content ) {
			return;
		}
		const headings = Array.from( content.querySelectorAll( ':scope > h2, :scope > .wp-block-group > h2' ) ).filter( ( h ) => ! h.closest( '.author-box' ) );
		if ( headings.length < 2 ) {
			return;
		}
		const used = new Set();
		headings.forEach( ( h ) => {
			if ( ! h.id ) {
				let id = slugify( h.textContent );
				while ( used.has( id ) || document.getElementById( id ) ) {
					id += '-2';
				}
				h.id = id;
			}
			used.add( h.id );
			const link = document.createElement( 'a' );
			link.href = '#' + h.id;
			link.textContent = h.textContent.trim();
			toc.appendChild( link );
		} );
		toc.hidden = false;

		if ( ! ( 'IntersectionObserver' in window ) ) {
			return;
		}
		const links = Array.from( toc.querySelectorAll( 'a[href^="#"]' ) );
		const setActive = ( id ) => {
			links.forEach( ( a ) => {
				if ( a.getAttribute( 'href' ) === '#' + id ) {
					a.setAttribute( 'aria-current', 'true' );
				} else {
					a.removeAttribute( 'aria-current' );
				}
			} );
		};
		// A heading counts as current once it crosses the upper part of the viewport.
		const observer = new IntersectionObserver( ( entries ) => {
			entries.filter( ( e ) => e.isIntersecting ).forEach( ( e ) => setActive( e.target.id ) );
		}, { rootMargin: '-15% 0px -75% 0px' } );
		headings.forEach( ( h ) => observer.observe( h ) );
		setActive( headings[ 0 ].id );
	}

	document.querySelectorAll( '[data-toc]' ).forEach( init );
}() );
