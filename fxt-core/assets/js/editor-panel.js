/**
 * Block editor sidebar panel for Broker Reviews and Test Evidence: points
 * editors to the structured data panels below the content and opens them.
 */
( function ( wp ) {
	'use strict';

	const { registerPlugin } = wp.plugins;
	const PluginDocumentSettingPanel = ( wp.editor && wp.editor.PluginDocumentSettingPanel ) || ( wp.editPost && wp.editPost.PluginDocumentSettingPanel );
	const el = wp.element.createElement;
	const { __ } = wp.i18n;
	const { Button } = wp.components;
	const config = window.fxtCoreEditor || {};

	if ( ! PluginDocumentSettingPanel ) {
		return;
	}

	function openPanels() {
		// Meta boxes live in the main document, below the canvas.
		const area = document.querySelector( '.edit-post-layout__metaboxes, .edit-post-meta-boxes-area' );
		if ( ! area ) {
			return;
		}
		// Expand the resizable meta box pane when collapsed (WordPress 6.7+).
		const toggle = document.querySelector( '.edit-post-meta-boxes-main__presenter button[aria-expanded="false"]' );
		if ( toggle ) {
			toggle.click();
		}
		area.querySelectorAll( '.postbox.closed' ).forEach( ( box ) => box.classList.remove( 'closed' ) );
		area.scrollIntoView( { behavior: 'smooth', block: 'start' } );
		const first = area.querySelector( 'input:not([type="hidden"]), select, textarea' );
		if ( first ) {
			window.setTimeout( () => first.focus( { preventScroll: true } ), 400 );
		}
	}

	registerPlugin( 'fxt-core-data-panel', {
		render() {
			return el(
				PluginDocumentSettingPanel,
				{ name: 'fxt-core-data', title: config.title || __( 'Structured data', 'fxt-core' ), className: 'fxt-core-data-panel' },
				el( 'p', null, config.text || '' ),
				el( Button, { variant: 'secondary', onClick: openPanels }, config.button || __( 'Edit data', 'fxt-core' ) )
			);
		},
	} );
}( window.wp ) );
