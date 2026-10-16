/**
 * Inline "Source label" for any rich text (paragraphs, lists, tables,
 * headings...): Tested, Verified at source, Broker-stated or
 * Not tested / Not verified, with an optional link.
 *
 * Stored as <a class="fxt-source-label" data-kind="..." [href] [target]>text</a>;
 * PHP turns a label without a link into a <span> and adds the site's label
 * classes on output (fxt_core_label_markup()). No build step.
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.richText || ! wp.blockEditor ) {
		return;
	}

	const el = wp.element.createElement;
	const { useState, Fragment } = wp.element;
	const { __ } = wp.i18n;
	const { registerFormatType, applyFormat, removeFormat, insert, create, isCollapsed, getActiveFormat } = wp.richText;
	const { RichTextToolbarButton } = wp.blockEditor;
	const { Popover, SelectControl, TextControl, ToggleControl, Button } = wp.components;

	const NAME = 'fxt/source-label';
	const kinds = ( window.fxtCoreLabels && window.fxtCoreLabels.kinds ) || {
		tested: 'Tested',
		verified: 'Verified at source',
		stated: 'Broker-stated',
		unverified: 'Not tested / Not verified',
	};

	function LabelEdit( props ) {
		const { value, onChange, isActive, contentRef } = props;
		const active = getActiveFormat( value, NAME );
		const current = ( active && active.attributes ) || {};
		const [ open, setOpen ] = useState( false );
		const [ kind, setKind ] = useState( 'tested' );
		const [ text, setText ] = useState( '' );
		const [ url, setUrl ] = useState( '' );
		const [ newTab, setNewTab ] = useState( false );
		const anchor = wp.richText.useAnchor
			? wp.richText.useAnchor( { editableContentElement: contentRef && contentRef.current, settings: { tagName: 'a', className: 'fxt-source-label', title: __( 'Source label', 'fxt-core' ) } } )
			: undefined;

		function start() {
			setKind( current.kind || 'tested' );
			setUrl( current.url || '' );
			setNewTab( '_blank' === current.target );
			setText( '' );
			setOpen( true );
		}

		function attributes() {
			const attrs = { kind };
			const link = url.trim();
			if ( link ) {
				attrs.url = link;
				if ( newTab ) {
					attrs.target = '_blank';
					attrs.rel = 'noopener';
				}
			}
			return attrs;
		}

		function apply() {
			const format = { type: NAME, attributes: attributes() };
			if ( isCollapsed( value ) && ! isActive ) {
				// Nothing selected: insert the label text at the cursor.
				const label = ( text.trim() || kinds[ kind ] || kind );
				const startAt = value.start;
				const withText = insert( value, create( { text: label } ) );
				onChange( applyFormat( withText, format, startAt, startAt + label.length ) );
			} else {
				// Selected text, or the cursor inside an existing label: (re)apply.
				onChange( applyFormat( value, format ) );
			}
			setOpen( false );
		}

		return el(
			Fragment,
			null,
			el( RichTextToolbarButton, {
				icon: 'tag',
				title: isActive ? __( 'Edit source label', 'fxt-core' ) : __( 'Source label', 'fxt-core' ),
				onClick: start,
				isActive,
			} ),
			open && el(
				Popover,
				{ anchor, placement: 'bottom', onClose: () => setOpen( false ), className: 'fxt-label-popover' },
				el(
					'div',
					{ style: { padding: '12px', minWidth: '260px' } },
					el( SelectControl, {
						label: __( 'Label', 'fxt-core' ),
						value: kind,
						options: Object.keys( kinds ).map( ( k ) => ( { value: k, label: kinds[ k ] } ) ),
						onChange: setKind,
					} ),
					( isCollapsed( value ) && ! isActive )
						? el( TextControl, {
							label: __( 'Text (optional)', 'fxt-core' ),
							help: __( 'Empty: the label name. Or select text first to turn it into a label.', 'fxt-core' ),
							value: text,
							onChange: setText,
						} )
						: null,
					el( TextControl, {
						label: __( 'Link (optional)', 'fxt-core' ),
						help: __( 'Where a click on the label goes, e.g. the evidence page or the official register.', 'fxt-core' ),
						type: 'url',
						value: url,
						onChange: setUrl,
					} ),
					url.trim() ? el( ToggleControl, { label: __( 'Open in a new tab', 'fxt-core' ), checked: newTab, onChange: setNewTab } ) : null,
					el(
						'div',
						{ style: { display: 'flex', gap: '8px', justifyContent: 'space-between' } },
						el( Button, { variant: 'primary', onClick: apply }, isActive ? __( 'Update', 'fxt-core' ) : __( 'Insert', 'fxt-core' ) ),
						isActive
							? el( Button, { variant: 'tertiary', isDestructive: true, onClick: () => { onChange( removeFormat( value, NAME ) ); setOpen( false ); } }, __( 'Remove label', 'fxt-core' ) )
							: null
					)
				)
			)
		);
	}

	registerFormatType( NAME, {
		title: __( 'Source label', 'fxt-core' ),
		tagName: 'a',
		className: 'fxt-source-label',
		attributes: { kind: 'data-kind', url: 'href', target: 'target', rel: 'rel' },
		edit: LabelEdit,
	} );
}( window.wp ) );
