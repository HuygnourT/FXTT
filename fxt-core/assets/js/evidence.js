/**
 * Evidence viewer: opens a pre-rendered (already masked) record in a dialog.
 */
( function () {
	'use strict';

	let opener = null;

	function dialog() {
		return document.querySelector( '[data-evidence-dialog]' );
	}

	function open( id, trigger ) {
		const box = dialog();
		const template = document.querySelector( '[data-evidence-record="' + CSS.escape( id ) + '"]' );
		if ( ! box || ! template ) {
			return;
		}
		box.replaceChildren( template.content.cloneNode( true ) );
		opener = trigger;
		box.showModal();
		const close = box.querySelector( '[data-close-evidence]' );
		if ( close ) {
			close.focus();
		}
	}

	document.addEventListener( 'click', ( event ) => {
		const trigger = event.target.closest( '[data-view-evidence]' );
		if ( trigger ) {
			event.preventDefault();
			open( trigger.getAttribute( 'data-view-evidence' ), trigger );
			return;
		}
		const box = dialog();
		if ( box && box.open && ( event.target.closest( '[data-close-evidence]' ) || event.target === box ) ) {
			box.close();
		}
	} );

	document.addEventListener(
		'close',
		( event ) => {
			if ( event.target.matches && event.target.matches( '[data-evidence-dialog]' ) ) {
				event.target.replaceChildren();
				if ( opener && document.contains( opener ) ) {
					opener.focus();
				}
				opener = null;
			}
		},
		true
	);
}() );
