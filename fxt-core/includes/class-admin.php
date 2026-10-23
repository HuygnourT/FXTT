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
				'fxt-research'  => array( __( 'Broker data: research summary', 'fxt-core' ), array( 'fxt_name', 'fxt_status', 'fxt_score', 'fxt_score_breakdown', 'fxt_reviewed', 'fxt_monogram', 'fxt_founded', 'fxt_min_deposit', 'fxt_currencies', 'fxt_other_platforms', 'fxt_affiliate_url', 'fxt_cta_label' ) ),
				'fxt-trading'   => array( __( 'Broker data: trading conditions and costs', 'fxt-core' ), array( 'fxt_trading', 'fxt_costs', 'fxt_payments' ) ),
				'fxt-accounts'  => array( __( 'Broker data: account types', 'fxt-core' ), 'render' => array( Accounts::class, 'render_fields_box' ) ),
				'fxt-accounts-display' => array( __( 'Broker data: account types display options', 'fxt-core' ), 'render' => array( Accounts::class, 'render_display_box' ) ),
				'fxt-entities'  => array( __( 'Broker data: legal entities', 'fxt-core' ), array( 'fxt_entities' ) ),
				'fxt-accepting' => array( __( 'Broker data: accepting residents', 'fxt-core' ), array( 'fxt_accepting' ) ),
				'fxt-markets'   => array( __( 'Broker data: tested residents', 'fxt-core' ), array( 'fxt_markets' ) ),
				'fxt-sources'   => array( __( 'Broker data: sources', 'fxt-core' ), array( 'fxt_sources' ) ),
			),
			Content_Types::EVIDENCE => array(
				'fxt-test'      => array( __( 'Evidence: test details', 'fxt-core' ), array( 'fxt_broker_id', 'fxt_market', 'fxt_status', 'fxt_period', 'fxt_account_label' ) ),
				'fxt-summary'   => array( __( 'Evidence: summary and timeline', 'fxt-core' ), array( 'fxt_summary', 'fxt_timeline' ) ),
				'fxt-payments'  => array( __( 'Evidence: deposit and withdrawal tests (masked on save)', 'fxt-core' ), array( 'fxt_deposits', 'fxt_withdrawals' ) ),
				'fxt-more-tests' => array( __( 'Evidence: spread, execution and customer support tests', 'fxt-core' ), array( 'fxt_spread_tests', 'fxt_execution_tests', 'fxt_support_tests' ) ),
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
		// Sidebar box: content under the author line (all content types with a byline).
		foreach ( Schema::byline_types() as $post_type ) {
			add_meta_box(
				'fxt-byline-note',
				__( 'Under the author line', 'fxt-core' ),
				static function ( $post ) {
					wp_nonce_field( 'fxt_byline_note', '_fxt_byline_nonce' );
					echo '<div class="fxt-fields fxt-fields--side">';
					foreach ( Schema::byline() as $key => $field ) {
						Fields::render( $field, 'fxt_byline[' . $key . ']', get_post_meta( $post->ID, $key, true ), 'fxt-' . $key );
					}
					echo '<p class="description">' . esc_html__( 'Default content: Settings > FX Trading Today.', 'fxt-core' ) . '</p></div>';
				},
				$post_type,
				'side',
				'default'
			);
		}

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
						if ( isset( $box['render'] ) ) {
							call_user_func( $box['render'], $post );
							return;
						}
						echo '<div class="fxt-fields">';
						foreach ( $box[1] as $key ) {
							$value = get_post_meta( $post->ID, $key, true );
							if ( 'fxt_accepting' === $key && ! metadata_exists( 'post', $post->ID, 'fxt_accepting' ) ) {
								// Pre-1.4 brokers: start from the countries their rows marked "Available".
								$value = Repository::accepting_config( $post->ID );
							}
							if ( 'fxt_entities' === $key && is_array( $value ) ) {
								// Version 1.1 "verified" checkbox -> source select.
								$value = array_map( array( Repository::class, 'normalize_entity' ), $value );
							}
							Fields::render( $schema[ $key ], 'fxt_meta[' . $key . ']', $value, 'fxt-' . $key );
							if ( 'fxt_sources' === $key ) {
								self::sources_warning( $post );
							}
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
	 * List facts marked "Tested" that have no published test evidence behind
	 * them. They are shown as "Needs review" on the site until evidence is
	 * published or the source is changed.
	 *
	 * @param \WP_Post $post Broker post.
	 */
	private static function sources_warning( $post ) {
		$broker = Repository::normalize_broker( $post );
		$rows   = Schema::source_rows();
		$flags  = array();
		foreach ( $rows as $key => $label ) {
			if ( 'review' === Repository::source( $broker, $key )['source'] ) {
				$flags[] = $label;
			}
		}
		foreach ( $broker['entities'] as $entity ) {
			if ( 'tested' === $entity['source'] && 'review' === Repository::resolve_source( array( 'source' => 'tested' ), $broker )['source'] ) {
				/* translators: %s: legal entity name */
				$flags[] = sprintf( __( 'Legal entity %s', 'fxt-core' ), $entity['name'] ? $entity['name'] : $entity['key'] );
			}
		}
		if ( 'tested' === $broker['accepting']['source'] && ! Repository::has_published_tests( $broker['id'] ) ) {
			$flags[] = __( 'Accepting residents (availability source)', 'fxt-core' );
		}
		if ( $flags ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Marked "Tested" but no published test evidence with completed tests backs it, so the site shows "Needs review":', 'fxt-core' ) . ' ' . esc_html( implode( ', ', $flags ) ) . '.</p></div>';
		}
	}

	/**
	 * Save the "Under the author line" sidebar box.
	 *
	 * @param int $post_id Post ID.
	 */
	private static function save_byline_note( $post_id ) {
		if ( ! isset( $_POST['_fxt_byline_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_fxt_byline_nonce'] ) ), 'fxt_byline_note' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per field below.
		$input = isset( $_POST['fxt_byline'] ) && is_array( $_POST['fxt_byline'] ) ? wp_unslash( $_POST['fxt_byline'] ) : array();
		foreach ( Schema::byline() as $key => $field ) {
			$value = Fields::sanitize( $field, isset( $input[ $key ] ) ? $input[ $key ] : null );
			if ( null === $value ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
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
		self::save_byline_note( $post_id );
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

		if ( Content_Types::BROKER === $post->post_type && isset( $_POST['fxt_acc'] ) && is_array( $_POST['fxt_acc'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in Accounts::save().
			Accounts::save( $post_id, wp_unslash( $_POST['fxt_acc'] ) );
		}
		foreach ( $boxes[ $post->post_type ] as $box ) {
			if ( ! isset( $box[1] ) ) {
				continue;
			}
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
		$raw      = is_array( $raw ) ? $raw : array();
		$previous = get_option( Repository::SETTINGS, array() );
		$previous = is_array( $previous ) ? $previous : array();
		// The settings page has tabs; a tab only submits its own fields.
		$tab   = isset( $raw['_tab'] ) ? sanitize_key( $raw['_tab'] ) : '';
		$clean = array();
		foreach ( Schema::settings() as $key => $field ) {
			$field_tab = isset( $field['tab'] ) ? $field['tab'] : 'general';
			if ( $tab && $tab !== $field_tab ) {
				$clean[ $key ] = isset( $previous[ $key ] ) ? $previous[ $key ] : null;
				continue;
			}
			$clean[ $key ] = Fields::sanitize( $field, isset( $raw[ $key ] ) ? $raw[ $key ] : null );
		}
		if ( ! $tab || 'general' === $tab ) {
			$clean['score_categories'] = self::validate_categories( $clean['score_categories'] );
			$clean['deposit_bands']    = $clean['deposit_bands'] ? array_values( array_unique( array_map( 'strval', array_map( 'absint', $clean['deposit_bands'] ) ) ) ) : null;
		}
		if ( ! $tab || 'directory' === $tab ) {
			$clean['directory_filters'] = self::validate_directory_filters( $clean['directory_filters'] );
		}

		return $clean;
	}

	/**
	 * Directory filter rows: each filter once. A duplicate row is dropped
	 * with a notice; filters missing from the list keep their default text.
	 *
	 * @param array|null $rows Sanitized repeater rows.
	 * @return array|null
	 */
	private static function validate_directory_filters( $rows ) {
		$seen  = array();
		$out   = array();
		$dupes = array();
		$keys  = Schema::directory_filter_keys();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$key = isset( $row['key'] ) ? (string) $row['key'] : '';
			if ( ! isset( $keys[ $key ] ) ) {
				continue;
			}
			if ( isset( $seen[ $key ] ) ) {
				$dupes[] = $keys[ $key ];
				continue;
			}
			$seen[ $key ] = true;
			$out[]        = $row;
		}
		if ( $dupes ) {
			/* translators: %s: filter names */
			add_settings_error( Repository::SETTINGS, 'fxt-filters', sprintf( __( 'Each filter can be listed once. Removed duplicate rows: %s.', 'fxt-core' ), implode( ', ', array_unique( $dupes ) ) ), 'warning' );
		}
		return $out ? $out : null;
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
		$tabs     = array(
			'general'   => __( 'General', 'fxt-core' ),
			'review'    => __( 'Review page', 'fxt-core' ),
			'directory' => __( 'Broker Reviews filters', 'fxt-core' ),
		);
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- tab navigation only.
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
		$tab = isset( $tabs[ $tab ] ) ? $tab : 'general';

		echo '<div class="wrap"><h1>' . esc_html__( 'FX Trading Today settings', 'fxt-core' ) . '</h1>';
		echo '<nav class="nav-tab-wrapper" aria-label="' . esc_attr__( 'Settings sections', 'fxt-core' ) . '">';
		foreach ( $tabs as $slug => $label ) {
			printf(
				'<a href="%s" class="nav-tab%s"%s>%s</a>',
				esc_url( add_query_arg( array( 'page' => 'fxt-settings', 'tab' => $slug ), admin_url( 'options-general.php' ) ) ),
				$slug === $tab ? ' nav-tab-active' : '',
				$slug === $tab ? ' aria-current="page"' : '',
				esc_html( $label )
			);
		}
		echo '</nav>';
		if ( 'directory' === $tab ) {
			echo '<p>' . esc_html__( 'Text of the filter panel and results on the Broker Reviews page. This only changes what visitors read: broker data, filter keys and filtering logic stay the same.', 'fxt-core' ) . '</p>';
		} elseif ( 'review' === $tab ) {
			echo '<p>' . esc_html__( 'Default texts of broker reviews that depend on the visitor\'s country. Per country: Broker Reviews > Countries. Per broker and country: Broker data: conditions by country on each review. When a broker does not accept residents of a country, review sections wrapped in a "Country eligibility section" block are hidden and the Trader verdict block shows the verdict below (or the country row\'s own verdict).', 'fxt-core' ) . '</p>';
		} else {
			echo '<p>' . esc_html__( 'Site-wide content used by the theme and blocks. Brand name and logo are set in Appearance > Customize > Site Identity.', 'fxt-core' ) . '</p>';
		}
		echo '<form method="post" action="options.php">';
		settings_fields( 'fxt_settings_group' );
		echo '<input type="hidden" name="' . esc_attr( Repository::SETTINGS ) . '[_tab]" value="' . esc_attr( $tab ) . '">';
		echo '<div class="fxt-fields fxt-fields--settings">';
		foreach ( Schema::settings() as $key => $field ) {
			if ( ( isset( $field['tab'] ) ? $field['tab'] : 'general' ) !== $tab ) {
				continue;
			}
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
		// Native media library for image fields (evidence screenshots and
		// galleries, author photo). The block editor does not always load it.
		$media = in_array( $hook, array( 'profile.php', 'user-edit.php' ), true ) || ( $screen && in_array( $screen->post_type, array( Content_Types::BROKER, Content_Types::EVIDENCE ), true ) && in_array( $hook, array( 'post.php', 'post-new.php' ), true ) );
		if ( $media ) {
			wp_enqueue_media();
		}
		wp_enqueue_script( 'fxt-core-admin', FXT_CORE_URL . 'assets/js/admin.js', $media ? array( 'media-editor' ) : array(), FXT_CORE_VERSION, true );
		wp_localize_script(
			'fxt-core-admin',
			'fxtCoreAdmin',
			array(
				'chooseImage'  => __( 'Choose image', 'fxt-core' ),
				'chooseImages' => __( 'Choose images', 'fxt-core' ),
				'useImage'     => __( 'Use this image', 'fxt-core' ),
				'useImages'    => __( 'Add to the evidence', 'fxt-core' ),
				'noMedia'      => __( 'The media library could not be loaded on this screen. Reload the page and try again.', 'fxt-core' ),
				'remove'       => __( 'Remove', 'fxt-core' ),
			)
		);
	}
}
