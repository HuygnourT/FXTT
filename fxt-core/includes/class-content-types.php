<?php
/**
 * Post types, taxonomies and meta registration.
 *
 * @package FXT\Core
 */

namespace FXT\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the content model.
 */
final class Content_Types {

	const BROKER   = 'fxt_broker';
	const EVIDENCE = 'fxt_evidence';

	const MARKET       = 'fxt_market';
	const REGULATOR    = 'fxt_regulator';
	const PLATFORM     = 'fxt_platform';
	const ACCOUNT_TYPE = 'fxt_account_type';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_types' ) );
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ) );
		add_action( 'init', array( __CLASS__, 'register_meta' ), 20 );
	}

	/**
	 * Broker reviews and test evidence.
	 */
	public static function register_post_types() {
		register_post_type(
			self::BROKER,
			array(
				'labels'        => array(
					'name'               => __( 'Broker Reviews', 'fxt-core' ),
					'singular_name'      => __( 'Broker Review', 'fxt-core' ),
					'add_new'            => __( 'Add New Review', 'fxt-core' ),
					'add_new_item'       => __( 'Add New Broker Review', 'fxt-core' ),
					'edit_item'          => __( 'Edit Broker Review', 'fxt-core' ),
					'new_item'           => __( 'New Broker Review', 'fxt-core' ),
					'view_item'          => __( 'View Broker Review', 'fxt-core' ),
					'search_items'       => __( 'Search Broker Reviews', 'fxt-core' ),
					'not_found'          => __( 'No broker reviews found.', 'fxt-core' ),
					'all_items'          => __( 'All Broker Reviews', 'fxt-core' ),
					'menu_name'          => __( 'Broker Reviews', 'fxt-core' ),
					'featured_image'     => __( 'Broker logo', 'fxt-core' ),
					'set_featured_image' => __( 'Set broker logo', 'fxt-core' ),
				),
				'public'        => true,
				'show_in_rest'  => true,
				'menu_icon'     => 'dashicons-chart-line',
				'menu_position' => 20,
				// The directory is a normal Page (editable in Gutenberg) holding the fxt/broker-directory block.
				'has_archive'   => false,
				'rewrite'       => array( 'slug' => _x( 'broker-reviews', 'URL slug', 'fxt-core' ), 'with_front' => false ),
				// page-attributes: "Order" breaks ties between equal scores in lists.
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions', 'custom-fields', 'page-attributes' ),
				'template'      => Block_Templates::broker(),
			)
		);

		register_post_type(
			self::EVIDENCE,
			array(
				'labels'        => array(
					'name'          => __( 'Test Evidence', 'fxt-core' ),
					'singular_name' => __( 'Test Evidence', 'fxt-core' ),
					'add_new'       => __( 'Add New Evidence', 'fxt-core' ),
					'add_new_item'  => __( 'Add New Test Evidence', 'fxt-core' ),
					'edit_item'     => __( 'Edit Test Evidence', 'fxt-core' ),
					'view_item'     => __( 'View Test Evidence', 'fxt-core' ),
					'all_items'     => __( 'Test Evidence', 'fxt-core' ),
					'not_found'     => __( 'No test evidence found.', 'fxt-core' ),
				),
				'public'        => true,
				'show_in_rest'  => true,
				'show_in_menu'  => 'edit.php?post_type=' . self::BROKER,
				'has_archive'   => false,
				'rewrite'       => array( 'slug' => _x( 'test-evidence', 'URL slug', 'fxt-core' ), 'with_front' => false ),
				'supports'      => array( 'title', 'editor', 'excerpt', 'author', 'revisions', 'custom-fields' ),
				'template'      => Block_Templates::evidence(),
			)
		);
	}

	/**
	 * Markets (countries) and broker classification taxonomies.
	 */
	public static function register_taxonomies() {
		register_taxonomy(
			self::MARKET,
			array( self::BROKER ),
			array(
				'labels'            => array(
					'name'          => __( 'Markets', 'fxt-core' ),
					'singular_name' => __( 'Market', 'fxt-core' ),
					'add_new_item'  => __( 'Add New Market', 'fxt-core' ),
					'edit_item'     => __( 'Edit Market', 'fxt-core' ),
					'menu_name'     => __( 'Markets', 'fxt-core' ),
				),
				'public'            => false,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => false,
				// Per-broker market data lives in the fxt_markets meta table, not in term assignments.
				'meta_box_cb'       => false,
				'hierarchical'      => false,
			)
		);

		$classifications = array(
			self::REGULATOR    => array( __( 'Regulators', 'fxt-core' ), __( 'Regulator', 'fxt-core' ) ),
			self::PLATFORM     => array( __( 'Platforms', 'fxt-core' ), __( 'Platform', 'fxt-core' ) ),
			self::ACCOUNT_TYPE => array( __( 'Account Types', 'fxt-core' ), __( 'Account Type', 'fxt-core' ) ),
		);

		foreach ( $classifications as $taxonomy => $names ) {
			register_taxonomy(
				$taxonomy,
				array( self::BROKER ),
				array(
					'labels'            => array(
						'name'          => $names[0],
						'singular_name' => $names[1],
						'menu_name'     => $names[0],
					),
					'public'            => false,
					'show_ui'           => true,
					'show_in_rest'      => true,
					'show_admin_column' => true,
					'hierarchical'      => true, // Checkbox UI in the editor: pick from a fixed list.
					'sort'              => true, // Keep the order terms were assigned in (term_order).
				)
			);
		}
	}

	/**
	 * Post, term and user meta, all generated from Schema.
	 */
	public static function register_meta() {
		self::register_set( 'post', Schema::broker(), self::BROKER );
		self::register_set( 'post', Schema::evidence(), self::EVIDENCE );
		self::register_set( 'term', Schema::market(), self::MARKET );
		self::register_set( 'user', Schema::author(), '' );
	}

	/**
	 * Register one schema as meta.
	 *
	 * @param string $object_type post|term|user.
	 * @param array  $schema      Field schema.
	 * @param string $subtype     Post type or taxonomy.
	 */
	private static function register_set( $object_type, array $schema, $subtype ) {
		foreach ( $schema as $key => $field ) {
			$args = array(
				'type'              => Fields::meta_type( $field ),
				'single'            => true,
				'show_in_rest'      => array( 'schema' => Fields::rest_schema( $field ) ),
				'sanitize_callback' => static function ( $value ) use ( $field ) {
					return Fields::sanitize( $field, $value );
				},
				'auth_callback'     => static function ( $allowed, $meta_key, $object_id ) use ( $object_type ) {
					if ( 'user' === $object_type ) {
						return current_user_can( 'edit_user', $object_id );
					}
					if ( 'term' === $object_type ) {
						return current_user_can( 'manage_categories' );
					}
					return current_user_can( 'edit_post', $object_id );
				},
			);
			if ( $subtype ) {
				$args['object_subtype'] = $subtype;
			}
			register_meta( $object_type, $key, $args );
		}
	}
}
