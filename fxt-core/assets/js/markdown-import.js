/**
 * Markdown import panel (1.4.7) for Broker Reviews and Test Evidence.
 *
 * Sidebar panel (Post tab) > "Import Markdown…" opens a dialog:
 *   1. Upload a .md file or paste Markdown; choose Append or Replace.
 *   2. Preview: the converted blocks with the site styles, plus notes
 *      (images skipped, H1 -> H2...).
 *   3. Import. Replace asks for an explicit confirmation first.
 * The server converts Markdown into a tree of core blocks (REST
 * fxt/v1/markdown); the blocks are created here with wp.blocks.createBlock,
 * so they are native, valid and editable. Only the post content changes;
 * nothing is saved until the editor saves the post. No build step.
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.plugins || ! wp.blocks || ! wp.data ) {
		return;
	}

	const el = wp.element.createElement;
	const { useState, Fragment } = wp.element;
	const { __, sprintf, _n } = wp.i18n;
	const { Button, Modal, TextareaControl, RadioControl, Notice, CheckboxControl, Spinner } = wp.components;
	const { useSelect, useDispatch } = wp.data;
	const { BlockPreview } = wp.blockEditor;
	const Panel = ( wp.editor && wp.editor.PluginDocumentSettingPanel ) || ( wp.editPost && wp.editPost.PluginDocumentSettingPanel );
	const cfg = window.fxtMarkdownImport || { templates: [], maxBytes: 1048576 };


	const NAMES = {
		'core/heading': [ 'heading', 'headings' ],
		'core/paragraph': [ 'paragraph', 'paragraphs' ],
		'core/list': [ 'list', 'lists' ],
		'core/quote': [ 'quote', 'quotes' ],
		'core/table': [ 'table', 'tables' ],
		'core/separator': [ 'separator', 'separators' ],
		'core/image': [ 'image', 'images' ],
		'core/code': [ 'code block', 'code blocks' ],
		'fxt/affiliate-cta': [ 'affiliate button', 'affiliate buttons' ],
	};

	// Tree from the server -> editor blocks (unknown block types are skipped).
	function toBlocks( nodes ) {
		return ( nodes || [] ).filter( ( n ) => wp.blocks.getBlockType( n.name ) ).map( ( n ) =>
			wp.blocks.createBlock( n.name, Object.assign( {}, n.attributes || {} ), toBlocks( n.innerBlocks ) )
		);
	}

	// The editor's empty starting paragraph does not count as content.
	function isEmpty( blocks ) {
		return ! blocks.length || ( 1 === blocks.length && 'core/paragraph' === blocks[ 0 ].name && ! String( blocks[ 0 ].attributes.content || '' ).trim() );
	}

	function summary( counts ) {
		return Object.keys( NAMES ).filter( ( k ) => counts[ k ] && 'core/list-item' !== k ).map( ( k ) => counts[ k ] + ' ' + ( 1 === counts[ k ] ? NAMES[ k ][ 0 ] : NAMES[ k ][ 1 ] ) ).join( ', ' );
	}

	function ImportDialog( { onClose } ) {
		const postId = useSelect( ( s ) => s( 'core/editor' ).getCurrentPostId(), [] );
		const current = useSelect( ( s ) => s( 'core/block-editor' ).getBlocks(), [] );
		const { replaceBlocks, insertBlocks } = useDispatch( 'core/block-editor' );
		const { createNotice } = useDispatch( 'core/notices' );
		const [ step, setStep ] = useState( 'input' );
		const [ text, setText ] = useState( '' );
		const [ fileName, setFileName ] = useState( '' );
		const [ mode, setMode ] = useState( 'append' );
		const [ busy, setBusy ] = useState( false );
		const [ error, setError ] = useState( '' );
		const [ result, setResult ] = useState( null );
		const [ agree, setAgree ] = useState( false );
		const existing = isEmpty( current ) ? 0 : current.length;
		const dataBlocks = current.filter( ( b ) => 0 !== b.name.indexOf( 'core/' ) ).length;

		function readFile( event ) {
			const file = event.target.files && event.target.files[ 0 ];
			setError( '' );
			if ( ! file ) {
				return;
			}
			if ( ! /\.(md|markdown|txt)$/i.test( file.name ) ) {
				setError( __( 'Choose a Markdown file (.md, .markdown or .txt).', 'fxt-core' ) );
				event.target.value = '';
				return;
			}
			if ( file.size > cfg.maxBytes ) {
				setError( __( 'The file is larger than 1 MB. Split it into smaller files.', 'fxt-core' ) );
				event.target.value = '';
				return;
			}
			const reader = new window.FileReader();
			reader.onload = () => {
				setText( String( reader.result || '' ) );
				setFileName( file.name );
			};
			reader.onerror = () => setError( __( 'The file could not be read.', 'fxt-core' ) );
			reader.readAsText( file, 'UTF-8' );
		}

		function preview() {
			if ( ! text.trim() ) {
				setError( __( 'Upload a .md file or paste Markdown first.', 'fxt-core' ) );
				return;
			}
			setBusy( true );
			setError( '' );
			wp.apiFetch( { path: '/fxt/v1/markdown', method: 'POST', data: { post_id: postId, markdown: text } } )
				.then( ( res ) => {
					const blocks = toBlocks( res.blocks );
					if ( ! blocks.length ) {
						setError( __( 'Nothing to import: the Markdown has no content.', 'fxt-core' ) );
						return;
					}
					setResult( { blocks, warnings: res.warnings || [], counts: res.counts || {} } );
					setStep( 'preview' );
				} )
				.catch( ( err ) => setError( ( err && err.message ) || __( 'The Markdown could not be converted.', 'fxt-core' ) ) )
				.finally( () => setBusy( false ) );
		}

		function apply() {
			// Fresh copies, so the preview's blocks are never reused.
			const blocks = result.blocks.map( ( b ) => wp.blocks.cloneBlock( b ) );
			const ids = current.map( ( b ) => b.clientId );
			if ( 'replace' === mode || isEmpty( current ) ) {
				if ( ids.length ) {
					replaceBlocks( ids, blocks );
				} else {
					insertBlocks( blocks );
				}
			} else {
				insertBlocks( blocks );
			}
			createNotice(
				'success',
				sprintf(
					/* translators: %d: number of blocks */
					'replace' === mode
						? _n( 'Markdown imported: %d block replaced the content. Check it, then save the post.', 'Markdown imported: %d blocks replaced the content. Check them, then save the post.', blocks.length, 'fxt-core' )
						: _n( 'Markdown imported: %d block added at the end. Check it, then save the post.', 'Markdown imported: %d blocks added at the end. Check them, then save the post.', blocks.length, 'fxt-core' ),
					blocks.length
				),
				{ type: 'snackbar', id: 'fxt-markdown-import' }
			);
			onClose();
		}

		let body;
		if ( 'input' === step ) {
			body = el(
				Fragment,
				null,
				el( 'p', { className: 'fxt-md__intro' }, __( 'The Markdown becomes ordinary blocks (headings, paragraphs, lists, quotes, tables, separators, images) that you can edit afterwards. Only the post content changes: the data boxes below the editor are not touched.', 'fxt-core' ) ),
				el(
					'div',
					{ className: 'fxt-md__file' },
					el( 'label', { htmlFor: 'fxt-md-file', className: 'fxt-md__label' }, __( 'Markdown file', 'fxt-core' ) ),
					el( 'input', { id: 'fxt-md-file', type: 'file', accept: '.md,.markdown,.txt,text/markdown,text/plain', onChange: readFile } ),
					fileName ? el( 'p', { className: 'fxt-md__hint' }, sprintf( /* translators: %s: file name */ __( 'Loaded: %s. You can edit the text below.', 'fxt-core' ), fileName ) ) : null
				),
				el( TextareaControl, {
					label: __( 'Or paste Markdown', 'fxt-core' ),
					value: text,
					rows: 12,
					className: 'fxt-md__text',
					onChange: ( v ) => {
						setText( v );
						setError( '' );
					},
				} ),
				el( RadioControl, {
					label: __( 'Import', 'fxt-core' ),
					selected: mode,
					options: [
						{ value: 'append', label: __( 'Append to existing content (added at the end)', 'fxt-core' ) },
						{ value: 'replace', label: __( 'Replace existing content', 'fxt-core' ) },
					],
					onChange: setMode,
				} ),
				error ? el( Notice, { status: 'error', isDismissible: false }, error ) : null,
				el(
					'div',
					{ className: 'fxt-md__actions' },
					el( Button, { variant: 'tertiary', onClick: onClose }, __( 'Cancel', 'fxt-core' ) ),
					el( Button, { variant: 'primary', onClick: preview, isBusy: busy, disabled: busy }, busy ? __( 'Converting…', 'fxt-core' ) : __( 'Preview', 'fxt-core' ) )
				)
			);
		} else if ( 'preview' === step ) {
			body = el(
				Fragment,
				null,
				el(
					'p',
					{ className: 'fxt-md__summary' },
					sprintf(
						/* translators: 1: number of blocks, 2: list such as "3 headings, 5 paragraphs" */
						__( '%1$d blocks: %2$s.', 'fxt-core' ),
						result.blocks.length,
						summary( result.counts )
					)
				),
				result.warnings.length
					? el( Notice, { status: 'warning', isDismissible: false, className: 'fxt-md__warnings' }, el( 'ul', null, result.warnings.map( ( w, i ) => el( 'li', { key: i }, w ) ) ) )
					: null,
				el( 'div', { className: 'fxt-md__preview', 'aria-label': __( 'Preview of the imported content', 'fxt-core' ) }, el( BlockPreview, { blocks: result.blocks, viewportWidth: 900 } ) ),
				el(
					'p',
					{ className: 'fxt-md__where' },
					'replace' === mode
						? sprintf( /* translators: %d: number of blocks */ _n( 'Replace: the current content (%d block) will be removed.', 'Replace: the current content (%d blocks) will be removed.', existing, 'fxt-core' ), existing )
						: ( existing ? sprintf( /* translators: %d: number of blocks */ _n( 'Append: added after the current content (%d block).', 'Append: added after the current content (%d blocks).', existing, 'fxt-core' ), existing ) : __( 'The post has no content yet: the blocks become its content.', 'fxt-core' ) )
				),
				el(
					'div',
					{ className: 'fxt-md__actions' },
					el( Button, { variant: 'tertiary', onClick: () => setStep( 'input' ) }, __( 'Back', 'fxt-core' ) ),
					'replace' === mode && existing
						? el( Button, { variant: 'primary', isDestructive: true, onClick: () => { setAgree( false ); setStep( 'confirm' ); } }, __( 'Replace content…', 'fxt-core' ) )
						: el( Button, { variant: 'primary', onClick: apply }, __( 'Import', 'fxt-core' ) )
				)
			);
		} else {
			body = el(
				Fragment,
				null,
				el( 'p', null, sprintf( /* translators: %d: number of blocks */ _n( 'Replace the whole content of this post (%d block) with the imported Markdown?', 'Replace the whole content of this post (%d blocks) with the imported Markdown?', existing, 'fxt-core' ), existing ) ),
				dataBlocks
					? el( 'p', null, sprintf( /* translators: %d: number of blocks */ _n( '%d of these is a site block (for example Broker data or Evidence data) that displays structured data. The block is removed from the content; the data itself stays in the boxes below the editor.', '%d of these are site blocks (for example Broker data or Evidence data) that display structured data. The blocks are removed from the content; the data itself stays in the boxes below the editor.', dataBlocks, 'fxt-core' ), dataBlocks ) )
					: null,
				el( 'p', null, __( 'Nothing is saved until you save the post, and Undo restores the previous content.', 'fxt-core' ) ),
				el( CheckboxControl, { label: __( 'I understand that the current content will be replaced.', 'fxt-core' ), checked: agree, onChange: setAgree } ),
				el(
					'div',
					{ className: 'fxt-md__actions' },
					el( Button, { variant: 'tertiary', onClick: () => setStep( 'preview' ) }, __( 'Back', 'fxt-core' ) ),
					el( Button, { variant: 'primary', isDestructive: true, disabled: ! agree, onClick: apply }, __( 'Replace content', 'fxt-core' ) )
				)
			);
		}

		return el(
			Modal,
			{
				title: 'confirm' === step ? __( 'Replace the content?', 'fxt-core' ) : ( 'preview' === step ? __( 'Preview the import', 'fxt-core' ) : __( 'Import Markdown', 'fxt-core' ) ),
				onRequestClose: onClose,
				className: 'fxt-md fxt-md--' + step,
				size: 'confirm' === step ? 'medium' : 'large',
				shouldCloseOnClickOutside: false,
			},
			busy && 'input' !== step ? el( Spinner ) : null,
			body
		);
	}

	// Several ways in, one dialog: header button (Markdown icon), the ⋮ menu,
	// the sidebar panel, and "?fxt-md-import=1" in the editor URL (row action
	// "Import Markdown" on the Broker Reviews / Test Evidence lists).
	const OPEN = 'fxt-md-open';
	const openDialog = () => window.dispatchEvent( new window.CustomEvent( OPEN ) );
	const { useEffect } = wp.element;
	const MoreItem = ( wp.editor && wp.editor.PluginMoreMenuItem ) || ( wp.editPost && wp.editPost.PluginMoreMenuItem );
	const Sidebar = ( wp.editor && wp.editor.PluginSidebar ) || ( wp.editPost && wp.editPost.PluginSidebar );
	const SidebarItem = ( wp.editor && wp.editor.PluginSidebarMoreMenuItem ) || ( wp.editPost && wp.editPost.PluginSidebarMoreMenuItem );
	const ICON = el(
		'svg',
		{ xmlns: 'http://www.w3.org/2000/svg', viewBox: '0 0 24 24', width: 24, height: 24, 'aria-hidden': true },
		el( 'path', { d: 'M3 6.5A1.5 1.5 0 0 1 4.5 5h15A1.5 1.5 0 0 1 21 6.5v11a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 17.5v-11Zm1.5 0v11h15v-11h-15ZM6 15.5v-7h1.6l1.9 2.4 1.9-2.4H13v7h-1.6v-4.6l-1.9 2.4-1.9-2.4v4.6H6Zm10.2 0-2.7-3.2h1.8V8.5h1.8v3.8h1.8l-2.7 3.2Z' } )
	);

	function Body() {
		return el(
			Fragment,
			null,
			el( 'p', null, __( 'Add content from a Markdown (.md) file as editable blocks.', 'fxt-core' ) ),
			el( Button, { variant: 'primary', onClick: openDialog, className: 'fxt-md-open' }, __( 'Import Markdown…', 'fxt-core' ) ),
			el( 'p', { className: 'fxt-md-panel__label' }, __( 'Templates', 'fxt-core' ) ),
			el(
				'ul',
				{ className: 'fxt-md-panel__templates' },
				( cfg.templates || [] ).map( ( t ) => el( 'li', { key: t.key }, el( 'a', { href: t.url, download: t.file }, t.file ) ) )
			),
			cfg.helpUrl ? el( 'p', null, el( 'a', { href: cfg.helpUrl }, __( 'How Markdown becomes blocks', 'fxt-core' ) ) ) : null
		);
	}

	function MarkdownImport() {
		const [ open, setOpen ] = useState( false );
		useEffect( () => {
			const show = () => setOpen( true );
			window.addEventListener( OPEN, show );
			// Opened from the list row action: open once the editor is ready.
			if ( /[?&]fxt-md-import=1(&|$)/.test( window.location.search ) ) {
				const wait = window.setInterval( () => {
					if ( wp.data.select( 'core/editor' ).getCurrentPostId() && wp.data.select( 'core/block-editor' ).getBlocks().length ) {
						window.clearInterval( wait );
						setOpen( true );
					}
				}, 300 );
				window.setTimeout( () => window.clearInterval( wait ), 20000 );
			}
			return () => window.removeEventListener( OPEN, show );
		}, [] );
		return el(
			Fragment,
			null,
			Panel ? el( Panel, { name: 'fxt-markdown-import', title: __( 'Markdown import', 'fxt-core' ), className: 'fxt-md-panel' }, el( Body ) ) : null,
			Sidebar ? el( Sidebar, { name: 'fxt-markdown-import-sidebar', title: __( 'Markdown import', 'fxt-core' ), icon: ICON, className: 'fxt-md-sidebar' }, el( 'div', { className: 'fxt-md-sidebar__body' }, el( Body ) ) ) : null,
			SidebarItem ? el( SidebarItem, { target: 'fxt-markdown-import-sidebar', icon: ICON }, __( 'Markdown import', 'fxt-core' ) ) : null,
			MoreItem ? el( MoreItem, { icon: ICON, onClick: openDialog }, __( 'Import Markdown…', 'fxt-core' ) ) : null,
			open ? el( ImportDialog, { onClose: () => setOpen( false ) } ) : null
		);
	}

	wp.plugins.registerPlugin( 'fxt-markdown-import', { render: MarkdownImport, icon: ICON } );

	// Show the Markdown button in the editor header (pinned) the first time.
	wp.domReady && wp.domReady( () => {
		try {
			const key = 'fxt-md-pinned';
			if ( ! window.localStorage.getItem( key ) && wp.data.dispatch( 'core/interface' ) ) {
				wp.data.dispatch( 'core/interface' ).pinItem( 'core', 'fxt-markdown-import/fxt-markdown-import-sidebar' );
				wp.data.dispatch( 'core/interface' ).pinItem( 'core/edit-post', 'fxt-markdown-import/fxt-markdown-import-sidebar' );
				window.localStorage.setItem( key, '1' );
			}
		} catch ( e ) {}
	} );
}( window.wp ) );
