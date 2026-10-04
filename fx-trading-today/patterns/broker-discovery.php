<?php
/**
 * Title: Broker discovery
 * Slug: fx-trading-today/broker-discovery
 * Categories: fxt-home
 * Description: Section heading, broker search and broker cards.
 * Viewport width: 1400
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","align":"full","className":"section"} -->
<section class="wp-block-group alignfull section" id="reviews"><!-- wp:group {"className":"container"} -->
<div class="wp-block-group container"><!-- wp:group {"className":"discovery__head"} -->
<div class="wp-block-group discovery__head"><!-- wp:group {"className":"section-head"} -->
<div class="wp-block-group section-head"><!-- wp:heading {"className":"section-head__title"} -->
<h2 class="wp-block-heading section-head__title">Start with the broker you&#8217;re researching.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"section-head__lead"} -->
<p class="section-head__lead">Each review opens with the entity, licence and test results for your country, then the costs and platforms.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:search {"label":"Search broker reviews","showLabel":false,"placeholder":"Search a broker, e.g. Exness","buttonText":"Search","buttonPosition":"no-button","className":"is-style-field"} /--></div>
<!-- /wp:group -->

<!-- wp:fxt/broker-cards {"count":4} /-->

<!-- wp:group {"className":"section-foot"} -->
<div class="wp-block-group section-foot"><!-- wp:paragraph {"className":"is-style-sample"} -->
<p class="is-style-sample">Sample research data</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"is-style-link-arrow"} -->
<p class="is-style-link-arrow"><a href="<?php echo esc_url( fxt_tt_url( 'directory' ) ); ?>">Browse all broker reviews</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
