<?php
/**
 * REST: server-side rendering of interactive regions.
 *
 * JavaScript never builds markup. When a visitor changes the country, the
 * comparison selection or a directory filter, it asks this endpoint for the
 * region's HTML, rendered by the same PHP views as the initial page.
 *
 * GET /wp-json/fxt/v1/render?view=compare&args={...}&base=...&country=VN
 *
 * @package FXT\Core
 */

namespace FXT\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Read-only render endpoint (public data only).
 */
final class Rest {

	const VIEWS = array( 'country-brokers', 'broker-cards', 'compare', 'directory-results', 'evidence-list' );

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	/**
	 * Route registration.
	 */
	public static function routes() {
		register_rest_route(
			'fxt/v1',
			'/render',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'render' ),
				'args'                => array(
					'view'    => array(
						'type'     => 'string',
						'enum'     => self::VIEWS,
						'required' => true,
					),
					'args'    => array(
						'type'    => 'string',
						'default' => '{}',
					),
					'base'    => array(
						'type'    => 'string',
						'default' => '',
					),
					'country' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
	}

	/**
	 * Render a region.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public static function render( \WP_REST_Request $request ) {
		$args = json_decode( (string) $request['args'], true );
		$args = is_array( $args ) ? $args : array();
		$base = Blocks::safe_base( $request['base'] );

		switch ( $request['view'] ) {
			case 'country-brokers':
				$html = fxt_core_get_view( 'blocks/country-brokers', Blocks::hero_args( $args ) );
				break;

			case 'evidence-list':
				$html = fxt_core_get_view( 'blocks/evidence-list', array_merge( Blocks::evidence_list_args( $args ), array( 'filter' => '', 'base' => $base ) ) );
				break;

			case 'broker-cards':
				$html = fxt_core_get_view( 'blocks/broker-cards', Blocks::card_args( $args ) );
				break;

			case 'compare':
				$slots = max( 2, min( 4, isset( $args['slots'] ) ? (int) $args['slots'] : 3 ) );
				$html  = fxt_core_get_view(
					'blocks/compare',
					array(
						'mode'     => ( isset( $args['mode'] ) && 'full' === $args['mode'] ) ? 'full' : 'preview',
						'slots'    => $slots,
						'selected' => array_slice( Blocks::parse_slugs( isset( $args['brokers'] ) ? $args['brokers'] : '' ), 0, $slots ),
						'all'      => ! empty( $args['all'] ),
						'sync'     => ! empty( $args['sync'] ),
						'rows'     => Blocks::compare_keys( isset( $args['rows'] ) ? $args['rows'] : '', 'rows' ),
						'groups'   => Blocks::compare_keys( isset( $args['groups'] ) ? $args['groups'] : '', 'groups' ),
						'base_url' => $base,
					)
				);
				break;

			default: // directory-results.
				$html = fxt_core_get_view(
					'blocks/directory-results',
					array(
						'filters'  => Blocks::directory_filters( $args ),
						'base_url' => $base,
					)
				);
		}

		$response = new \WP_REST_Response( array( 'html' => $html ) );
		$response->header( 'Cache-Control', 'no-store' );
		return $response;
	}
}
