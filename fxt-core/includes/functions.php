<?php
/**
 * Public template API. Themes call these functions; they never touch meta keys directly.
 * Every function returns escaped HTML (or plain data) and is safe to echo.
 *
 * @package FXT\Core
 */

defined( 'ABSPATH' ) || exit;

use FXT\Core\Repository;
use FXT\Core\Schema;

/* -------------------------------------------------------------------------
 * Views
 * ---------------------------------------------------------------------- */

/**
 * Render a view. A theme can override any view by adding fxt-core/{view}.php.
 *
 * @param string $view View name, e.g. "broker/regulation".
 * @param array  $args Variables available to the view as $args.
 */
function fxt_core_view( $view, array $args = array() ) {
	$view  = trim( preg_replace( '#[^a-z0-9/_-]#', '', strtolower( $view ) ), '/' );
	$theme = locate_template( 'fxt-core/' . $view . '.php' );
	$file  = $theme ? $theme : FXT_CORE_DIR . 'views/' . $view . '.php';
	if ( is_readable( $file ) ) {
		( static function () use ( $file, $args ) {
			include $file;
		} )();
	}
}

/**
 * Render a view to a string.
 *
 * @param string $view View name.
 * @param array  $args View variables.
 * @return string
 */
function fxt_core_get_view( $view, array $args = array() ) {
	ob_start();
	fxt_core_view( $view, $args );
	return (string) ob_get_clean();
}

/* -------------------------------------------------------------------------
 * Data access shortcuts
 * ---------------------------------------------------------------------- */

/**
 * Normalized broker for a post (defaults to the current post).
 *
 * @param int|WP_Post|null $post Post.
 * @return array|null
 */
function fxt_core_broker( $post = null ) {
	return Repository::broker( $post ? $post : get_post() );
}

/**
 * Author profile.
 *
 * @param int $user_id User ID.
 * @return array|null
 */
function fxt_core_author( $user_id ) {
	return Repository::author( $user_id );
}

/**
 * The visitor's market (ISO code).
 *
 * @return string
 */
function fxt_core_current_market() {
	return Repository::current_market();
}

/**
 * Market record by code.
 *
 * @param string $code ISO code.
 * @return array|null
 */
function fxt_core_market( $code ) {
	return Repository::market( $code );
}

/**
 * Setting value.
 *
 * @param string $key Setting key.
 * @return mixed
 */
function fxt_core_setting( $key ) {
	return Repository::setting( $key );
}

/**
 * URL of a key page (directory, compare, evidence, methodology).
 *
 * @param string $key Page key.
 * @return string
 */
function fxt_core_page_url( $key ) {
	return Repository::page_url( $key );
}

/**
 * Comparison URL for a set of brokers.
 *
 * @param string[] $slugs Broker slugs.
 * @param bool     $all   Compare every broker.
 * @return string
 */
function fxt_core_compare_url( array $slugs, $all = false ) {
	$base = fxt_core_page_url( 'compare' );
	if ( ! $base ) {
		return '';
	}
	$args = array();
	if ( $slugs ) {
		$args['brokers'] = implode( ',', array_map( 'sanitize_title', $slugs ) );
	}
	if ( $all ) {
		$args['all'] = 1;
	}
	return add_query_arg( $args, $base );
}

/* -------------------------------------------------------------------------
 * Formatting
 * ---------------------------------------------------------------------- */

/**
 * "2026-09-12" -> "12 Sep 2026" (format filterable). Returns plain text: escape on output.
 *
 * @param string $ymd Date.
 * @return string
 */
function fxt_core_date( $ymd ) {
	if ( ! $ymd ) {
		return __( 'TBD', 'fxt-core' );
	}
	$timestamp = strtotime( $ymd . ' 12:00:00' );
	return $timestamp ? date_i18n( apply_filters( 'fxt_core_date_format', 'd M Y' ), $timestamp ) : (string) $ymd;
}

/**
 * "2026-09-12T09:14" -> "12 Sep 2026, 09:14".
 *
 * @param string $iso Date time.
 * @return string
 */
function fxt_core_datetime( $iso ) {
	if ( ! $iso ) {
		return __( 'TBD', 'fxt-core' );
	}
	return fxt_core_date( substr( $iso, 0, 10 ) ) . ', ' . substr( $iso, 11, 5 );
}

/**
 * Minimum deposit label.
 *
 * @param array $broker Normalized broker.
 * @return string
 */
function fxt_core_min_deposit( array $broker ) {
	return null === $broker['min_deposit'] ? __( 'TBD', 'fxt-core' ) : '$' . number_format_i18n( $broker['min_deposit'] );
}

/**
 * Score label ("4.5" or "Pending").
 *
 * @param array $broker Normalized broker.
 * @return string
 */
function fxt_core_score_label( array $broker ) {
	return null === $broker['score'] ? __( 'Pending', 'fxt-core' ) : number_format_i18n( $broker['score'], 1 );
}

/**
 * Platform names of a broker, in the order of Broker Reviews > Platforms.
 *
 * @param array $broker  Normalized broker.
 * @param bool  $compact Only platforms marked "Show in compact lists".
 * @return string[]
 */
function fxt_core_platform_names( array $broker, $compact = false ) {
	$names = array();
	foreach ( Repository::terms( FXT\Core\Content_Types::PLATFORM ) as $term ) {
		if ( isset( $broker['platforms'][ $term['slug'] ] ) && ( ! $compact || $term['compact'] ) ) {
			$names[] = $term['name'];
		}
	}
	return $names;
}

/**
 * Estimated reading time in minutes (data blocks count as ~120 words each).
 *
 * @param WP_Post|int|null $post Post.
 * @return int
 */
function fxt_core_reading_time( $post = null ) {
	$post  = get_post( $post );
	$words = $post ? str_word_count( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) ) : 0;
	$words += $post ? 120 * substr_count( $post->post_content, '<!-- wp:fxt/' ) : 0;
	return max( 1, (int) round( $words / 220 ) );
}

/* -------------------------------------------------------------------------
 * Markup atoms (all escaped)
 * ---------------------------------------------------------------------- */

/**
 * SVG icon from the sprite.
 *
 * @param string $name  Icon id without "i-".
 * @param string $class Extra classes.
 * @return string
 */
function fxt_core_icon( $name, $class = '' ) {
	return sprintf(
		'<svg class="icon %s" aria-hidden="true" focusable="false"><use href="#i-%s"></use></svg>',
		esc_attr( $class ),
		esc_attr( sanitize_key( $name ) )
	);
}

/**
 * "Sample data" tag; empty when sample labels are turned off in settings.
 *
 * @param string $text Label text.
 * @param bool   $dark On dark background.
 * @return string
 */
function fxt_core_sample( $text = '', $dark = false ) {
	if ( ! Repository::setting( 'sample_labels' ) ) {
		return '';
	}
	return sprintf(
		'<span class="tag-sample%s">%s</span>',
		$dark ? ' tag-sample--on-dark' : '',
		esc_html( $text ? $text : __( 'Sample data', 'fxt-core' ) )
	);
}

/**
 * Status / availability / test badge.
 *
 * @param string $kind  status|availability|test.
 * @param string $value Raw value.
 * @param string $extra Visually hidden suffix (e.g. " in Vietnam").
 * @return string
 */
function fxt_core_badge( $kind, $value, $extra = '' ) {
	switch ( $kind ) {
		case 'status':
			$labels  = Schema::review_status();
			$classes = array( 'published' => 'published', 'updating' => 'progress', 'pending' => 'pending' );
			$class   = isset( $classes[ $value ] ) ? $classes[ $value ] : 'pending';
			break;
		case 'availability':
			$labels = Schema::availability();
			$value  = isset( $labels[ $value ] ) ? $value : 'pending';
			$class  = 'avail-' . $value;
			break;
		default:
			$labels = Schema::test_status();
			$value  = isset( $labels[ $value ] ) ? $value : '';
			$class  = 'test-' . ( $value ? $value : 'none' );
	}
	$label = isset( $labels[ $value ] ) ? $labels[ $value ] : reset( $labels );
	return sprintf(
		'<span class="badge badge--%s">%s%s</span>',
		esc_attr( $class ),
		esc_html( $label ),
		$extra ? '<span class="u-visually-hidden">' . esc_html( $extra ) . '</span>' : ''
	);
}

/**
 * Score element.
 *
 * @param array $broker Normalized broker.
 * @return string
 */
function fxt_core_score( array $broker ) {
	if ( null === $broker['score'] ) {
		return '<span class="score score--pending">' . esc_html__( 'Pending', 'fxt-core' ) . '</span>';
	}
	return sprintf( '<span class="score">%s<span class="score__scale">/5</span></span>', esc_html( number_format_i18n( $broker['score'], 1 ) ) );
}

/**
 * Logo (featured image) or monogram.
 *
 * @param array  $broker Normalized broker.
 * @param string $size   '' or 'sm'.
 * @return string
 */
function fxt_core_logo( array $broker, $size = '' ) {
	if ( $broker['logo_id'] ) {
		return wp_get_attachment_image(
			$broker['logo_id'],
			'thumbnail',
			false,
			array(
				'class' => 'broker-logo' . ( 'sm' === $size ? ' broker-logo--sm' : '' ),
				'alt'   => '',
			)
		);
	}
	return sprintf( '<span class="monogram%s" aria-hidden="true">%s</span>', 'sm' === $size ? ' monogram--sm' : '', esc_html( $broker['monogram'] ) );
}

/**
 * Regulator chips, flagged as unverified.
 *
 * @param string[] $names Regulator names.
 * @param int      $limit Show at most this many (0 = all).
 * @return string
 */
function fxt_core_regulators( array $names, $limit = 0 ) {
	if ( ! $names ) {
		return '<span class="muted">' . esc_html__( 'Research pending', 'fxt-core' ) . '</span>';
	}
	$shown = $limit ? array_slice( $names, 0, $limit ) : $names;
	$html  = '<ul class="chip-list" aria-label="' . esc_attr__( 'Regulators', 'fxt-core' ) . '">';
	foreach ( $shown as $name ) {
		$html .= '<li class="chip">' . esc_html( $name ) . '</li>';
	}
	if ( count( $names ) > count( $shown ) ) {
		$html .= '<li class="chip chip--more">+' . esc_html( (string) ( count( $names ) - count( $shown ) ) ) . '</li>';
	}
	return $html . '</ul>';
}

/**
 * Yes / no mark.
 *
 * @param bool $value Value.
 * @return string
 */
function fxt_core_yesno( $value ) {
	return $value
		? '<span class="yesno yesno--yes">' . fxt_core_icon( 'check', 'icon--sm' ) . '<span class="u-visually-hidden">' . esc_html__( 'Yes', 'fxt-core' ) . '</span></span>'
		: '<span class="yesno yesno--no">' . fxt_core_icon( 'minus', 'icon--sm' ) . '<span class="u-visually-hidden">' . esc_html__( 'No', 'fxt-core' ) . '</span></span>';
}

/**
 * Value with an "unverified" marker when the source entity is not verified.
 *
 * @param string $value    Value.
 * @param bool   $verified Verified on an official register.
 * @return string
 */
function fxt_core_unverified( $value, $verified ) {
	if ( '' === (string) $value ) {
		return '<span class="muted">' . esc_html__( 'Research pending', 'fxt-core' ) . '</span>';
	}
	return esc_html( $value ) . ( $verified ? '' : ' <span class="unverified">' . esc_html__( 'unverified', 'fxt-core' ) . '</span>' );
}

/**
 * Text or a muted "TBD".
 *
 * @param string $value Value.
 * @return string
 */
function fxt_core_text( $value ) {
	return '' === (string) $value ? '<span class="muted">' . esc_html__( 'TBD', 'fxt-core' ) . '</span>' : esc_html( $value );
}
