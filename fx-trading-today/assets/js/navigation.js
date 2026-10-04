/**
 * Header behaviour: mega menu, mobile drawer, scrim, Escape and outside click.
 * Without JavaScript the menu triggers are inert and the drawer stays closed;
 * every destination is still reachable through the footer menus.
 */
( function () {
	'use strict';

	const header = document.querySelector( '[data-site-header]' );
	if ( ! header ) {
		return;
	}
	const scrim = document.querySelector( '[data-scrim]' );
	const mobileToggle = header.querySelector( '[data-mobile-toggle]' );
	const triggers = Array.from( header.querySelectorAll( '[data-menu-trigger]' ) );
	let active = null;

	const panelFor = ( trigger ) => document.getElementById( trigger.getAttribute( 'aria-controls' ) );

	function setMobileIcon( isOpen ) {
		if ( ! mobileToggle ) {
			return;
		}
		mobileToggle.querySelector( '[data-icon-open]' ).hidden = isOpen;
		mobileToggle.querySelector( '[data-icon-close]' ).hidden = ! isOpen;
		mobileToggle.setAttribute( 'aria-label', mobileToggle.getAttribute( isOpen ? 'data-label-close' : 'data-label-open' ) );
	}

	function close( restoreFocus ) {
		if ( ! active ) {
			return;
		}
		const trigger = active;
		trigger.setAttribute( 'aria-expanded', 'false' );
		const panel = panelFor( trigger );
		if ( panel ) {
			panel.hidden = true;
		}
		if ( scrim ) {
			scrim.hidden = true;
		}
		document.body.style.overflow = '';
		if ( trigger === mobileToggle ) {
			setMobileIcon( false );
		}
		active = null;
		if ( restoreFocus ) {
			trigger.focus();
		}
	}

	function open( trigger ) {
		close( false );
		const panel = panelFor( trigger );
		if ( ! panel ) {
			return;
		}
		trigger.setAttribute( 'aria-expanded', 'true' );
		panel.hidden = false;
		active = trigger;
		if ( trigger === mobileToggle ) {
			setMobileIcon( true );
			// Bring the header to the top so the drawer fills the remaining height.
			const offset = header.getBoundingClientRect().top;
			if ( offset > 0 ) {
				window.scrollBy( 0, offset );
			}
			document.body.style.overflow = 'hidden';
		} else if ( scrim ) {
			scrim.hidden = false;
		}
	}

	triggers.concat( mobileToggle ? [ mobileToggle ] : [] ).forEach( ( trigger ) => {
		trigger.addEventListener( 'click', () => ( active === trigger ? close( false ) : open( trigger ) ) );
	} );

	if ( scrim ) {
		scrim.addEventListener( 'click', () => close( false ) );
	}

	document.addEventListener( 'keydown', ( event ) => {
		if ( 'Escape' === event.key && active ) {
			close( true );
		}
	} );

	document.addEventListener( 'click', ( event ) => {
		if ( ! active ) {
			return;
		}
		const panel = panelFor( active );
		if ( event.target.closest( 'a' ) && panel && panel.contains( event.target ) ) {
			close( false );
		} else if ( ! header.contains( event.target ) ) {
			close( false );
		}
	} );

	window.matchMedia( '(min-width: 1280px)' ).addEventListener( 'change', () => close( false ) );
}() );
