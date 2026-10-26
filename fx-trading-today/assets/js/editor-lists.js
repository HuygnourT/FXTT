/**
 * Numbered list style = ordered list (1.4.5).
 *
 * When a List block gets the "Numbered list" style, it and every list nested
 * inside it become ordered lists, so the saved markup is <ol><li>...</li></ol>.
 * Lists saved earlier as <ul> are converted when the post is opened (and on
 * the frontend by fxt_tt_numbered_list_markup()).
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.data || ! wp.data.subscribe ) {
		return;
	}
	const STYLE = /(^|\s)is-style-numbered(\s|$)/;
	let busy = false;

	function fix() {
		const select = wp.data.select( 'core/block-editor' );
		if ( ! select || ! select.getClientIdsWithDescendants ) {
			return;
		}
		const updates = [];
		select.getClientIdsWithDescendants().forEach( ( id ) => {
			if ( 'core/list' !== select.getBlockName( id ) ) {
				return;
			}
			const attrs = select.getBlockAttributes( id ) || {};
			if ( attrs.ordered ) {
				return;
			}
			// The list itself has the style, or a list above it does.
			const numbered = STYLE.test( attrs.className || '' ) || select.getBlockParents( id ).some( ( parent ) => 'core/list' === select.getBlockName( parent ) && STYLE.test( ( select.getBlockAttributes( parent ) || {} ).className || '' ) );
			if ( numbered ) {
				updates.push( id );
			}
		} );
		if ( updates.length ) {
			busy = true;
			wp.data.dispatch( 'core/block-editor' ).updateBlockAttributes( updates, { ordered: true } );
			busy = false;
		}
	}

	wp.data.subscribe( () => {
		if ( ! busy ) {
			fix();
		}
	} );
}( window.wp ) );
