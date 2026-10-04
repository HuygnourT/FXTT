<?php
/**
 * Site header: optional banner, sticky bar, mega menu and mobile drawer.
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;

$fxt_cta_label = (string) fxt_tt_setting( 'header_cta_label', __( 'Find a Broker', 'fx-trading-today' ) );
$fxt_cta_url   = (string) fxt_tt_setting( 'header_cta_url', fxt_tt_url( 'directory' ) );
$fxt_banner    = fxt_tt_banner_text();
$fxt_country   = function_exists( 'fxt_core_current_market' ) && \FXT\Core\Repository::markets() ? fxt_core_current_market() : '';
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e( 'Skip to content', 'fx-trading-today' ); ?></a>

<?php if ( $fxt_banner ) : ?>
	<div class="proto-banner" role="note">
		<div class="container proto-banner__inner">
			<?php echo fxt_tt_icon( 'alert', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<span><?php echo esc_html( $fxt_banner ); ?></span>
		</div>
	</div>
<?php endif; ?>

<header class="site-header" data-site-header>
	<div class="site-header__inner">
		<?php if ( has_custom_logo() ) : ?>
			<?php the_custom_logo(); ?>
		<?php else : ?>
			<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
				<span class="brand__mark" aria-hidden="true"></span>
				<span class="brand__name"><?php bloginfo( 'name' ); ?></span>
			</a>
		<?php endif; ?>

		<?php fxt_tt_the_primary_nav(); ?>

		<div class="site-header__actions">
			<?php if ( $fxt_country ) : ?>
				<button class="country-chip" type="button" data-open-country aria-haspopup="dialog">
					<?php echo fxt_tt_icon( 'globe', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span data-country-code><?php echo esc_html( $fxt_country ); ?></span>
					<span class="u-visually-hidden"><?php esc_html_e( ': change country', 'fx-trading-today' ); ?></span>
				</button>
			<?php endif; ?>
			<a class="icon-btn search-trigger" href="<?php echo esc_url( fxt_tt_core() ? fxt_tt_url( 'directory' ) . '#search' : home_url( '/?s=' ) ); ?>">
				<?php echo fxt_tt_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span class="search-trigger__label"><?php esc_html_e( 'Search', 'fx-trading-today' ); ?></span>
				<span class="u-visually-hidden"><?php esc_html_e( 'brokers and research', 'fx-trading-today' ); ?></span>
			</a>
			<?php if ( $fxt_cta_label && $fxt_cta_url ) : ?>
				<a class="btn btn--primary btn--sm site-header__cta" href="<?php echo esc_url( $fxt_cta_url ); ?>"><?php echo esc_html( $fxt_cta_label ); ?></a>
			<?php endif; ?>
			<button class="icon-btn menu-toggle" type="button" aria-expanded="false" aria-controls="mobile-nav" aria-label="<?php esc_attr_e( 'Open menu', 'fx-trading-today' ); ?>" data-label-open="<?php esc_attr_e( 'Open menu', 'fx-trading-today' ); ?>" data-label-close="<?php esc_attr_e( 'Close menu', 'fx-trading-today' ); ?>" data-mobile-toggle>
				<svg class="icon" aria-hidden="true" focusable="false" data-icon-open><use href="#i-menu"></use></svg>
				<svg class="icon" aria-hidden="true" focusable="false" data-icon-close hidden><use href="#i-close"></use></svg>
			</button>
		</div>
	</div>
	<?php fxt_tt_the_mega_menus(); ?>
	<?php fxt_tt_the_mobile_nav( $fxt_cta_label, $fxt_cta_url ); ?>
</header>
<div class="scrim" hidden data-scrim></div>

<main id="main" tabindex="-1">
