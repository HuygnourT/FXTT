/**
 * Editor UI for the dynamic blocks (no build step).
 *
 * Every block is rendered by PHP; the editor shows a live server-side preview
 * and exposes the block attributes in the sidebar.
 */
( function ( wp ) {
	'use strict';

	const el = wp.element.createElement;
	const { __ } = wp.i18n;
	const { registerBlockType } = wp.blocks;
	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const { PanelBody, RangeControl, SelectControl, TextControl, ToggleControl, Disabled } = wp.components;
	const ServerSideRender = wp.serverSideRender;
	const { useSelect } = wp.data;

	/**
	 * Sidebar controls per block: [ attribute, control factory ].
	 */
	const controls = {
		'fxt/country-brokers': ( a, set ) => [
			el( RangeControl, { key: 'limit', label: __( 'Brokers shown', 'fxt-core' ), min: 1, max: 8, value: a.limit, onChange: ( v ) => set( { limit: v } ) } ),
		],
		'fxt/broker-cards': ( a, set ) => [
			el( RangeControl, { key: 'count', label: __( 'Number of cards', 'fxt-core' ), min: 1, max: 12, value: a.count, onChange: ( v ) => set( { count: v } ) } ),
		],
		'fxt/broker-compare': ( a, set ) => [
			el( SelectControl, {
				key: 'mode',
				label: __( 'Layout', 'fxt-core' ),
				value: a.mode,
				options: [
					{ value: 'preview', label: __( 'Preview (key rows)', 'fxt-core' ) },
					{ value: 'full', label: __( 'Full comparison', 'fxt-core' ) },
				],
				onChange: ( v ) => set( { mode: v } ),
			} ),
			el( RangeControl, { key: 'slots', label: __( 'Broker slots', 'fxt-core' ), min: 2, max: 4, value: a.slots, onChange: ( v ) => set( { slots: v } ) } ),
			el( TextControl, {
				key: 'brokers',
				label: __( 'Default brokers', 'fxt-core' ),
				help: __( 'Broker slugs separated by commas. Visitors can change the selection.', 'fxt-core' ),
				value: a.brokers,
				onChange: ( v ) => set( { brokers: v } ),
			} ),
			el( ToggleControl, {
				key: 'sync',
				label: __( 'Keep selection in the page URL', 'fxt-core' ),
				help: __( 'Use on the main comparison page so a comparison can be shared.', 'fxt-core' ),
				checked: !! a.syncUrl,
				onChange: ( v ) => set( { syncUrl: v } ),
			} ),
		],
		'fxt/broker-data': ( a, set ) => [
			el( SelectControl, {
				key: 'section',
				label: __( 'Section', 'fxt-core' ),
				value: a.section,
				options: [
					{ value: 'quick-facts', label: __( 'Quick facts', 'fxt-core' ) },
					{ value: 'regulation', label: __( 'Regulation and entities', 'fxt-core' ) },
					{ value: 'costs', label: __( 'Trading costs', 'fxt-core' ) },
					{ value: 'platforms', label: __( 'Platforms', 'fxt-core' ) },
					{ value: 'payments', label: __( 'Deposits and withdrawals', 'fxt-core' ) },
					{ value: 'accounts', label: __( 'Account types', 'fxt-core' ) },
					{ value: 'evidence', label: __( 'Test evidence', 'fxt-core' ) },
					{ value: 'final-score', label: __( 'Final score', 'fxt-core' ) },
				],
				onChange: ( v ) => set( { section: v } ),
			} ),
			el( TextControl, {
				key: 'broker',
				type: 'number',
				label: __( 'Broker ID (optional)', 'fxt-core' ),
				help: __( 'Leave at 0 to use the broker being edited.', 'fxt-core' ),
				value: a.brokerId,
				onChange: ( v ) => set( { brokerId: parseInt( v, 10 ) || 0 } ),
			} ),
		],
		'fxt/evidence-data': ( a, set ) => [
			el( SelectControl, {
				key: 'section',
				label: __( 'Section', 'fxt-core' ),
				value: a.section,
				options: [
					{ value: 'summary', label: __( 'Summary', 'fxt-core' ) },
					{ value: 'timeline', label: __( 'Timeline', 'fxt-core' ) },
					{ value: 'deposits', label: __( 'Deposit tests', 'fxt-core' ) },
					{ value: 'withdrawals', label: __( 'Withdrawal tests', 'fxt-core' ) },
				],
				onChange: ( v ) => set( { section: v } ),
			} ),
		],
		'fxt/score-weights': ( a, set ) => [
			el( SelectControl, {
				key: 'variant',
				label: __( 'Display', 'fxt-core' ),
				value: a.variant,
				options: [
					{ value: 'list', label: __( 'Bars', 'fxt-core' ) },
					{ value: 'table', label: __( 'Table', 'fxt-core' ) },
				],
				onChange: ( v ) => set( { variant: v } ),
			} ),
		],
	};

	const names = [
		'fxt/country-brokers',
		'fxt/broker-cards',
		'fxt/broker-compare',
		'fxt/broker-data',
		'fxt/evidence-data',
		'fxt/score-weights',
		'fxt/broker-directory',
		'fxt/evidence-list',
	];

	names.forEach( ( name ) => {
		registerBlockType( name, {
			edit( props ) {
				const blockProps = useBlockProps( { className: 'fxt-block-preview' } );
				const postId = useSelect( ( select ) => select( 'core/editor' ) && select( 'core/editor' ).getCurrentPostId(), [] );
				const factory = controls[ name ];
				return el(
					'div',
					blockProps,
					factory
						? el( InspectorControls, null, el( PanelBody, { title: __( 'Settings', 'fxt-core' ) }, factory( props.attributes, props.setAttributes ) ) )
						: null,
					el(
						Disabled,
						null,
						el( ServerSideRender, {
							block: name,
							attributes: props.attributes,
							urlQueryArgs: postId ? { post_id: postId } : {},
						} )
					)
				);
			},
			save: () => null,
		} );
	} );
}( window.wp ) );
