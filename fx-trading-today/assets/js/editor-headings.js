/**
 * "Heading ID" panel for H2 headings (1.4.6).
 *
 * Edits the Heading block's own anchor (the same value as Advanced >
 * HTML anchor), so the saved markup stays core markup: <h2 id="...">.
 * Shows the ID the site creates from the text when the field is empty,
 * keeps IDs to letters, numbers, "-" and "_", and warns about an ID used
 * twice in the same content. No build step.
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.hooks || ! wp.compose || ! wp.blockEditor || ! wp.components ) {
		return;
	}

	const el = wp.element.createElement;
	const { __, sprintf } = wp.i18n;
	const { InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl, Button, Notice } = wp.components;
	const { useSelect } = wp.data;

	// Same rule as fxt_tt_heading_slug() in inc/headings.php.
	function slug( html ) {
		const div = document.createElement( 'div' );
		div.innerHTML = String( html || '' );
		const text = ( div.textContent || '' ).replace( /[đĐ]/g, 'd' ).normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' ).toLowerCase();
		return text.replace( /[^a-z0-9]+/g, '-' ).replace( /^-+|-+$/g, '' ) || 'section';
	}

	// What an editor may type: letters, numbers, "-" and "_"; spaces become "-".
	function clean( value ) {
		return String( value || '' ).replace( /\s+/g, '-' ).replace( /[^A-Za-z0-9_-]/g, '' );
	}

	function anchors( blocks, out ) {
		blocks.forEach( ( b ) => {
			if ( b.attributes && b.attributes.anchor ) {
				out.push( { id: b.attributes.anchor, clientId: b.clientId } );
			}
			anchors( b.innerBlocks || [], out );
		} );
		return out;
	}

	function HeadingId( props ) {
		const { attributes, setAttributes, clientId } = props;
		const others = useSelect( ( select ) => anchors( select( 'core/block-editor' ).getBlocks(), [] ).filter( ( a ) => a.clientId !== clientId ), [ clientId ] );
		const value = attributes.anchor || '';
		const auto = slug( attributes.content && attributes.content.toString ? attributes.content.toString() : attributes.content );
		const twice = value && others.some( ( a ) => a.id === value );
		return el(
			PanelBody,
			{ title: __( 'Heading ID', 'fx-trading-today' ), initialOpen: true, className: 'fxt-heading-id' },
			el( TextControl, {
				label: __( 'ID of this H2', 'fx-trading-today' ),
				value,
				placeholder: auto,
				help: value
					? sprintf( /* translators: %s: link */ __( 'Link to this section: #%s', 'fx-trading-today' ), value )
					: sprintf( /* translators: %s: id */ __( 'Empty: the site uses "%s" (made from the heading text). Letters, numbers, - and _ only.', 'fx-trading-today' ), auto ),
				onChange: ( v ) => setAttributes( { anchor: clean( v ) || undefined } ),
			} ),
			el(
				'div',
				{ style: { display: 'flex', gap: '8px', flexWrap: 'wrap' } },
				el( Button, { variant: 'secondary', size: 'small', onClick: () => setAttributes( { anchor: auto } ) }, __( 'Use the heading text', 'fx-trading-today' ) ),
				value ? el( Button, { variant: 'tertiary', size: 'small', onClick: () => setAttributes( { anchor: undefined } ) }, __( 'Clear', 'fx-trading-today' ) ) : null
			),
			twice
				? el( Notice, { status: 'warning', isDismissible: false, className: 'fxt-heading-id__warning' }, __( 'Another block on this page already uses this ID. Each ID must be unique.', 'fx-trading-today' ) )
				: null
		);
	}

	const withHeadingId = wp.compose.createHigherOrderComponent( ( BlockEdit ) => ( props ) => {
		if ( 'core/heading' !== props.name || 2 !== ( props.attributes.level || 2 ) ) {
			return el( BlockEdit, props );
		}
		return el(
			wp.element.Fragment,
			null,
			el( BlockEdit, props ),
			props.isSelected ? el( InspectorControls, null, el( HeadingId, props ) ) : null
		);
	}, 'withFxtHeadingId' );

	wp.hooks.addFilter( 'editor.BlockEdit', 'fxt-tt/heading-id', withHeadingId );
}( window.wp ) );
