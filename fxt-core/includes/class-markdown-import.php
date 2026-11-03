<?php
/**
 * Markdown import for Broker Reviews and Test Evidence (1.4.7).
 *
 * - Block editor panel "Markdown import" (assets/js/markdown-import.js):
 *   upload a .md file or paste Markdown, preview, then append to or replace
 *   the post content (replacing asks for confirmation).
 * - REST route POST fxt/v1/markdown: converts Markdown into a tree of core
 *   blocks (class Markdown). Users who can edit the post only.
 * - Templates: broker-review-template.md and test-evidence-template.md,
 *   downloadable from the panel and from Broker Reviews > Markdown import.
 *
 * Only post_content changes, and only in the editor: Broker data, Test
 * Evidence data and every other meta value or post setting are not touched.
 *
 * @package FXT\Core
 */

namespace FXT\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Markdown import integration.
 */
class Markdown_Import {

	const PAGE = 'fxt-markdown-import';

	/** @var int Remote image checks left in this request. */
	private static $remote_checks = 25;

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'editor_assets' ) );
		add_action( 'admin_post_fxt_markdown_template', array( __CLASS__, 'download_template' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
	}

	/**
	 * Post types with Markdown import.
	 *
	 * @return string[]
	 */
	public static function post_types() {
		return array( Content_Types::BROKER, Content_Types::EVIDENCE );
	}

	/**
	 * Templates: key => { file, title }.
	 *
	 * @return array
	 */
	public static function templates() {
		return array(
			'broker-review' => array(
				'file'  => 'broker-review-template.md',
				'title' => __( 'Broker Review template', 'fxt-core' ),
				'type'  => Content_Types::BROKER,
			),
			'test-evidence' => array(
				'file'  => 'test-evidence-template.md',
				'title' => __( 'Test Evidence template', 'fxt-core' ),
				'type'  => Content_Types::EVIDENCE,
			),
		);
	}

	/**
	 * Download link of a template.
	 *
	 * @param string $key Template key.
	 * @return string
	 */
	public static function template_url( $key ) {
		return wp_nonce_url( add_query_arg( array( 'action' => 'fxt_markdown_template', 'template' => $key ), admin_url( 'admin-post.php' ) ), 'fxt_markdown_template' );
	}

	/**
	 * Send a template file as a download.
	 */
	public static function download_template() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You are not allowed to download this file.', 'fxt-core' ), 403 );
		}
		check_admin_referer( 'fxt_markdown_template' );
		$templates = self::templates();
		$key       = isset( $_GET['template'] ) ? sanitize_key( wp_unslash( $_GET['template'] ) ) : '';
		if ( ! isset( $templates[ $key ] ) ) {
			wp_die( esc_html__( 'Unknown template.', 'fxt-core' ), 404 );
		}
		$file = FXT_CORE_DIR . 'templates/markdown/' . $templates[ $key ]['file'];
		nocache_headers();
		header( 'Content-Type: text/markdown; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $templates[ $key ]['file'] . '"' );
		header( 'Content-Length: ' . filesize( $file ) );
		header( 'X-Content-Type-Options: nosniff' );
		readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}

	/**
	 * REST route.
	 */
	public static function routes() {
		register_rest_route(
			'fxt/v1',
			'/markdown',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'permission_callback' => array( __CLASS__, 'can_import' ),
				'callback'            => array( __CLASS__, 'convert' ),
				'args'                => array(
					'post_id'  => array( 'type' => 'integer', 'required' => true ),
					'markdown' => array( 'type' => 'string', 'required' => true ),
				),
			)
		);
	}

	/**
	 * Only users who can edit this Broker Review / Test Evidence post.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return bool|\WP_Error
	 */
	public static function can_import( $request ) {
		$post = get_post( (int) $request['post_id'] );
		if ( ! $post || ! in_array( $post->post_type, self::post_types(), true ) ) {
			return new \WP_Error( 'fxt_markdown_type', __( 'Markdown import is available for Broker Reviews and Test Evidence only.', 'fxt-core' ), array( 'status' => 400 ) );
		}
		return current_user_can( 'edit_post', $post->ID );
	}

	/**
	 * Convert Markdown to a block tree.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function convert( $request ) {
		$markdown = (string) $request['markdown'];
		if ( strlen( $markdown ) > Markdown::MAX_BYTES ) {
			return new \WP_Error( 'fxt_markdown_size', __( 'The Markdown is larger than 1 MB. Split it into smaller files.', 'fxt-core' ), array( 'status' => 400 ) );
		}
		if ( '' === wp_check_invalid_utf8( $markdown ) && '' !== $markdown ) {
			return new \WP_Error( 'fxt_markdown_encoding', __( 'The file is not UTF-8 text. Save it as UTF-8 Markdown and try again.', 'fxt-core' ), array( 'status' => 400 ) );
		}
		if ( '' === trim( $markdown ) ) {
			return new \WP_Error( 'fxt_markdown_empty', __( 'There is no Markdown to import.', 'fxt-core' ), array( 'status' => 400 ) );
		}
		$converter = new Markdown( array( __CLASS__, 'check_image' ) );
		$result    = $converter->convert( $markdown );
		$counts    = array();
		self::count( $result['blocks'], $counts );
		return rest_ensure_response(
			array(
				'blocks'   => $result['blocks'],
				'warnings' => $result['warnings'],
				'counts'   => $counts,
			)
		);
	}

	/**
	 * Count blocks by name (for the preview summary).
	 *
	 * @param array $blocks Tree.
	 * @param array $counts Counts (by reference).
	 */
	private static function count( array $blocks, array &$counts ) {
		foreach ( $blocks as $block ) {
			$counts[ $block['name'] ] = isset( $counts[ $block['name'] ] ) ? $counts[ $block['name'] ] + 1 : 1;
			self::count( $block['innerBlocks'], $counts );
		}
	}

	/**
	 * A usable image: an image in this site's media library or uploads folder,
	 * or a remote http(s) address that answers with an image.
	 *
	 * @param string $url Image URL from the Markdown.
	 * @return array|null { url, id }
	 */
	public static function check_image( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return null;
		}
		if ( '/' === $url[0] && ( ! isset( $url[1] ) || '/' !== $url[1] ) ) {
			$url = home_url( $url );
		}
		if ( ! preg_match( '#^https?://#i', $url ) || ! wp_http_validate_url( $url ) && 0 !== strpos( $url, home_url() ) ) {
			return null;
		}
		$url = esc_url_raw( $url );
		// Media library (also resized copies such as image-300x200.jpg).
		$id = attachment_url_to_postid( $url );
		if ( ! $id ) {
			$id = attachment_url_to_postid( preg_replace( '/-\d+x\d+(\.[a-z0-9]+)$/i', '$1', $url ) );
		}
		if ( $id && wp_attachment_is_image( $id ) ) {
			return array( 'url' => $url, 'id' => $id );
		}
		// A file in the uploads folder.
		$uploads = wp_get_upload_dir();
		if ( 0 === strpos( $url, $uploads['baseurl'] . '/' ) ) {
			$file = $uploads['basedir'] . '/' . rawurldecode( substr( wp_parse_url( $url, PHP_URL_PATH ), strlen( wp_parse_url( $uploads['baseurl'], PHP_URL_PATH ) ) + 1 ) );
			$real = realpath( $file );
			if ( $real && 0 === strpos( $real, realpath( $uploads['basedir'] ) ) && is_file( $real ) ) {
				$type = wp_check_filetype( $real );
				return 0 === strpos( (string) $type['type'], 'image/' ) ? array( 'url' => $url, 'id' => 0 ) : null;
			}
			return null;
		}
		// Remote: must answer with an image (a few checks per import).
		if ( self::$remote_checks <= 0 ) {
			return null;
		}
		--self::$remote_checks;
		$response = wp_safe_remote_head( $url, array( 'timeout' => 5, 'redirection' => 3 ) );
		if ( is_wp_error( $response ) || 405 === (int) wp_remote_retrieve_response_code( $response ) ) {
			$response = wp_safe_remote_get( $url, array( 'timeout' => 5, 'redirection' => 3, 'limit_response_size' => 2048 ) );
		}
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}
		return 0 === strpos( strtolower( (string) wp_remote_retrieve_header( $response, 'content-type' ) ), 'image/' ) ? array( 'url' => $url, 'id' => 0 ) : null;
	}

	/**
	 * Editor panel on Broker Review and Test Evidence screens.
	 */
	public static function editor_assets() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->post_type, self::post_types(), true ) ) {
			return;
		}
		wp_enqueue_script(
			'fxt-core-markdown-import',
			FXT_CORE_URL . 'assets/js/markdown-import.js',
			array( 'wp-plugins', 'wp-element', 'wp-components', 'wp-data', 'wp-blocks', 'wp-block-editor', 'wp-api-fetch', 'wp-i18n', 'wp-notices', 'wp-editor', 'wp-edit-post' ),
			FXT_CORE_VERSION,
			true
		);
		wp_enqueue_style( 'fxt-core-markdown-import', FXT_CORE_URL . 'assets/css/markdown-import.css', array(), FXT_CORE_VERSION );
		$templates = array();
		foreach ( self::templates() as $key => $template ) {
			$templates[] = array(
				'key'     => $key,
				'title'   => $template['title'],
				'file'    => $template['file'],
				'url'     => self::template_url( $key ),
				'primary' => $template['type'] === $screen->post_type,
			);
		}
		wp_localize_script(
			'fxt-core-markdown-import',
			'fxtMarkdownImport',
			array(
				'templates' => $templates,
				'maxBytes'  => Markdown::MAX_BYTES,
				'helpUrl'   => admin_url( 'edit.php?post_type=' . Content_Types::BROKER . '&page=' . self::PAGE ),
			)
		);
		wp_set_script_translations( 'fxt-core-markdown-import', 'fxt-core', FXT_CORE_DIR . 'languages' );
	}

	/**
	 * Broker Reviews > Markdown import (templates and how-to).
	 */
	public static function menu() {
		add_submenu_page(
			'edit.php?post_type=' . Content_Types::BROKER,
			__( 'Markdown import', 'fxt-core' ),
			__( 'Markdown import', 'fxt-core' ),
			'edit_posts',
			self::PAGE,
			array( __CLASS__, 'page' )
		);
	}

	/**
	 * Help page with the two templates.
	 */
	public static function page() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		$rows = array(
			array( '## Heading (### to ######)', __( 'Heading H2 to H6 (# becomes H2: the post title is the H1)', 'fxt-core' ) ),
			array( __( 'Text separated by an empty line', 'fxt-core' ), __( 'Paragraph', 'fxt-core' ) ),
			array( '**bold**, *italic*, ~~strike~~, `code`', __( 'Bold, italic, strikethrough, inline code', 'fxt-core' ) ),
			array( '[text](https://example.com)', __( 'Link', 'fxt-core' ) ),
			array( '- item / 1. item (indent 2 spaces to nest)', __( 'List (unordered / ordered, nested)', 'fxt-core' ) ),
			array( '> quote', __( 'Quote', 'fxt-core' ) ),
			array( '| a | b | + | --- | :---: |', __( 'Table (header row, alignment), style "Data table"', 'fxt-core' ) ),
			array( '---', __( 'Separator', 'fxt-core' ) ),
			array( '![alt text](https://.../image.jpg "Caption")', __( 'Image (on its own line; only valid, reachable images)', 'fxt-core' ) ),
			array( '```', __( 'Code', 'fxt-core' ) ),
		);
		echo '<div class="wrap"><h1>' . esc_html__( 'Markdown import', 'fxt-core' ) . '</h1>';
		echo '<p>' . esc_html__( 'Open a Broker Review or a Test Evidence record, then in the editor sidebar (Post tab) use the "Markdown import" panel: upload a .md file or paste Markdown, check the preview, then append it to the content or replace the content. The result is ordinary blocks you can edit. Only the post content changes: Broker data, Test Evidence data and other settings are not touched.', 'fxt-core' ) . '</p>';
		echo '<h2>' . esc_html__( 'Templates', 'fxt-core' ) . '</h2><p>';
		foreach ( self::templates() as $key => $template ) {
			printf( '<a class="button" href="%1$s">%2$s</a> ', esc_url( self::template_url( $key ) ), esc_html( sprintf( /* translators: %s: file name */ __( 'Download %s', 'fxt-core' ), $template['file'] ) ) );
		}
		echo '</p><h2>' . esc_html__( 'How Markdown becomes blocks', 'fxt-core' ) . '</h2>';
		echo '<table class="widefat striped" style="max-width:60rem"><thead><tr><th>' . esc_html__( 'Markdown', 'fxt-core' ) . '</th><th>' . esc_html__( 'Block', 'fxt-core' ) . '</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			echo '<tr><td><code>' . esc_html( $row[0] ) . '</code></td><td>' . esc_html( $row[1] ) . '</td></tr>';
		}
		echo '</tbody></table>';
		echo '<p>' . esc_html__( 'HTML written in the Markdown is shown as text. Links keep http(s), mailto, tel, relative and #anchor addresses only. Images must be in the media library, the uploads folder or at an address that answers with an image; other images are listed in the preview and skipped.', 'fxt-core' ) . '</p></div>';
	}
}
