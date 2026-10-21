<?php
/**
 * Dynamic blocks: registration, shared request parsing and the editor script.
 *
 * Every block is server-rendered (block.json + render.php). Editors see a live
 * preview through ServerSideRender; no build step is required.
 *
 * @package FXT\Core
 */

namespace FXT\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Block registry and helpers used by render.php files and the REST renderer.
 */
final class Blocks {

	const BLOCKS = array( 'country-brokers', 'broker-cards', 'broker-compare', 'broker-data', 'evidence-data', 'score-weights', 'broker-directory', 'evidence-list', 'evidence-snapshot', 'post-byline', 'eligibility-section', 'trader-verdict', 'source-label', 'cost-calculator' );

	/** @var bool Whether the evidence viewer markup still has to be printed. */
	private static $viewer_pending = false;

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'block_categories_all', array( __CLASS__, 'category' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'editor_ui_style' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'label_format' ) );
		// Inline source labels: final markup on output (also for REST refreshes).
		add_filter( 'the_content', 'fxt_core_label_markup', 20 );
		// Table blocks: a row whose only content is a source label spans the table.
		add_filter( 'render_block_core/table', array( __CLASS__, 'table_label_rows' ) );
	}

	/**
	 * Source labels between table rows (core Table block). A row with one
	 * filled cell that contains a source label, all other cells empty, is
	 * rendered as one full-width cell, so editors can put a label between
	 * any two rows from Gutenberg. Other rows and the table styles are
	 * unchanged.
	 *
	 * @param string $html Block HTML.
	 * @return string
	 */
	public static function table_label_rows( $html ) {
		if ( false === strpos( (string) $html, 'fxt-source-label' ) ) {
			return $html;
		}
		return preg_replace_callback(
			'#<tr(\s[^>]*)?>(.*?)</tr>#s',
			static function ( $row ) {
				if ( ! preg_match_all( '#<(td|th)\b([^>]*)>(.*?)</\1>#s', $row[2], $cells, PREG_SET_ORDER ) || count( $cells ) < 2 ) {
					return $row[0];
				}
				$span   = 0;
				$filled = array();
				foreach ( $cells as $cell ) {
					$span += preg_match( '/colspan="(\d+)"/', $cell[2], $c ) ? max( 1, (int) $c[1] ) : 1;
					$text  = trim( str_replace( array( '&nbsp;', "\xc2\xa0" ), ' ', wp_strip_all_tags( $cell[3] ) ) );
					if ( '' !== $text ) {
						$filled[] = $cell;
					}
				}
				if ( 1 !== count( $filled ) || false === strpos( $filled[0][3], 'fxt-source-label' ) ) {
					return $row[0];
				}
				return '<tr class="fxt-source-row"><td colspan="' . (int) $span . '">' . $filled[0][3] . '</td></tr>';
			},
			$html
		);
	}

	/**
	 * Register scripts first, then every block from its block.json.
	 */
	public static function register() {
		wp_register_script(
			'fxt-core-blocks',
			FXT_CORE_URL . 'assets/js/blocks-editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n', 'wp-data' ),
			FXT_CORE_VERSION,
			true
		);
		wp_set_script_translations( 'fxt-core-blocks', 'fxt-core', FXT_CORE_DIR . 'languages' );
		wp_add_inline_script( 'fxt-core-blocks', 'window.fxtCoreBlocks = ' . wp_json_encode( array( 'compare' => self::compare_options(), 'labelKinds' => Schema::label_kinds() ) ) . ';', 'before' );

		Assets::register_frontend_scripts();

		foreach ( self::BLOCKS as $block ) {
			register_block_type( FXT_CORE_DIR . 'blocks/' . $block );
		}
	}

	/**
	 * Styles for the block sidebar controls (broker picker, checklists).
	 */
	public static function editor_ui_style() {
		wp_enqueue_style( 'fxt-core-editor-ui', FXT_CORE_URL . 'assets/css/editor-ui.css', array( 'wp-components' ), FXT_CORE_VERSION );
	}

	/**
	 * Block inserter category.
	 *
	 * @param array $categories Existing categories.
	 * @return array
	 */
	public static function category( $categories ) {
		array_unshift(
			$categories,
			array(
				'slug'  => 'fxt',
				'title' => __( 'FX Trading Today', 'fxt-core' ),
			)
		);
		return $categories;
	}

	/* ---------------------------------------------------------------------
	 * Request parsing (shared by render.php and the REST renderer)
	 * ------------------------------------------------------------------ */

	/**
	 * Current page URL without query args (base for links and form actions).
	 *
	 * @return string
	 */
	public static function base_url() {
		$id = get_queried_object_id();
		if ( $id && ! is_author() ) {
			return get_permalink( $id );
		}
		return home_url( '/' );
	}

	/**
	 * Only accept same-site URLs from the client.
	 *
	 * @param string $url Candidate URL.
	 * @return string
	 */
	public static function safe_base( $url ) {
		$url = esc_url_raw( (string) $url );
		return wp_validate_redirect( $url, home_url( '/' ) );
	}

	/**
	 * Parse a broker list from a comma string or an array of slugs.
	 *
	 * @param mixed $raw Raw value.
	 * @return string[]
	 */
	public static function parse_slugs( $raw ) {
		$items = is_array( $raw ) ? $raw : explode( ',', (string) $raw );
		return array_values( array_unique( array_filter( array_map( 'sanitize_title', array_map( 'trim', $items ) ) ) ) );
	}

	/**
	 * Comparison selection: URL (?brokers=, ?remove=) wins over block defaults.
	 *
	 * @param array $attributes Block attributes.
	 * @return array { selected: string[], all: bool }
	 */
	public static function compare_state( array $attributes ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only view state.
		$selected = isset( $_GET['brokers'] ) ? self::parse_slugs( wp_unslash( $_GET['brokers'] ) ) : self::parse_slugs( $attributes['brokers'] );
		if ( isset( $_GET['remove'] ) ) {
			$index = absint( $_GET['remove'] );
			unset( $selected[ $index ] );
			$selected = array_values( $selected );
		}
		$all = ! empty( $_GET['all'] );
		// phpcs:enable
		return array(
			'selected' => array_slice( $selected, 0, max( 2, min( 4, (int) $attributes['slots'] ) ) ),
			'all'      => $all,
		);
	}

	/**
	 * Broker cards arguments (block attributes or REST args), sanitized.
	 *
	 * @param array $raw Raw values.
	 * @return array { count: int, brokers: int[] }
	 */
	public static function card_args( array $raw ) {
		return array(
			'count'   => max( 1, min( 12, isset( $raw['count'] ) ? (int) $raw['count'] : 4 ) ),
			'brokers' => array_values( array_filter( array_map( 'absint', isset( $raw['brokers'] ) ? (array) $raw['brokers'] : array() ) ) ),
		);
	}

	/**
	 * Country hero arguments, sanitized.
	 *
	 * @param array $raw Raw values.
	 * @return array { limit: int, orderBy: string }
	 */
	public static function hero_args( array $raw ) {
		return array(
			'limit'   => max( 1, min( 8, isset( $raw['limit'] ) ? (int) $raw['limit'] : 4 ) ),
			'orderBy' => isset( $raw['orderBy'] ) && 'manual' === $raw['orderBy'] ? 'manual' : 'score',
		);
	}

	/**
	 * Evidence index arguments, sanitized.
	 *
	 * @param array $raw Raw values.
	 * @return array { scope: string, showPending: bool, publishedTitle: string, pendingTitle: string }
	 */
	public static function evidence_list_args( array $raw ) {
		return array(
			'scope'          => isset( $raw['scope'] ) && 'country' === $raw['scope'] ? 'country' : 'all',
			'showPending'    => ! isset( $raw['showPending'] ) || ! empty( $raw['showPending'] ),
			'publishedTitle' => isset( $raw['publishedTitle'] ) ? sanitize_text_field( (string) $raw['publishedTitle'] ) : '',
			'pendingTitle'   => isset( $raw['pendingTitle'] ) ? sanitize_text_field( (string) $raw['pendingTitle'] ) : '',
		);
	}

	/**
	 * Comparison row options: preview row keys and full-table group keys.
	 *
	 * @return array{rows: array<string,string>, groups: array<string,string>}
	 */
	public static function compare_options() {
		return array(
			'rows'   => array(
				'score'        => __( 'Research score', 'fxt-core' ),
				'availability' => __( 'Availability in the selected country', 'fxt-core' ),
				'regulator'    => __( 'Regulator for the selected country', 'fxt-core' ),
				'deposit'      => __( 'Minimum deposit', 'fxt-core' ),
				'cost'         => __( 'Typical EUR/USD cost', 'fxt-core' ),
				'platforms'    => __( 'Platforms', 'fxt-core' ),
				'local'        => __( 'Local payments', 'fxt-core' ),
				'researched'   => __( 'Countries researched', 'fxt-core' ),
			),
			'groups' => array(
				'overview'   => __( 'Overview', 'fxt-core' ),
				'regulation' => __( 'Regulation', 'fxt-core' ),
				'costs'      => __( 'Trading costs', 'fxt-core' ),
				'trading'    => __( 'Trading', 'fxt-core' ),
				'platforms'  => __( 'Platforms', 'fxt-core' ),
				'accounts'   => __( 'Accounts', 'fxt-core' ),
				'payments'   => __( 'Payments', 'fxt-core' ),
				'research'   => __( 'Research', 'fxt-core' ),
			),
		);
	}

	/**
	 * Keep only known keys, in the given order; empty means "all".
	 *
	 * @param mixed  $raw  Raw list (array or comma string).
	 * @param string $type rows|groups.
	 * @return string[]
	 */
	public static function compare_keys( $raw, $type ) {
		$known = self::compare_options()[ $type ];
		$keys  = is_array( $raw ) ? $raw : explode( ',', (string) $raw );
		$keys  = array_values( array_intersect( array_map( 'sanitize_key', $keys ), array_keys( $known ) ) );
		return $keys ? $keys : array_keys( $known );
	}

	/**
	 * Editor: inline "Source label" format for every rich text.
	 */
	public static function label_format() {
		wp_enqueue_script( 'fxt-core-label-format', FXT_CORE_URL . 'assets/js/source-label-format.js', array( 'wp-rich-text', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n' ), FXT_CORE_VERSION, true );
		wp_add_inline_script( 'fxt-core-label-format', 'window.fxtCoreLabels = ' . wp_json_encode( array( 'kinds' => Schema::label_kinds() ) ) . ';', 'before' );
		wp_set_script_translations( 'fxt-core-label-format', 'fxt-core', FXT_CORE_DIR . 'languages' );
	}

	/**
	 * Read directory filters from a request. The country is the directory's
	 * own filter (query parameter "market", so it never collides with the
	 * site-wide "country" preference): it defaults to no country, the
	 * visitor's saved country is not applied here, and only countries
	 * offered by the filter are accepted.
	 *
	 * @param array $source $_GET or REST args.
	 * @return array
	 */
	public static function directory_filters( array $source ) {
		$keys    = array( 'q', 'market', 'availability', 'regulator', 'platform', 'deposit', 'account', 'status' );
		$filters = array();
		foreach ( $keys as $key ) {
			$filters[ $key ] = isset( $source[ $key ] ) && is_scalar( $source[ $key ] ) ? sanitize_text_field( wp_unslash( (string) $source[ $key ] ) ) : '';
		}
		$filters['deposit'] = '' === $filters['deposit'] ? '' : (string) absint( $filters['deposit'] );

		$country           = strtoupper( $filters['market'] );
		$filters['market'] = in_array( $country, wp_list_pluck( Repository::directory_markets(), 'code' ), true ) ? $country : '';
		if ( '' === $filters['market'] ) {
			$filters['availability'] = ''; // Availability is per country.
		}

		// A hidden filter is never applied, even from a shared URL.
		foreach ( Repository::setting( 'directory_filters' ) as $row ) {
			if ( ! empty( $row['hidden'] ) ) {
				$filters[ self::directory_param( $row['key'] ) ] = '';
			}
		}

		$sort            = isset( $source['sort'] ) ? sanitize_key( $source['sort'] ) : 'score';
		$filters['sort'] = in_array( $sort, array( 'score', 'recent', 'deposit' ), true ) ? $sort : 'score';
		return $filters;
	}

	/**
	 * Directory filters as configured in Settings > FX Trading Today >
	 * Broker Reviews filters, in display order. Labels are display text; the
	 * key is the query parameter and the filtering logic never depends on
	 * the label.
	 *
	 * @param bool $include_hidden Also return hidden filters.
	 * @return array key => { label, placeholder, description, hidden, options }
	 */
	public static function directory_fields( $include_hidden = false ) {
		$term_options = static function ( $taxonomy ) {
			$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
			$out   = array();
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$out[ $term->slug ] = $term->name;
				}
			}
			return $out;
		};
		$names = Repository::setting( 'directory_options' );

		$deposit = array();
		foreach ( (array) Repository::setting( 'deposit_bands' ) as $amount ) {
			$amount                       = absint( $amount );
			$deposit[ (string) $amount ] = 0 === $amount ? $names['deposit_zero'] : strtr( $names['deposit_band'], array( '{amount}' => '$' . number_format_i18n( $amount ) ) );
		}
		$countries = array();
		foreach ( Repository::directory_markets() as $market ) {
			$countries[ $market['code'] ] = $market['name'];
		}

		$options = array(
			'country'      => $countries,
			'availability' => array(
				'yes'        => $names['availability_yes'],
				'restricted' => $names['availability_restricted'],
				'pending'    => $names['availability_pending'],
			),
			'regulator'    => $term_options( Content_Types::REGULATOR ),
			'platform'     => $term_options( Content_Types::PLATFORM ),
			'deposit'      => $deposit,
			'account'      => $term_options( Content_Types::ACCOUNT_TYPE ),
			'status'       => array(
				'published' => $names['status_published'],
				'updating'  => $names['status_updating'],
				'pending'   => $names['status_pending'],
			),
		);

		$out = array();
		foreach ( Repository::setting( 'directory_filters' ) as $row ) {
			if ( ! $include_hidden && ! empty( $row['hidden'] ) ) {
				continue;
			}
			$row['options'] = $options[ $row['key'] ];
			$out[ self::directory_param( $row['key'] ) ] = $row;
		}
		return $out;
	}

	/**
	 * Query parameter of a directory filter. The "country" filter uses
	 * "market" so it never collides with the site-wide ?country= preference.
	 *
	 * @param string $key Filter key (settings).
	 * @return string
	 */
	public static function directory_param( $key ) {
		return 'country' === $key ? 'market' : $key;
	}

	/**
	 * Apply directory filters and sort.
	 *
	 * @param array  $filters Sanitized filters.
	 * @param string $code    Market code.
	 * @return array[]
	 */
	public static function filter_brokers( array $filters, $code ) {
		$brokers = array_filter(
			Repository::brokers(),
			static function ( $b ) use ( $filters, $code ) {
				if ( '' !== $filters['q'] && false === mb_stripos( $b['name'], $filters['q'] ) ) {
					return false;
				}
				if ( '' !== $code && '' !== $filters['availability'] && Repository::availability( $b, $code ) !== $filters['availability'] ) {
					return false;
				}
				if ( '' !== $filters['regulator'] ) {
					if ( ! in_array( $filters['regulator'], $b['regulator_slugs'], true ) ) {
						return false;
					}
				}
				if ( '' !== $filters['platform'] && ! isset( $b['platforms'][ $filters['platform'] ] ) ) {
					return false;
				}
				if ( '' !== $filters['deposit'] && ( null === $b['min_deposit'] || $b['min_deposit'] > (float) $filters['deposit'] ) ) {
					return false;
				}
				if ( '' !== $filters['account'] && ! in_array( $filters['account'], $b['account_types'], true ) ) {
					return false;
				}
				if ( '' !== $filters['status'] && $b['status'] !== $filters['status'] ) {
					return false;
				}
				return true;
			}
		);

		$sorters = array(
			'score'   => static function ( $a, $b ) {
				return ( null === $b['score'] ? -1 : $b['score'] ) <=> ( null === $a['score'] ? -1 : $a['score'] );
			},
			'recent'  => static function ( $a, $b ) {
				return strcmp( (string) $b['reviewed'], (string) $a['reviewed'] );
			},
			'deposit' => static function ( $a, $b ) {
				return ( null === $a['min_deposit'] ? PHP_INT_MAX : $a['min_deposit'] ) <=> ( null === $b['min_deposit'] ? PHP_INT_MAX : $b['min_deposit'] );
			},
		);
		usort( $brokers, $sorters[ $filters['sort'] ] );
		return $brokers;
	}

	/**
	 * Print the evidence viewer once per page, after the content.
	 *
	 * @param array $evidence Evidence record.
	 */
	public static function queue_viewer( array $evidence ) {
		if ( self::$viewer_pending ) {
			return;
		}
		self::$viewer_pending = true;
		wp_enqueue_script( 'fxt-core-evidence' );
		add_action(
			'wp_footer',
			static function () use ( $evidence ) {
				fxt_core_view( 'evidence/viewer', array( 'evidence' => $evidence ) );
			},
			5
		);
	}
}
