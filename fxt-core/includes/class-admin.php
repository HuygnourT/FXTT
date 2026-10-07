<?php
/**
 * Admin UI: meta boxes, market term fields, author profile fields, settings page.
 * Every form is rendered and sanitized from Schema through Fields.
 *
 * @package FXT\Core
 */

namespace FXT\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Admin screens.
 */
final class Admin {

	const NONCE = 'fxt_core_save';

	/**
	 * Meta box layout: post type => [ box id => [ title, field keys ] ].
	 *
	 * @return array
	 */
	private static function boxes() {
		return array(
			Content_Types::BROKER   => array(
				'fxt-research'  => array( __( 'Broker data: research summary', 'fxt-core' ), array( 'fxt_status', 'fxt_score', 'fxt_score_breakdown', 'fxt_reviewed', 'fxt_monogram', 'fxt_founded', 'fxt_min_deposit', 'fxt_currencies', 'fxt_other_platforms', 'fxt_affiliate_url', 'fxt_cta_label' ) ),
				'fxt-trading'   => array( __( 'Broker data: trading conditions and costs', 'fxt-core' ), array( 'fxt_trading', 'fxt_costs', 'fxt_payments' ) ),
				'fxt-entities'  => array( __( 'Broker data: accounts and legal entities', 'fxt-core' ), array( 'fxt_accounts', 'fxt_entities' ) ),
				'fxt-markets'   => array( __( 'Broker data: conditions by country', 'fxt-core' ), array( 'fxt_markets' ) ),
			),
			Content_Types::EVIDENCE => array(
				'fxt-test'      => array( __( 'Evidence: test details', 'fxt-core' ), array( 'fxt_broker_id', 'fxt_market', 'fxt_status', 'fxt_period', 'fxt_account_label' ) ),
				'fxt-summary'   => array( __( 'Evidence: summary and timeline', 'fxt-core' ), array( 'fxt_summary', 'fxt_timeline' ) ),
				'fxt-payments'  => array( __( 'Evidence: deposit and withdrawal tests (masked on save)', 'fxt-core' ), array( 'fxt_deposits', 'fxt_withdrawals' ) ),
			),
		);
	}

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save_post' ), 10, 2 );

		foreach ( array_keys( self::term_schemas() ) as $taxonomy ) {
			add_action( $taxonomy . '_add_form_fields', array( __CLASS__, 'term_add_fields' ) );
			add_action( $taxonomy . '_edit_form_fields', array( __CLASS__, 'term_edit_fields' ), 10, 2 );
			add_action( 'created_' . $taxonomy, array( __CLASS__, 'save_term' ) );
			add_action( 'edited_' . $taxonomy, array( __CLASS__, 'save_term' ) );
		}

		add_action( 'show_user_profile', array( __CLASS__, 'user_fields' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'user_fields' ) );
		add_action( 'personal_options_update', array( __CLASS__, 'save_user' ) );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'save_user' ) );

		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'editor_panel' ) );
		add_action( 'admin_menu', array( __CLASS__, 'settings_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );

		add_filter( 'manage_' . Content_Types::BROKER . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . Content_Types::BROKER . '_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
	}

	/* ---------------------------------------------------------------------
	 * Post meta boxes
	 * ------------------------------------------------------------------ */

	/**
	 * Register meta boxes.
	 */
	public static function add_meta_boxes() {
		foreach ( self::boxes() as $post_type => $boxes ) {
			$schema = Content_Types::BROKER === $post_type ? Schema::broker() : Schema::evidence();
			$first  = true;
			foreach ( $boxes as $id => $box ) {
				add_meta_box(
					$id,
					$box[0],
					static function ( $post ) use ( $schema, $box, $first ) {
						if ( $first ) {
							wp_nonce_field( self::NONCE, '_fxt_nonce' );
						}
						echo '<div class="fxt-fields">';
						foreach ( $box[1] as $key ) {
							Fields::render( $schema[ $key ], 'fxt_meta[' . $key . ']', get_post_meta( $post->ID, $key, true ), 'fxt-' . $key );
						}
						echo '</div>';
					},
					$post_type,
					'normal',
					'default'
				);
				$first = false;
			}
		}
	}

	/**
	 * Save meta from the meta box form (also used by the block editor's meta box request).
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post.
	 */
	public static function save_post( $post_id, $post ) {
		$boxes = self::boxes();
		if ( ! isset( $boxes[ $post->post_type ] ) ) {
			return;
		}
		if ( ! isset( $_POST['_fxt_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_fxt_nonce'] ) ), self::NONCE ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$schema = Content_Types::BROKER === $post->post_type ? Schema::broker() : Schema::evidence();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per field below.
		$input = isset( $_POST['fxt_meta'] ) && is_array( $_POST['fxt_meta'] ) ? wp_unslash( $_POST['fxt_meta'] ) : array();

		foreach ( $boxes[ $post->post_type ] as $box ) {
			foreach ( $box[1] as $key ) {
				$value = Fields::sanitize( $schema[ $key ], isset( $input[ $key ] ) ? $input[ $key ] : null );
				if ( null === $value || false === $value ) {
					delete_post_meta( $post_id, $key );
				} else {
					update_post_meta( $post_id, $key, $value );
				}
			}
		}
		Repository::flush();
	}

	/* ---------------------------------------------------------------------
	 * Market term fields
	 * ------------------------------------------------------------------ */

	/**
	 * Taxonomies with extra term fields: taxonomy => schema.
	 *
	 * @return array<string, array>
	 */
	private static function term_schemas() {
		return array(
			Content_Types::MARKET   => Schema::market(),
			Content_Types::PLATFORM => Schema::platform(),
		);
	}

	/**
	 * Fields on the "Add term" form.
	 *
	 * @param string $taxonomy Taxonomy.
	 */
	public static function term_add_fields( $taxonomy ) {
		$schemas = self::term_schemas();
		if ( empty( $schemas[ $taxonomy ] ) ) {
			return;
		}
		wp_nonce_field( self::NONCE, '_fxt_nonce' );
		foreach ( $schemas[ $taxonomy ] as $key => $field ) {
			echo '<div class="form-field">';
			Fields::render( $field, 'fxt_meta[' . $key . ']', null, 'fxt-' . $key );
			echo '</div>';
		}
	}

	/**
	 * Fields on the "Edit term" form.
	 *
	 * @param \WP_Term $term     Term.
	 * @param string   $taxonomy Taxonomy.
	 */
	public static function term_edit_fields( $term, $taxonomy = '' ) {
		$schemas = self::term_schemas();
		$taxonomy = $taxonomy ? $taxonomy : $term->taxonomy;
		if ( empty( $schemas[ $taxonomy ] ) ) {
			return;
		}
		wp_nonce_field( self::NONCE, '_fxt_nonce' );
		foreach ( $schemas[ $taxonomy ] as $key => $field ) {
			echo '<tr class="form-field"><th scope="row"></th><td>';
			Fields::render( $field, 'fxt_meta[' . $key . ']', get_term_meta( $term->term_id, $key, true ), 'fxt-' . $key );
			echo '</td></tr>';
		}
	}

	/**
	 * Save market term meta.
	 *
	 * @param int $term_id Term ID.
	 */
	public static function save_term( $term_id ) {
		if ( ! isset( $_POST['_fxt_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_fxt_nonce'] ) ), self::NONCE ) || ! current_user_can( 'manage_categories' ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per field below.
		$input   = isset( $_POST['fxt_meta'] ) && is_array( $_POST['fxt_meta'] ) ? wp_unslash( $_POST['fxt_meta'] ) : array();
		$term    = get_term( $term_id );
		$schemas = self::term_schemas();
		if ( ! $term || is_wp_error( $term ) || empty( $schemas[ $term->taxonomy ] ) ) {
			return;
		}
		foreach ( $schemas[ $term->taxonomy ] as $key => $field ) {
			$value = Fields::sanitize( $field, isset( $input[ $key ] ) ? $input[ $key ] : null );
			if ( 'fxt_iso' === $key && $value ) {
				$value = strtoupper( substr( $value, 0, 2 ) );
			}
			if ( null === $value ) {
				delete_term_meta( $term_id, $key );
			} else {
				update_term_meta( $term_id, $key, $value );
			}
		}
		Repository::flush();
	}

	/* ---------------------------------------------------------------------
	 * Author profile fields
	 * ------------------------------------------------------------------ */

	/**
	 * Profile section.
	 *
	 * @param \WP_User $user User.
	 */
	public static function user_fields( $user ) {
		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}
		echo '<h2>' . esc_html__( 'Author profile (FX Trading Today)', 'fxt-core' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Shown in bylines, author boxes and the author page. "Biographical Info" above is the long biography.', 'fxt-core' ) . '</p>';
		wp_nonce_field( self::NONCE, '_fxt_nonce' );
		echo '<div class="fxt-fields fxt-fields--profile">';
		foreach ( Schema::author() as $key => $field ) {
			Fields::render( $field, 'fxt_meta[' . $key . ']', get_user_meta( $user->ID, $key, true ), 'fxt-' . $key );
		}
		echo '</div>';
	}

	/**
	 * Save profile fields.
	 *
	 * @param int $user_id User ID.
	 */
	public static function save_user( $user_id ) {
		if ( ! isset( $_POST['_fxt_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_fxt_nonce'] ) ), self::NONCE ) || ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per field below.
		$input = isset( $_POST['fxt_meta'] ) && is_array( $_POST['fxt_meta'] ) ? wp_unslash( $_POST['fxt_meta'] ) : array();
		foreach ( Schema::author() as $key => $field ) {
			$value = Fields::sanitize( $field, isset( $input[ $key ] ) ? $input[ $key ] : null );
			if ( null === $value || false === $value ) {
				delete_user_meta( $user_id, $key );
			} else {
				update_user_meta( $user_id, $key, $value );
			}
		}
		Repository::flush();
	}

	/* ---------------------------------------------------------------------
	 * Settings page
	 * ------------------------------------------------------------------ */

	/**
	 * Settings > FX Trading Today.
	 */
	public static function settings_menu() {
		add_options_page(
			__( 'FX Trading Today', 'fxt-core' ),
			__( 'FX Trading Today', 'fxt-core' ),
			'manage_options',
			'fxt-settings',
			array( __CLASS__, 'settings_page' )
		);
	}

	/**
	 * Register the option with its sanitizer.
	 */
	public static function register_settings() {
		register_setting(
			'fxt_settings_group',
			Repository::SETTINGS,
			array(
				'type'              => 'object',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Sanitize the whole settings array.
	 *
	 * @param mixed $raw Raw input.
	 * @return array
	 */
	public static function sanitize_settings( $raw ) {
		$raw   = is_array( $raw ) ? $raw : array();
		$clean = array();
		foreach ( Schema::settings() as $key => $field ) {
			$clean[ $key ] = Fields::sanitize( $field, isset( $raw[ $key ] ) ? $raw[ $key ] : null );
		}
		$clean['score_categories'] = self::validate_categories( $clean['score_categories'] );
		$clean['deposit_bands']    = $clean['deposit_bands'] ? array_values( array_unique( array_map( 'strval', array_map( 'absint', $clean['deposit_bands'] ) ) ) ) : null;
		return $clean;
	}

	/**
	 * Score categories: keys are required and unique (generated from the label
	 * when empty) and weights must add up to 100. An invalid set is rejected
	 * and the previous categories are kept, so scores never use a broken
	 * configuration.
	 *
	 * @param array|null $rows Sanitized repeater rows.
	 * @return array|null
	 */
	private static function validate_categories( $rows ) {
		$previous = Repository::setting( 'score_categories' );
		$rows     = is_array( $rows ) ? $rows : array();
		$out      = array();
		$seen     = array();
		$errors   = array();
		foreach ( $rows as $row ) {
			$label = isset( $row['label'] ) ? (string) $row['label'] : '';
			$key   = sanitize_key( isset( $row['key'] ) && '' !== $row['key'] ? $row['key'] : sanitize_title( $label ) );
			if ( '' === $key && '' === $label ) {
				continue; // Empty row.
			}
			if ( '' === $key || isset( $seen[ $key ] ) ) {
				/* translators: %s: category label */
				$errors[] = sprintf( __( 'Category "%s" needs a unique key.', 'fxt-core' ), $label ? $label : $key );
				continue;
			}
			$seen[ $key ] = true;
			$out[]        = array(
				'key'         => $key,
				'label'       => $label ? $label : $key,
				'weight'      => isset( $row['weight'] ) ? (int) $row['weight'] : 0,
				'description' => isset( $row['description'] ) ? (string) $row['description'] : '',
				'evidence'    => isset( $row['evidence'] ) ? (string) $row['evidence'] : '',
			);
		}
		$sum = array_sum( wp_list_pluck( $out, 'weight' ) );
		if ( ! $out ) {
			$errors[] = __( 'Add at least one score category.', 'fxt-core' );
		} elseif ( 100 !== $sum ) {
			/* translators: %d: sum of weights */
			$errors[] = sprintf( __( 'Score weights add up to %d%%. They must add up to 100%%.', 'fxt-core' ), $sum );
		}
		if ( $errors ) {
			add_settings_error( Repository::SETTINGS, 'fxt-categories', implode( ' ', $errors ) . ' ' . __( 'Score categories were not changed; the other settings were saved.', 'fxt-core' ), 'error' );
			return $previous;
		}
		return $out;
	}

	/**
	 * Sidebar panel in the block editor that leads to the data meta boxes.
	 */
	public static function editor_panel() {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, array( Content_Types::BROKER, Content_Types::EVIDENCE ), true ) ) {
			return;
		}
		wp_enqueue_script( 'fxt-core-editor-panel', FXT_CORE_URL . 'assets/js/editor-panel.js', array( 'wp-plugins', 'wp-element', 'wp-components', 'wp-i18n', 'wp-editor' ), FXT_CORE_VERSION, true );
		$is_broker = Content_Types::BROKER === $screen->post_type;
		wp_localize_script(
			'fxt-core-editor-panel',
			'fxtCoreEditor',
			array(
				'title'  => $is_broker ? __( 'Broker data', 'fxt-core' ) : __( 'Evidence data', 'fxt-core' ),
				'text'   => $is_broker
					? __( 'Scores, costs, entities, countries, payments, accounts and links are edited in the panels below the content. Every page that shows this broker (home, directory, compare, review) updates on save.', 'fxt-core' )
					: __( 'Country, period, summary cards, timeline and each deposit or withdrawal test are edited in the panels below the content.', 'fxt-core' ),
				'button' => $is_broker ? __( 'Edit broker data', 'fxt-core' ) : __( 'Edit evidence data', 'fxt-core' ),
			)
		);
	}

	/**
	 * Render the settings page.
	 */
	public static function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = Repository::settings();
		echo '<div class="wrap"><h1>' . esc_html__( 'FX Trading Today settings', 'fxt-core' ) . '</h1>';
		echo '<p>' . esc_html__( 'Site-wide content used by the theme and blocks. Brand name and logo are set in Appearance > Customize > Site Identity.', 'fxt-core' ) . '</p>';
		echo '<form method="post" action="options.php">';
		settings_fields( 'fxt_settings_group' );
		echo '<div class="fxt-fields fxt-fields--settings">';
		foreach ( Schema::settings() as $key => $field ) {
			Fields::render( $field, Repository::SETTINGS . '[' . $key . ']', isset( $settings[ $key ] ) ? $settings[ $key ] : null, 'fxt-setting-' . $key );
		}
		echo '</div>';
		submit_button();
		echo '</form></div>';
	}

	/* ---------------------------------------------------------------------
	 * List table columns
	 * ------------------------------------------------------------------ */

	/**
	 * Add score and status columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function columns( $columns ) {
		$out = array();
		foreach ( $columns as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'title' === $key ) {
				$out['fxt_score']  = __( 'Score', 'fxt-core' );
				$out['fxt_status'] = __( 'Review status', 'fxt-core' );
			}
		}
		return $out;
	}

	/**
	 * Column content.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public static function column( $column, $post_id ) {
		if ( 'fxt_score' === $column ) {
			$score = get_post_meta( $post_id, 'fxt_score', true );
			echo '' === $score ? '&mdash;' : esc_html( number_format_i18n( (float) $score, 1 ) );
		} elseif ( 'fxt_status' === $column ) {
			$labels = Schema::review_status();
			$status = (string) get_post_meta( $post_id, 'fxt_status', true );
			echo esc_html( isset( $labels[ $status ] ) ? $labels[ $status ] : $labels['published'] );
		}
	}

	/* ---------------------------------------------------------------------
	 * Assets
	 * ------------------------------------------------------------------ */

	/**
	 * Load admin CSS/JS only on screens that use the field engine.
	 *
	 * @param string $hook Current admin page.
	 */
	public static function assets( $hook ) {
		$screen = get_current_screen();
		$ours   = $screen && (
			in_array( $screen->post_type, array( Content_Types::BROKER, Content_Types::EVIDENCE ), true )
			|| in_array( $screen->taxonomy, array( Content_Types::MARKET, Content_Types::PLATFORM ), true )
			|| in_array( $hook, array( 'profile.php', 'user-edit.php', 'settings_page_fxt-settings', 'appearance_page_fxt-demo' ), true )
		);
		if ( ! $ours ) {
			return;
		}
		wp_enqueue_style( 'fxt-core-admin', FXT_CORE_URL . 'assets/css/admin.css', array(), FXT_CORE_VERSION );
		wp_enqueue_script( 'fxt-core-admin', FXT_CORE_URL . 'assets/js/admin.js', array(), FXT_CORE_VERSION, true );
		if ( in_array( $hook, array( 'profile.php', 'user-edit.php' ), true ) ) {
			wp_enqueue_media();
		}
	}
}
