/**
 * Editor UI for the dynamic blocks (no build step).
 *
 * Every block is rendered by PHP; the editor shows a live server-side preview
 * and exposes the block attributes in the sidebar. Brokers and countries are
 * picked from the site's own data (Broker Reviews, Countries), never typed in.
 */
( function ( wp ) {
	'use strict';

	const el = wp.element.createElement;
	const { __ } = wp.i18n;
	const { registerBlockType } = wp.blocks;
	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const { PanelBody, RangeControl, SelectControl, TextControl, ToggleControl, CheckboxControl, Button, Disabled, Spinner } = wp.components;
	const ServerSideRender = wp.serverSideRender;
	const { useSelect } = wp.data;
	const config = window.fxtCoreBlocks || {};

	/* ---------------------------------------------------------------------
	 * Data hooks
	 * ------------------------------------------------------------------ */

	function useBrokers() {
		return useSelect( ( select ) => select( 'core' ).getEntityRecords( 'postType', 'fxt_broker', { per_page: -1, status: 'publish', orderby: 'title', order: 'asc', _fields: 'id,slug,title' } ), [] );
	}

	function useCountries() {
		return useSelect( ( select ) => select( 'core' ).getEntityRecords( 'taxonomy', 'fxt_market', { per_page: -1, orderby: 'name', order: 'asc', _fields: 'id,name,meta' } ), [] );
	}

	const titleOf = ( record ) => ( record.title && ( record.title.raw || record.title.rendered ) ) || record.slug || String( record.id );

	/* ---------------------------------------------------------------------
	 * Ordered broker picker: add, remove, move up/down.
	 * value: array of ids (key "id") or comma string of slugs (key "slug").
	 * ------------------------------------------------------------------ */

	function BrokerPicker( props ) {
		const brokers = useBrokers();
		if ( ! brokers ) {
			return el( Spinner );
		}
		const key = props.valueKey;
		const list = 'slug' === key
			? String( props.value || '' ).split( ',' ).map( ( s ) => s.trim() ).filter( Boolean )
			: ( props.value || [] ).slice();
		const byKey = {};
		brokers.forEach( ( b ) => { byKey[ b[ key ] ] = b; } );
		const emit = ( next ) => props.onChange( 'slug' === key ? next.join( ',' ) : next );
		const move = ( i, d ) => {
			const next = list.slice();
			const j = i + d;
			if ( j < 0 || j >= next.length ) {
				return;
			}
			[ next[ i ], next[ j ] ] = [ next[ j ], next[ i ] ];
			emit( next );
		};
		const available = brokers.filter( ( b ) => list.indexOf( b[ key ] ) === -1 );
		const full = props.max && list.length >= props.max;

		return el(
			'div',
			{ className: 'fxt-picker' },
			el( 'p', { className: 'components-base-control__label' }, props.label ),
			props.help ? el( 'p', { className: 'components-base-control__help' }, props.help ) : null,
			list.length
				? el(
					'ol',
					{ className: 'fxt-picker__list' },
					list.map( ( value, i ) => el(
						'li',
						{ key: value, className: 'fxt-picker__item' },
						el( 'span', { className: 'fxt-picker__name' }, byKey[ value ] ? titleOf( byKey[ value ] ) : value + ' ' + __( '(not found)', 'fxt-core' ) ),
						el( Button, { icon: 'arrow-up-alt2', label: __( 'Move up', 'fxt-core' ), size: 'small', disabled: 0 === i, onClick: () => move( i, -1 ) } ),
						el( Button, { icon: 'arrow-down-alt2', label: __( 'Move down', 'fxt-core' ), size: 'small', disabled: i === list.length - 1, onClick: () => move( i, 1 ) } ),
						el( Button, { icon: 'no-alt', label: __( 'Remove', 'fxt-core' ), size: 'small', isDestructive: true, onClick: () => emit( list.filter( ( v ) => v !== value ) ) } )
					) )
				)
				: el( 'p', { className: 'fxt-picker__empty' }, props.emptyText ),
			full
				? null
				: el( SelectControl, {
					label: __( 'Add a broker', 'fxt-core' ),
					value: '',
					options: [ { value: '', label: __( 'Choose…', 'fxt-core' ) } ].concat( available.map( ( b ) => ( { value: String( b[ key ] ), label: titleOf( b ) } ) ) ),
					onChange: ( v ) => {
						if ( v ) {
							emit( list.concat( 'id' === key ? parseInt( v, 10 ) : v ) );
						}
					},
				} )
		);
	}

	/* ---------------------------------------------------------------------
	 * Checkbox list for compare rows / groups (empty value = all).
	 * ------------------------------------------------------------------ */

	function KeyChecklist( props ) {
		const options = props.options || {};
		const all = Object.keys( options );
		const current = props.value && props.value.length ? props.value : all;
		return el(
			'div',
			{ className: 'fxt-checklist' },
			el( 'p', { className: 'components-base-control__label' }, props.label ),
			all.map( ( k ) => el( CheckboxControl, {
				key: k,
				label: options[ k ],
				checked: current.indexOf( k ) !== -1,
				onChange: ( on ) => {
					const next = on ? all.filter( ( x ) => x === k || current.indexOf( x ) !== -1 ) : current.filter( ( x ) => x !== k );
					props.onChange( next.length === all.length ? [] : next );
				},
			} ) )
		);
	}

	function CountrySelect( props ) {
		const countries = useCountries();
		if ( ! countries ) {
			return el( Spinner );
		}
		return el( SelectControl, {
			label: props.label,
			value: props.value,
			options: [ { value: '', label: __( 'Choose…', 'fxt-core' ) } ].concat( countries.map( ( c ) => ( { value: ( c.meta && c.meta.fxt_iso ) || '', label: c.name } ) ).filter( ( o ) => o.value ) ),
			onChange: props.onChange,
		} );
	}

	function BrokerSelect( props ) {
		const brokers = useBrokers();
		if ( ! brokers ) {
			return el( Spinner );
		}
		return el( SelectControl, {
			label: props.label,
			value: props.value,
			options: [ { value: '', label: __( 'Choose…', 'fxt-core' ) } ].concat( brokers.map( ( b ) => ( { value: b.slug, label: titleOf( b ) } ) ) ),
			onChange: props.onChange,
		} );
	}

	/* ---------------------------------------------------------------------
	 * Sidebar controls per block
	 * ------------------------------------------------------------------ */

	const compareOptions = config.compare || { rows: {}, groups: {} };

	const controls = {
		'fxt/country-brokers': ( a, set ) => [
			el( RangeControl, { key: 'limit', label: __( 'Brokers shown', 'fxt-core' ), min: 1, max: 8, value: a.limit, onChange: ( v ) => set( { limit: v } ) } ),
			el( SelectControl, {
				key: 'order',
				label: __( 'Order', 'fxt-core' ),
				value: a.orderBy,
				options: [
					{ value: 'score', label: __( 'Highest research score first', 'fxt-core' ) },
					{ value: 'manual', label: __( 'Manual ("Order" field of each Broker Review)', 'fxt-core' ) },
				],
				help: __( 'Only brokers marked "Available" for the visitor\'s country are listed.', 'fxt-core' ),
				onChange: ( v ) => set( { orderBy: v } ),
			} ),
		],
		'fxt/broker-cards': ( a, set ) => [
			el( BrokerPicker, {
				key: 'brokers',
				valueKey: 'id',
				label: __( 'Brokers to show', 'fxt-core' ),
				help: __( 'Pick brokers and set their order. Leave empty to show the highest research scores automatically.', 'fxt-core' ),
				emptyText: __( 'Automatic: highest research scores.', 'fxt-core' ),
				max: 12,
				value: a.brokers,
				onChange: ( v ) => set( { brokers: v } ),
			} ),
			a.brokers && a.brokers.length ? null : el( RangeControl, { key: 'count', label: __( 'Number of cards', 'fxt-core' ), min: 1, max: 12, value: a.count, onChange: ( v ) => set( { count: v } ) } ),
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
			el( BrokerPicker, {
				key: 'brokers',
				valueKey: 'slug',
				label: __( 'Brokers compared by default', 'fxt-core' ),
				help: __( 'Visitors can still change the selection on the page.', 'fxt-core' ),
				emptyText: __( 'No default brokers: visitors start with empty slots.', 'fxt-core' ),
				max: a.slots,
				value: a.brokers,
				onChange: ( v ) => set( { brokers: v } ),
			} ),
			'preview' === a.mode
				? el( KeyChecklist, { key: 'rows', label: __( 'Rows', 'fxt-core' ), options: compareOptions.rows, value: a.rows, onChange: ( v ) => set( { rows: v } ) } )
				: el( KeyChecklist, { key: 'groups', label: __( 'Row groups', 'fxt-core' ), options: compareOptions.groups, value: a.groups, onChange: ( v ) => set( { groups: v } ) } ),
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
				help: __( 'Categories and weights are edited in Settings > FX Trading Today.', 'fxt-core' ),
				onChange: ( v ) => set( { variant: v } ),
			} ),
		],
		'fxt/evidence-list': ( a, set ) => [
			el( SelectControl, {
				key: 'scope',
				label: __( 'Evidence shown', 'fxt-core' ),
				value: a.scope,
				options: [
					{ value: 'all', label: __( 'All countries (with a country filter)', 'fxt-core' ) },
					{ value: 'country', label: __( 'Only the visitor\'s selected country', 'fxt-core' ) },
				],
				onChange: ( v ) => set( { scope: v } ),
			} ),
			el( TextControl, { key: 'pt', label: __( 'Heading: published evidence', 'fxt-core' ), placeholder: __( 'Published evidence', 'fxt-core' ), value: a.publishedTitle, onChange: ( v ) => set( { publishedTitle: v } ) } ),
			el( ToggleControl, { key: 'sp', label: __( 'Show tests still in progress', 'fxt-core' ), checked: !! a.showPending, onChange: ( v ) => set( { showPending: v } ) } ),
			a.showPending ? el( TextControl, { key: 'pd', label: __( 'Heading: tests in progress', 'fxt-core' ), placeholder: __( 'Testing in progress', 'fxt-core' ), value: a.pendingTitle, onChange: ( v ) => set( { pendingTitle: v } ) } ) : null,
		],
		'fxt/post-byline': ( a, set ) => [
			el( ToggleControl, { key: 'r', label: __( 'Show author role', 'fxt-core' ), checked: !! a.showRole, onChange: ( v ) => set( { showRole: v } ) } ),
			el( TextControl, { key: 'd', label: __( 'Text before the date', 'fxt-core' ), placeholder: __( 'e.g. Updated', 'fxt-core' ), value: a.dateLabel, onChange: ( v ) => set( { dateLabel: v } ) } ),
			el( SelectControl, {
				key: 't',
				label: __( 'Reading time', 'fxt-core' ),
				value: a.readTime,
				options: [
					{ value: 'long', label: __( '"11 min read"', 'fxt-core' ) },
					{ value: 'short', label: __( '"11 min"', 'fxt-core' ) },
					{ value: 'none', label: __( 'Hidden', 'fxt-core' ) },
				],
				onChange: ( v ) => set( { readTime: v } ),
			} ),
			el( 'p', { key: 'h', className: 'components-base-control__help' }, __( 'Author name, role and photo are edited in Users > Profile.', 'fxt-core' ) ),
		],
		'fxt/evidence-snapshot': ( a, set ) => [
			el( BrokerSelect, { key: 'b', label: __( 'Broker', 'fxt-core' ), value: a.broker, onChange: ( v ) => set( { broker: v } ) } ),
			el( CountrySelect, { key: 'c', label: __( 'Country', 'fxt-core' ), value: a.country, onChange: ( v ) => set( { country: v } ) } ),
			el( 'p', { key: 'h', className: 'components-base-control__help' }, __( 'Results come from the Test Evidence record for this broker and country.', 'fxt-core' ) ),
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
		'fxt/evidence-snapshot',
		'fxt/post-byline',
	];

	names.forEach( ( name ) => {
		registerBlockType( name, {
			edit( props ) {
				const blockProps = useBlockProps( { className: 'fxt-block-preview' } );
				const postId = useSelect( ( select ) => select( 'core/editor' ) && select( 'core/editor' ).getCurrentPostId(), [] );
				const factory = controls[ name ];
				const hint = ( 'fxt/broker-data' === name || 'fxt/evidence-data' === name ) && props.isSelected
					? el( 'p', { className: 'fxt-block-hint' }, __( 'This section shows structured data. Edit the values in the data panels below the content (sidebar: "Edit … data").', 'fxt-core' ) )
					: null;
				return el(
					'div',
					blockProps,
					hint,
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
