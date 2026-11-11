/**
 * Instrument evidence dialog (1.4.8).
 *
 * A "View evidence" button in a spread test table holds the images of one
 * instrument in one session (JSON in data-fxt-evidence-gallery). Clicking it
 * opens a dialog that shows those images one at a time:
 *   - Previous / Next buttons, Left / Right arrow keys, swipe on touch screens
 *   - position "2 / 5" (announced to screen readers)
 *   - Close button, Escape, or a click outside the dialog
 *   - focus moves into the dialog and back to the button when it closes
 *   - the page behind does not scroll while it is open
 * Images are requested only when shown (plus the next one, preloaded).
 */
( function () {
	'use strict';

	const t = window.fxtEvidenceGallery || {};
	let box = null;
	let state = null;

	function el( tag, attrs, html ) {
		const node = document.createElement( tag );
		Object.keys( attrs || {} ).forEach( ( k ) => node.setAttribute( k, attrs[ k ] ) );
		if ( html ) {
			node.innerHTML = html;
		}
		return node;
	}

	function build() {
		if ( box ) {
			return box;
		}
		box = el( 'dialog', { class: 'evidence-dialog evidence-gallery', 'aria-labelledby': 'evidence-gallery-title', 'data-evidence-gallery': '' } );
		box.innerHTML =
			'<div class="evidence-dialog__head">' +
				'<div><p class="u-label">' + ( t.label || 'Evidence' ) + ' ' + ( t.sample || '' ) + '</p><h2 class="evidence-dialog__title" id="evidence-gallery-title"></h2></div>' +
				'<button class="icon-btn" type="button" data-evg-close>' + ( t.iconX || '&times;' ) + '<span class="u-visually-hidden"></span></button>' +
			'</div>' +
			'<div class="evidence-gallery__stage" data-evg-stage>' +
				'<figure class="evidence-gallery__figure"><img class="evidence-gallery__img" alt="" decoding="async"><figcaption class="evidence-gallery__caption"></figcaption></figure>' +
			'</div>' +
			'<div class="evidence-gallery__nav">' +
				'<button type="button" class="btn btn--secondary btn--sm" data-evg-prev>' + ( t.iconPrev || '' ) + '<span></span></button>' +
				'<p class="evidence-gallery__count" aria-live="polite"><span aria-hidden="true" data-evg-count></span><span class="u-visually-hidden" data-evg-sr></span></p>' +
				'<button type="button" class="btn btn--secondary btn--sm" data-evg-next><span></span>' + ( t.iconNext || '' ) + '</button>' +
			'</div>' +
			'<p class="evidence-gallery__full"><a target="_blank" rel="noopener" data-evg-full></a></p>';
		box.querySelector( '[data-evg-close] .u-visually-hidden' ).textContent = t.close || 'Close evidence';
		box.querySelector( '[data-evg-prev] span' ).textContent = t.prev || 'Previous';
		box.querySelector( '[data-evg-prev]' ).setAttribute( 'aria-label', t.prevImg || 'Previous image' );
		box.querySelector( '[data-evg-next] span' ).textContent = t.next || 'Next';
		box.querySelector( '[data-evg-next]' ).setAttribute( 'aria-label', t.nextImg || 'Next image' );
		document.body.appendChild( box );

		box.addEventListener( 'click', ( e ) => {
			if ( e.target === box || e.target.closest( '[data-evg-close]' ) ) {
				box.close(); // Close button, or a click on the backdrop (outside the dialog).
			} else if ( e.target.closest( '[data-evg-prev]' ) ) {
				go( -1 );
			} else if ( e.target.closest( '[data-evg-next]' ) ) {
				go( 1 );
			}
		} );
		box.addEventListener( 'keydown', ( e ) => {
			if ( 'ArrowLeft' === e.key ) {
				e.preventDefault();
				go( -1 );
			} else if ( 'ArrowRight' === e.key ) {
				e.preventDefault();
				go( 1 );
			}
		} );
		box.addEventListener( 'close', () => {
			document.documentElement.classList.remove( 'has-evidence-dialog' );
			const opener = state && state.opener;
			state = null;
			box.querySelector( '.evidence-gallery__img' ).removeAttribute( 'src' );
			if ( opener && document.contains( opener ) ) {
				opener.focus();
			}
		} );
		// Swipe left / right on touch screens.
		const stage = box.querySelector( '[data-evg-stage]' );
		let startX = null;
		let startY = null;
		stage.addEventListener( 'pointerdown', ( e ) => {
			if ( 'mouse' !== e.pointerType ) {
				startX = e.clientX;
				startY = e.clientY;
			}
		} );
		stage.addEventListener( 'pointerup', ( e ) => {
			if ( null === startX ) {
				return;
			}
			const dx = e.clientX - startX;
			const dy = e.clientY - startY;
			startX = null;
			if ( Math.abs( dx ) > 40 && Math.abs( dx ) > Math.abs( dy ) ) {
				go( dx < 0 ? 1 : -1 );
			}
		} );
		stage.addEventListener( 'pointercancel', () => {
			startX = null;
		} );
		return box;
	}

	function show() {
		const image = state.images[ state.index ];
		const total = state.images.length;
		const img = box.querySelector( '.evidence-gallery__img' );
		img.removeAttribute( 'srcset' );
		img.width = image.w || 0;
		img.height = image.h || 0;
		if ( image.srcset ) {
			img.setAttribute( 'srcset', image.srcset );
			img.setAttribute( 'sizes', '(max-width: 720px) 100vw, 56rem' );
		}
		img.src = image.src;
		img.alt = image.alt || '';
		box.querySelector( '.evidence-gallery__caption' ).textContent = image.caption || '';
		box.querySelector( '[data-evg-count]' ).textContent = ( state.index + 1 ) + ' / ' + total;
		box.querySelector( '[data-evg-sr]' ).textContent = ( t.position || 'Image %1$d of %2$d' ).replace( '%1$d', state.index + 1 ).replace( '%2$d', total );
		const prev = box.querySelector( '[data-evg-prev]' );
		const next = box.querySelector( '[data-evg-next]' );
		prev.disabled = 0 === state.index;
		next.disabled = state.index === total - 1;
		box.classList.toggle( 'is-single', 1 === total );
		const full = box.querySelector( '[data-evg-full]' );
		full.href = image.full || image.src;
		full.textContent = ( t.full || 'Open full size' ) + ' ';
		full.appendChild( el( 'span', { class: 'u-visually-hidden' } ) ).textContent = t.newTab || '(opens in a new tab)';
		// Preload the next image only.
		if ( state.index + 1 < total ) {
			const pre = new window.Image();
			if ( state.images[ state.index + 1 ].srcset ) {
				pre.sizes = '(max-width: 720px) 100vw, 56rem';
				pre.srcset = state.images[ state.index + 1 ].srcset;
			}
			pre.src = state.images[ state.index + 1 ].src;
		}
	}

	function go( step ) {
		if ( ! state ) {
			return;
		}
		const index = Math.min( Math.max( state.index + step, 0 ), state.images.length - 1 );
		if ( index === state.index ) {
			return;
		}
		const focused = document.activeElement;
		state.index = index;
		show();
		// Keep keyboard focus usable when a button becomes disabled at either end.
		if ( focused && focused.disabled ) {
			box.querySelector( focused.matches( '[data-evg-prev]' ) ? '[data-evg-next]' : '[data-evg-prev]' ).focus();
		}
	}

	function open( trigger ) {
		let data;
		try {
			data = JSON.parse( trigger.getAttribute( 'data-fxt-evidence-gallery' ) );
		} catch ( e ) {
			return;
		}
		if ( ! data || ! data.images || ! data.images.length ) {
			return;
		}
		build();
		state = { images: data.images, index: 0, opener: trigger };
		box.querySelector( '#evidence-gallery-title' ).textContent = data.title || '';
		show();
		document.documentElement.classList.add( 'has-evidence-dialog' );
		box.showModal();
		box.querySelector( 1 < data.images.length ? '[data-evg-next]' : '[data-evg-close]' ).focus();
	}

	document.addEventListener( 'click', ( e ) => {
		const trigger = e.target.closest && e.target.closest( '[data-fxt-evidence-gallery]' );
		if ( trigger ) {
			e.preventDefault();
			open( trigger );
		}
	} );
}() );
