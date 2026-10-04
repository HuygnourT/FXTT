<?php
/**
 * Title: Comparison preview
 * Slug: fx-trading-today/compare-preview
 * Categories: fxt-home
 * Description: Heading and the interactive broker comparison.
 * Viewport width: 1400
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","align":"full","className":"section section\u002d\u002dsurface"} -->
<section class="wp-block-group alignfull section section--surface" id="compare"><!-- wp:group {"className":"container"} -->
<div class="wp-block-group container"><!-- wp:group {"className":"section-head section-head\u002d\u002dsplit"} -->
<div class="wp-block-group section-head section-head--split"><!-- wp:heading {"className":"section-head__title"} -->
<h2 class="wp-block-heading section-head__title">Pick brokers and see the research side by side.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"section-head__lead"} -->
<p class="section-head__lead">Choose up to three brokers. Regulation and payment rows follow the country you select.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:fxt/broker-compare {"mode":"preview","slots":3,"brokers":"exness,vantage,icmarkets"} /--></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
