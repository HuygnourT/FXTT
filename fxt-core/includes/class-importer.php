<?php
/**
 * Demo content importer.
 *
 * Imports content, configuration and Gutenberg markup only. Presentation stays
 * in the theme. Every object it creates carries a `_fxt_demo_key` marker so the
 * import is idempotent (re-running updates instead of duplicating) and can be
 * removed cleanly.
 *
 * Sources (all inside this plugin):
 *   demo/data.json      markets, taxonomies, author, brokers, evidence, page and post manifests
 *   demo/pages/*.html   page content (core blocks; core/pattern references are expanded)
 *   demo/posts/*.html   article content
 *
 * @package FXT\Core
 */

namespace FXT\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Demo import / removal.
 */
final class Importer {

	const MARKER = '_fxt_demo_key';
	const PAGE   = 'fxt-demo';
	const ACTION = 'fxt_demo';

	/** @var string[] Log lines. */
	private $log = array();

	/** @var array<string,int> Page IDs by manifest key. */
	private $pages = array();

	/** @var array<string,int> Post IDs by "type:slug". */
	private $posts = array();

	/**
	 * Hooks.
	 */
	public static function init() {
		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
			add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle' ) );
			add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
		}
	}

	/* ---------------------------------------------------------------------
	 * Admin UI
	 * ------------------------------------------------------------------ */

	/**
	 * Appearance > Import Demo.
	 */
	public static function admin_menu() {
		add_theme_page(
			__( 'Import Demo Content', 'fxt-core' ),
			__( 'Import Demo', 'fxt-core' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'page' )
		);
	}

	/**
	 * Is the demo already imported?
	 *
	 * @return bool
	 */
	public static function is_imported() {
		return (bool) get_option( 'fxt_demo_imported' );
	}

	/**
	 * One-time hint after activation, until the demo is imported or dismissed.
	 */
	public static function notice() {
		if ( self::is_imported() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( $screen && 'appearance_page_' . self::PAGE === $screen->id ) {
			return;
		}
		printf(
			'<div class="notice notice-info"><p>%s <a class="button button-primary" href="%s">%s</a></p></div>',
			esc_html__( 'FX Trading Today: import the demo content to see the site as designed.', 'fxt-core' ),
			esc_url( admin_url( 'themes.php?page=' . self::PAGE ) ),
			esc_html__( 'Import demo', 'fxt-core' )
		);
	}

	/**
	 * Admin page.
	 */
	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$log = get_transient( 'fxt_demo_log_' . get_current_user_id() );
		delete_transient( 'fxt_demo_log_' . get_current_user_id() );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import Demo Content', 'fxt-core' ); ?></h1>
			<p><?php esc_html_e( 'Creates the sample brokers, test evidence, author profile, pages, articles, menus and settings shown in the design prototype. Styles are not imported: they come from the active theme.', 'fxt-core' ); ?></p>
			<ul class="fxt-demo-list">
				<li><?php esc_html_e( 'Safe to run more than once: existing demo items are updated, never duplicated.', 'fxt-core' ); ?></li>
				<li><?php esc_html_e( 'Your own content is never changed. The front page, menus and permalinks are only set when they are not configured yet (or when you tick the option below).', 'fxt-core' ); ?></li>
				<li><?php esc_html_e( 'All broker data is sample data. Ratings, entities, costs and test results are not verified research.', 'fxt-core' ); ?></li>
			</ul>

			<?php if ( $log ) : ?>
				<div class="notice notice-success inline"><p><strong><?php esc_html_e( 'Done.', 'fxt-core' ); ?></strong></p>
					<ul class="fxt-demo-list">
						<?php foreach ( (array) $log as $line ) : ?>
							<li><?php echo esc_html( $line ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( self::ACTION ); ?>
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
				<p>
					<label><input type="checkbox" name="fxt_force_setup" value="1"> <?php esc_html_e( 'Also replace the current front page, menus and reading settings', 'fxt-core' ); ?></label>
				</p>
				<p>
					<button class="button button-primary" type="submit" name="fxt_task" value="import"><?php esc_html_e( 'Import demo content', 'fxt-core' ); ?></button>
					<?php if ( self::is_imported() ) : ?>
						<button class="button button-link-delete" type="submit" name="fxt_task" value="remove" onclick="return window.confirm(this.dataset.confirm);" data-confirm="<?php esc_attr_e( 'Delete every demo item (brokers, evidence, pages, articles, menus, terms and the demo author)?', 'fxt-core' ); ?>"><?php esc_html_e( 'Remove demo content', 'fxt-core' ); ?></button>
					<?php endif; ?>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Form handler (admin-post.php).
	 */
	public static function handle() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'fxt-core' ), 403 );
		}
		check_admin_referer( self::ACTION );

		$task     = isset( $_POST['fxt_task'] ) ? sanitize_key( wp_unslash( $_POST['fxt_task'] ) ) : 'import';
		$force    = ! empty( $_POST['fxt_force_setup'] );
		$importer = new self();
		$log      = 'remove' === $task ? $importer->remove() : $importer->import( $force );

		set_transient( 'fxt_demo_log_' . get_current_user_id(), $log, 5 * MINUTE_IN_SECONDS );
		wp_safe_redirect( admin_url( 'themes.php?page=' . self::PAGE ) );
		exit;
	}

	/* ---------------------------------------------------------------------
	 * Import
	 * ------------------------------------------------------------------ */

	/**
	 * Run the full import.
	 *
	 * @param bool $force Replace front page, menus and reading settings.
	 * @return string[] Log.
	 */
	public function import( $force = false ) {
		$data = $this->data();
		if ( ! $data ) {
			return array( __( 'Demo data file is missing or invalid.', 'fxt-core' ) );
		}
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 120 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- long import.
		}
		wp_defer_term_counting( true );
		Repository::flush();

		$this->markets( $data['markets'] );
		Repository::flush(); // Market rows feed the broker field sanitizers.
		$this->terms( Content_Types::REGULATOR, $data['regulators'] );
		$this->terms( Content_Types::PLATFORM, $data['platforms'] );
		$this->terms( Content_Types::ACCOUNT_TYPE, $data['account_types'] );
		$author = $this->author( $data['author'] );
		$this->brokers( $data['brokers'], $author );
		$this->evidence( $data['evidence'], $author );
		$this->pages_create( $data['pages'], $author );
		$this->settings();
		$this->posts_import( $data['posts'], $author );
		$this->pages_fill( $data['pages'] );
		$this->menus( $data['markets'], $force );
		$this->reading( $force );

		wp_defer_term_counting( false );
		Repository::flush();
		update_option( 'fxt_demo_imported', time(), false );
		flush_rewrite_rules( false );

		$this->log[] = __( 'Import complete.', 'fxt-core' );
		return $this->log;
	}

	/**
	 * Decoded data.json.
	 *
	 * @return array|null
	 */
	private function data() {
		$file = FXT_CORE_DIR . 'demo/data.json';
		if ( ! is_readable( $file ) ) {
			return null;
		}
		$data = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
		return is_array( $data ) ? $data : null;
	}

	/**
	 * Find a demo post by marker.
	 *
	 * @param string $key       Marker.
	 * @param string $post_type Post type.
	 * @return int
	 */
	private function find_post( $key, $post_type ) {
		$ids = get_posts(
			array(
				'post_type'        => $post_type,
				'post_status'      => 'any',
				'meta_key'         => self::MARKER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- admin-only import.
				'meta_value'       => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- admin-only import.
				'fields'           => 'ids',
				'posts_per_page'   => 1,
				'suppress_filters' => true,
			)
		);
		return $ids ? (int) $ids[0] : 0;
	}

	/**
	 * Insert or update a post with a marker.
	 *
	 * @param string $key  Marker.
	 * @param array  $args wp_insert_post args.
	 * @return int Post ID (0 on failure).
	 */
	private function upsert_post( $key, array $args ) {
		$id = $this->find_post( $key, $args['post_type'] );
		if ( $id ) {
			$args['ID'] = $id;
		}
		$args = wp_slash( $args );
		$id   = $id ? wp_update_post( $args, true ) : wp_insert_post( $args, true );
		if ( is_wp_error( $id ) ) {
			$this->log[] = $id->get_error_message();
			return 0;
		}
		update_post_meta( $id, self::MARKER, $key );
		return (int) $id;
	}

	/**
	 * Write structured meta through the registered sanitizers.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $meta    Key => value.
	 * @param array $schema  Schema (only known keys are written).
	 */
	private function write_meta( $post_id, array $meta, array $schema ) {
		foreach ( $schema as $key => $field ) {
			$value = array_key_exists( $key, $meta ) ? Fields::sanitize( $field, $meta[ $key ] ) : null;
			if ( null === $value ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, wp_slash( $value ) );
			}
		}
	}

	/**
	 * Countries.
	 *
	 * @param array $markets Market rows.
	 */
	private function markets( array $markets ) {
		// The full country list is reference data (seeded by the plugin, never removed).
		Countries::sync();
		Repository::flush();
		foreach ( $markets as $market ) {
			$country = Repository::market( $market['code'] );
			if ( ! $country ) {
				continue;
			}
			// The demo only marks which countries are under research.
			update_term_meta( $country['term_id'], 'fxt_status', $market['status'] );
			update_term_meta( $country['term_id'], '_fxt_demo_status', 1 );
		}
		/* translators: 1: total countries, 2: researched countries */
		$this->log[] = sprintf( __( '%1$d countries available (%2$d marked as researched)', 'fxt-core' ), count( Repository::markets() ), count( $markets ) );
	}

	/**
	 * Simple taxonomy terms.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param array  $terms    Rows with name and optional slug.
	 */
	private function terms( $taxonomy, array $terms ) {
		foreach ( $terms as $term ) {
			$term_id = $this->upsert_term( $taxonomy, $term['name'], isset( $term['slug'] ) ? $term['slug'] : sanitize_title( $term['name'] ) );
			foreach ( $term_id && isset( $term['meta'] ) ? (array) $term['meta'] : array() as $key => $value ) {
				update_term_meta( $term_id, sanitize_key( $key ), $value );
			}
		}
	}

	/**
	 * Insert a term if missing and mark it.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param string $name     Name.
	 * @param string $slug     Slug.
	 * @return int
	 */
	private function upsert_term( $taxonomy, $name, $slug ) {
		$existing = get_term_by( 'slug', $slug, $taxonomy );
		if ( $existing ) {
			return (int) $existing->term_id;
		}
		$result = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );
		if ( is_wp_error( $result ) ) {
			$this->log[] = $result->get_error_message();
			return 0;
		}
		update_term_meta( $result['term_id'], self::MARKER, $taxonomy . ':' . $slug );
		return (int) $result['term_id'];
	}

	/**
	 * Demo author (simulated profile).
	 *
	 * @param array $author Author row.
	 * @return int User ID.
	 */
	private function author( array $author ) {
		$user = get_user_by( 'login', $author['login'] );
		$args = array(
			'display_name' => $author['display_name'],
			'nickname'     => $author['display_name'],
			'first_name'   => $author['display_name'],
			'description'  => $author['description'],
		);
		if ( $user ) {
			$args['ID'] = $user->ID;
			$user_id    = wp_update_user( $args );
		} else {
			$args['user_login'] = $author['login'];
			$args['user_pass']  = wp_generate_password( 32 );
			$args['user_email'] = $author['login'] . '@example.com';
			$args['role']       = 'author';
			$user_id            = wp_insert_user( $args );
			if ( ! is_wp_error( $user_id ) ) {
				update_user_meta( $user_id, self::MARKER, 'author:' . $author['login'] );
			}
		}
		if ( is_wp_error( $user_id ) ) {
			$this->log[] = $user_id->get_error_message();
			return get_current_user_id();
		}
		foreach ( Schema::author() as $key => $field ) {
			$value = isset( $author['meta'][ $key ] ) ? Fields::sanitize( $field, $author['meta'][ $key ] ) : null;
			if ( null !== $value ) {
				update_user_meta( $user_id, $key, $value );
			}
		}
		/* translators: %s: author name */
		$this->log[] = sprintf( __( 'Author profile: %s (simulated)', 'fxt-core' ), $author['display_name'] );
		return (int) $user_id;
	}

	/**
	 * Broker reviews.
	 *
	 * @param array $brokers Broker rows.
	 * @param int   $author  Author ID.
	 */
	private function brokers( array $brokers, $author ) {
		foreach ( $brokers as $i => $broker ) {
			$reviewed = ! empty( $broker['meta']['fxt_reviewed'] ) ? $broker['meta']['fxt_reviewed'] : '2026-09-01';
			$id       = $this->upsert_post(
				'broker:' . $broker['slug'],
				array(
					'post_type'    => Content_Types::BROKER,
					'post_status'  => 'publish',
					'post_title'   => $broker['title'],
					'post_name'    => $broker['slug'],
					'post_excerpt' => $broker['excerpt'],
					'post_content' => $broker['content'],
					'post_author'  => $author,
					'post_date'    => $reviewed . ' 09:00:00',
					'menu_order'   => $i,
				)
			);
			if ( ! $id ) {
				continue;
			}
			$this->posts[ 'broker:' . $broker['slug'] ] = $id;
			$this->write_meta( $id, $broker['meta'], Schema::broker() );
			wp_set_object_terms( $id, $broker['regulators'], Content_Types::REGULATOR );
			wp_set_object_terms( $id, $broker['platforms'], Content_Types::PLATFORM );
			wp_set_object_terms( $id, $broker['account_types'], Content_Types::ACCOUNT_TYPE );
		}
		/* translators: %d: number of brokers */
		$this->log[] = sprintf( __( '%d broker reviews', 'fxt-core' ), count( $brokers ) );
	}

	/**
	 * Test evidence.
	 *
	 * @param array $items  Evidence rows.
	 * @param int   $author Author ID.
	 */
	private function evidence( array $items, $author ) {
		foreach ( $items as $item ) {
			$broker_id = isset( $this->posts[ 'broker:' . $item['broker'] ] ) ? $this->posts[ 'broker:' . $item['broker'] ] : 0;
			$to        = ! empty( $item['meta']['fxt_period']['to'] ) ? $item['meta']['fxt_period']['to'] : '2026-09-10';
			$id        = $this->upsert_post(
				'evidence:' . $item['slug'],
				array(
					'post_type'    => Content_Types::EVIDENCE,
					'post_status'  => 'publish',
					'post_title'   => $item['title'],
					'post_name'    => $item['slug'],
					'post_excerpt' => $item['excerpt'],
					'post_content' => $item['content'],
					'post_author'  => $author,
					'post_date'    => $to . ' 18:00:00',
				)
			);
			if ( ! $id ) {
				continue;
			}
			$this->posts[ 'evidence:' . $item['slug'] ] = $id;
			$this->write_meta( $id, array_merge( $item['meta'], array( 'fxt_broker_id' => $broker_id ) ), Schema::evidence() );
		}
		/* translators: %d: number of evidence records */
		$this->log[] = sprintf( __( '%d test evidence records', 'fxt-core' ), count( $items ) );
	}

	/**
	 * Create pages first (empty) so their URLs exist for settings and links.
	 *
	 * @param array $pages  Page manifest.
	 * @param int   $author Author ID.
	 */
	private function pages_create( array $pages, $author ) {
		foreach ( $pages as $page ) {
			$id = $this->upsert_post(
				'page:' . $page['key'],
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $page['title'],
					'post_name'    => $page['slug'],
					'post_excerpt' => $page['excerpt'],
					'post_author'  => $author,
				)
			);
			if ( $id ) {
				$this->pages[ $page['key'] ] = $id;
				update_post_meta( $id, '_wp_page_template', $page['template'] );
			}
		}
	}

	/**
	 * Fill page content once settings (page links) are in place, so expanded
	 * patterns resolve their links.
	 *
	 * @param array $pages Page manifest.
	 */
	private function pages_fill( array $pages ) {
		foreach ( $pages as $page ) {
			if ( empty( $this->pages[ $page['key'] ] ) ) {
				continue;
			}
			$file = FXT_CORE_DIR . 'demo/pages/' . sanitize_file_name( $page['key'] ) . '.html';
			$html = is_readable( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
			wp_update_post(
				wp_slash(
					array(
						'ID'           => $this->pages[ $page['key'] ],
						'post_content' => $this->configure_blocks( $this->prepare( $html ) ),
					)
				)
			);
		}
		/* translators: %d: number of pages */
		$this->log[] = sprintf( __( '%d pages', 'fxt-core' ), count( $this->pages ) );
	}

	/**
	 * Articles.
	 *
	 * @param array $posts  Post manifest.
	 * @param int   $author Author ID.
	 */
	private function posts_import( array $posts, $author ) {
		foreach ( $posts as $post ) {
			$file = FXT_CORE_DIR . 'demo/posts/' . sanitize_file_name( $post['slug'] ) . '.html';
			$html = is_readable( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
			$cat  = $this->upsert_term( 'category', $post['category'], sanitize_title( $post['category'] ) );
			$id   = $this->upsert_post(
				'post:' . $post['slug'],
				array(
					'post_type'     => 'post',
					'post_status'   => 'publish',
					'post_title'    => $post['title'],
					'post_name'     => $post['slug'],
					'post_excerpt'  => $post['excerpt'],
					'post_content'  => $this->prepare( $html ),
					'post_author'   => $author,
					'post_date'     => $post['date'] . ' 08:00:00',
					'post_category' => $cat ? array( $cat ) : array(),
				)
			);
			if ( $id ) {
				$this->posts[ 'post:' . $post['slug'] ] = $id;
			}
		}
		/* translators: %d: number of articles */
		$this->log[] = sprintf( __( '%d articles', 'fxt-core' ), count( $posts ) );
	}

	/**
	 * Resolve link tokens and expand theme patterns into plain blocks, so the
	 * stored content does not depend on the theme staying active.
	 *
	 * Tokens: {{url:directory|compare|evidence|methodology|posts|home}} and
	 * {{post:type:slug}}.
	 *
	 * @param string $html Block markup.
	 * @return string
	 */
	private function prepare( $html ) {
		$html = preg_replace_callback(
			'/<!-- wp:pattern (\{.*?\}) \/-->/',
			static function ( $m ) {
				$attrs    = json_decode( $m[1], true );
				$registry = \WP_Block_Patterns_Registry::get_instance();
				if ( empty( $attrs['slug'] ) || ! $registry->is_registered( $attrs['slug'] ) ) {
					return $m[0];
				}
				$pattern = $registry->get_registered( $attrs['slug'] );
				return isset( $pattern['content'] ) ? $pattern['content'] : $m[0];
			},
			$html
		);
		$html = preg_replace_callback(
			'/\{\{url:([a-z]+)\}\}/',
			function ( $m ) {
				return esc_url( $this->url( $m[1] ) );
			},
			$html
		);
		return preg_replace_callback(
			'/\{\{post:([a-z]+):([a-z0-9-]+)\}\}/',
			function ( $m ) {
				$key = $m[1] . ':' . $m[2];
				return isset( $this->posts[ $key ] ) ? esc_url( get_permalink( $this->posts[ $key ] ) ) : esc_url( home_url( '/' ) );
			},
			$html
		);
	}

	/**
	 * Demo configuration of dynamic blocks that needs this site's IDs:
	 * homepage broker cards get an explicit, editable broker selection.
	 *
	 * @param string $html Block markup.
	 * @return string
	 */
	private function configure_blocks( $html ) {
		if ( false === strpos( $html, 'wp:fxt/broker-cards' ) ) {
			return $html;
		}
		$ids = array();
		foreach ( Repository::brokers() as $broker ) {
			if ( null !== $broker['score'] && count( $ids ) < 4 ) {
				$ids[] = $broker['id'];
			}
		}
		$walk = static function ( array $blocks ) use ( &$walk, $ids ) {
			foreach ( $blocks as $i => $block ) {
				if ( 'fxt/broker-cards' === $block['blockName'] && empty( $block['attrs']['brokers'] ) ) {
					$blocks[ $i ]['attrs']['brokers'] = $ids;
				}
				if ( ! empty( $block['innerBlocks'] ) ) {
					$blocks[ $i ]['innerBlocks'] = $walk( $block['innerBlocks'] );
				}
			}
			return $blocks;
		};
		return serialize_blocks( $walk( parse_blocks( $html ) ) );
	}

	/**
	 * URL of a demo page by key.
	 *
	 * @param string $key Page key.
	 * @return string
	 */
	private function url( $key ) {
		if ( 'home' === $key ) {
			return home_url( '/' );
		}
		return isset( $this->pages[ $key ] ) ? get_permalink( $this->pages[ $key ] ) : home_url( '/' );
	}

	/**
	 * Settings: keep anything the site owner already set, fill the rest.
	 */
	private function settings() {
		$current  = get_option( Repository::SETTINGS, array() );
		$current  = is_array( $current ) ? $current : array();
		$defaults = Schema::settings_defaults();
		$demo     = array(
			'banner_enabled'   => true,
			'banner_text'      => __( 'Prototype. Broker names are placeholders. Ratings, entities, regulators, costs and test results are sample data, not verified research.', 'fxt-core' ),
			'sample_labels'    => true,
			'default_market'   => 'VN',
			'header_cta_label' => __( 'Find a Broker', 'fxt-core' ),
			'header_cta_url'   => $this->url( 'directory' ),
			'footer_tagline'   => __( 'Independent Forex broker research for traders in Southeast Asia.', 'fxt-core' ),
			'social'           => array(),
		);
		$pages    = array();
		foreach ( array( 'directory', 'compare', 'evidence', 'methodology' ) as $key ) {
			$pages[ $key ] = isset( $this->pages[ $key ] ) ? $this->pages[ $key ] : null;
		}
		$settings          = array_merge( $defaults, $demo, array_filter( $current, static function ( $v ) {
			return null !== $v && '' !== $v && array() !== $v;
		} ) );
		// Keep the owner's page choices only while those pages still exist.
		$chosen = array_filter(
			isset( $current['pages'] ) ? (array) $current['pages'] : array(),
			static function ( $id ) {
				return $id && 'publish' === get_post_status( (int) $id );
			}
		);
		$settings['pages'] = array_merge( $pages, $chosen );
		update_option( Repository::SETTINGS, $settings );
		$this->log[] = __( 'Settings saved (Settings > FX Trading Today)', 'fxt-core' );
	}

	/**
	 * Menus for the theme locations.
	 *
	 * @param array $markets Market rows.
	 * @param bool  $force   Replace existing assignments.
	 */
	private function menus( array $markets, $force ) {
		$author_url = isset( $this->posts['post:why-the-same-broker-differs-by-country'] ) ? get_author_posts_url( (int) get_post_field( 'post_author', $this->posts['post:why-the-same-broker-differs-by-country'] ) ) : home_url( '/' );
		$dir        = $this->url( 'directory' );
		$method     = $this->url( 'methodology' );

		$countries = array();
		foreach ( $markets as $market ) {
			$countries[] = array( $market['name'], add_query_arg( 'country', $market['code'], $dir ) );
		}

		$menus = array(
			'primary'  => array(
				__( 'Primary', 'fxt-core' ),
				array(
					// Mega menu: every heading, link and label is a menu item (see theme inc/navigation.php).
					array( __( 'Broker Reviews', 'fxt-core' ), $dir, 'mega', array(
						array( __( 'Highest research scores', 'fxt-core' ), $dir, 'mega-auto-brokers', array(), __( 'All broker reviews', 'fxt-core' ) ),
						array( __( 'By account type', 'fxt-core' ), '#', '', array(
							array( __( 'Raw spread accounts', 'fxt-core' ), add_query_arg( 'account', 'raw', $dir ) ),
							array( __( 'Low minimum deposit', 'fxt-core' ), add_query_arg( 'deposit', '50', $dir ) ),
							array( __( 'MetaTrader 5 brokers', 'fxt-core' ), add_query_arg( 'platform', 'mt5', $dir ) ),
							array( __( 'TradingView brokers', 'fxt-core' ), add_query_arg( 'platform', 'tradingview', $dir ) ),
							array( __( 'Cent accounts', 'fxt-core' ), add_query_arg( 'account', 'cent', $dir ) ),
						) ),
						array( __( 'By country', 'fxt-core' ), $dir, 'mega-auto-countries', array(), __( 'All %d markets', 'fxt-core' ) ),
						array( __( 'How we research and score a broker', 'fxt-core' ), $method, 'mega-feature', array(), __( 'Before you read a review', 'fxt-core' ), __( 'Read the methodology', 'fxt-core' ) ),
					) ),
					array( __( 'Compare Brokers', 'fxt-core' ), $this->url( 'compare' ) ),
					array( __( 'Best Brokers', 'fxt-core' ), add_query_arg( 'sort', 'score', $dir ) ),
					array( __( 'Test Evidence', 'fxt-core' ), $this->url( 'evidence' ) ),
					array( __( 'Regulation', 'fxt-core' ), $method . '#regulatory' ),
					array( __( 'Methodology', 'fxt-core' ), $method ),
				),
			),
			'footer-1' => array(
				__( 'Brokers', 'fxt-core' ),
				array(
					array( __( 'Broker Reviews', 'fxt-core' ), $dir ),
					array( __( 'Compare Brokers', 'fxt-core' ), $this->url( 'compare' ) ),
					array( __( 'Best Brokers', 'fxt-core' ), add_query_arg( 'sort', 'score', $dir ) ),
					array( __( 'Test Evidence', 'fxt-core' ), $this->url( 'evidence' ) ),
				),
			),
			'footer-2' => array(
				__( 'Research', 'fxt-core' ),
				array(
					array( __( 'Research Methodology', 'fxt-core' ), $method ),
					array( __( 'Broker Testing Methodology', 'fxt-core' ), $method . '#testing' ),
					array( __( 'Regulation Framework', 'fxt-core' ), $method . '#regulatory' ),
					array( __( 'Review and Verification Process', 'fxt-core' ), $method . '#updates' ),
				),
			),
			'footer-3' => array( __( 'Countries', 'fxt-core' ), $countries ),
			'footer-4' => array(
				__( 'Company', 'fxt-core' ),
				array(
					array( __( 'About', 'fxt-core' ), $this->url( 'about' ) ),
					array( __( 'Meet the Reviewer', 'fxt-core' ), $author_url ),
					array( __( 'Editorial Independence', 'fxt-core' ), $method . '#independence' ),
					array( __( 'How We Make Money', 'fxt-core' ), $method . '#independence' ),
					array( __( 'Corrections and Updates', 'fxt-core' ), $method . '#updates' ),
					array( __( 'Contact', 'fxt-core' ), $this->url( 'contact' ) ),
				),
			),
			'footer-5' => array(
				__( 'Legal', 'fxt-core' ),
				array(
					array( __( 'Terms', 'fxt-core' ), $this->url( 'terms' ) ),
					array( __( 'Privacy', 'fxt-core' ), $this->url( 'privacy' ) ),
					array( __( 'Risk Disclosure', 'fxt-core' ), $this->url( 'risk' ) ),
					array( __( 'Affiliate Disclosure', 'fxt-core' ), $method . '#independence' ),
				),
			),
		);

		$locations = get_theme_mod( 'nav_menu_locations', array() );
		$locations = is_array( $locations ) ? $locations : array();
		foreach ( $menus as $location => $menu ) {
			$menu_id = $this->menu( 'FXT ' . $menu[0], $menu[1] );
			if ( $menu_id && ( $force || empty( $locations[ $location ] ) ) ) {
				$locations[ $location ] = $menu_id;
			}
		}
		set_theme_mod( 'nav_menu_locations', $locations );
		$this->log[] = __( 'Menus created and assigned to the header and footer', 'fxt-core' );
	}

	/**
	 * Create (or rebuild) one demo menu.
	 *
	 * @param string $name  Menu name.
	 * @param array  $items [ label, url, class?, children?, description?, title attribute? ].
	 * @return int
	 */
	private function menu( $name, array $items ) {
		$menu = wp_get_nav_menu_object( $name );
		if ( $menu ) {
			foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $item ) {
				wp_delete_post( $item->ID, true );
			}
			$menu_id = (int) $menu->term_id;
		} else {
			$menu_id = wp_create_nav_menu( $name );
			if ( is_wp_error( $menu_id ) ) {
				return 0;
			}
			update_term_meta( $menu_id, self::MARKER, 'menu:' . sanitize_title( $name ) );
		}
		$add = static function ( $item, $parent ) use ( $menu_id, &$add ) {
			$id = wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => $item[0],
					'menu-item-url'       => $item[1],
					'menu-item-classes'   => isset( $item[2] ) ? $item[2] : '',
					'menu-item-description' => isset( $item[4] ) ? $item[4] : '',
					'menu-item-attr-title'  => isset( $item[5] ) ? $item[5] : '',
					'menu-item-parent-id' => $parent,
					'menu-item-status'    => 'publish',
					'menu-item-type'      => 'custom',
				)
			);
			if ( ! is_wp_error( $id ) && ! empty( $item[3] ) ) {
				foreach ( $item[3] as $child ) {
					$add( $child, $id );
				}
			}
		};
		foreach ( $items as $item ) {
			$add( $item, 0 );
		}
		return (int) $menu_id;
	}

	/**
	 * Static front page, posts page and pretty permalinks.
	 *
	 * @param bool $force Replace existing reading settings.
	 */
	private function reading( $force ) {
		if ( $force || 'page' !== get_option( 'show_on_front' ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $this->pages['home'] );
			update_option( 'page_for_posts', $this->pages['posts'] );
			$this->log[] = __( 'Front page set to "Home", articles listed on "Latest research"', 'fxt-core' );
		}
		// The default "Hello world!" post would otherwise lead the article lists.
		$hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
		if ( $hello && $hello->post_date === $hello->post_modified && ! get_post_meta( $hello->ID, self::MARKER, true ) ) {
			wp_trash_post( $hello->ID );
			$this->log[] = __( 'Moved the default "Hello world!" post to the trash', 'fxt-core' );
		}
		if ( ! get_option( 'permalink_structure' ) ) {
			update_option( 'permalink_structure', '/%postname%/' );
			$this->log[] = __( 'Permalinks set to "Post name"', 'fxt-core' );
		}
	}

	/* ---------------------------------------------------------------------
	 * Removal
	 * ------------------------------------------------------------------ */

	/**
	 * Delete every object carrying the demo marker.
	 *
	 * @return string[] Log.
	 */
	public function remove() {
		$count = 0;
		$ids   = get_posts(
			array(
				'post_type'      => array( Content_Types::BROKER, Content_Types::EVIDENCE, 'page', 'post' ),
				'post_status'    => 'any',
				'meta_key'       => self::MARKER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- admin-only.
				'fields'         => 'ids',
				'posts_per_page' => -1,
			)
		);
		foreach ( $ids as $id ) {
			if ( (int) get_option( 'page_on_front' ) === (int) $id ) {
				update_option( 'show_on_front', 'posts' );
				update_option( 'page_on_front', 0 );
			}
			if ( (int) get_option( 'page_for_posts' ) === (int) $id ) {
				update_option( 'page_for_posts', 0 );
			}
			$count += wp_delete_post( $id, true ) ? 1 : 0;
		}

		$taxonomies = array( Content_Types::REGULATOR, Content_Types::PLATFORM, Content_Types::ACCOUNT_TYPE, 'category', 'nav_menu' );
		$terms      = get_terms(
			array(
				'taxonomy'   => $taxonomies,
				'hide_empty' => false,
				'meta_key'   => self::MARKER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- admin-only.
			)
		);
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( 'nav_menu' === $term->taxonomy ) {
					wp_delete_nav_menu( $term->term_id );
				} else {
					wp_delete_term( $term->term_id, $term->taxonomy );
				}
				++$count;
			}
		}

		$users = get_users(
			array(
				'meta_key' => self::MARKER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- admin-only.
				'fields'   => 'ID',
			)
		);
		if ( $users ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			foreach ( $users as $user_id ) {
				wp_delete_user( (int) $user_id, get_current_user_id() ? get_current_user_id() : null );
				++$count;
			}
		}

		// Links into deleted demo pages would now point nowhere.
		$settings = get_option( Repository::SETTINGS, array() );
		if ( is_array( $settings ) ) {
			$settings['pages'] = array();
			if ( ! empty( $settings['header_cta_url'] ) && ! url_to_postid( $settings['header_cta_url'] ) ) {
				$settings['header_cta_url'] = '';
			}
			update_option( Repository::SETTINGS, $settings );
		}

		// Research status set by the demo is cleared; the countries themselves stay.
		$flagged = get_terms(
			array(
				'taxonomy'   => Content_Types::MARKET,
				'hide_empty' => false,
				'meta_key'   => '_fxt_demo_status', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- admin-only.
				'fields'     => 'ids',
			)
		);
		foreach ( is_wp_error( $flagged ) ? array() : $flagged as $term_id ) {
			delete_term_meta( $term_id, 'fxt_status' );
			delete_term_meta( $term_id, '_fxt_demo_status' );
		}

		delete_option( 'fxt_demo_imported' );
		Repository::flush();
		/* translators: %d: number of deleted items */
		return array( sprintf( __( 'Removed %d demo items. Settings were kept.', 'fxt-core' ), $count ) );
	}
}
