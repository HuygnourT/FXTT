<?php
/**
 * Title: Methodology preview
 * Slug: fx-trading-today/methodology-preview
 * Categories: fxt-home
 * Description: Score formula and category weights.
 * Viewport width: 1400
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","align":"full","className":"section"} -->
<section class="wp-block-group alignfull section" id="methodology"><!-- wp:group {"className":"container"} -->
<div class="wp-block-group container"><!-- wp:group {"className":"methodology__grid"} -->
<div class="wp-block-group methodology__grid"><!-- wp:group {"className":"methodology__intro"} -->
<div class="wp-block-group methodology__intro"><!-- wp:group {"className":"section-head"} -->
<div class="wp-block-group section-head"><!-- wp:heading {"className":"section-head__title"} -->
<h2 class="wp-block-heading section-head__title">How a research score is built.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"section-head__lead"} -->
<p class="section-head__lead">Six weighted categories, each scored from 0 to 5 using evidence we collect ourselves. Regulation carries the most weight because it decides what happens to your money if things go wrong.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"formula"} -->
<div class="wp-block-group formula"><!-- wp:paragraph {"className":"is-style-eyebrow-muted"} -->
<p class="is-style-eyebrow-muted">Formula</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><code>Score = sum of (category score x weight)</code></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"className":"is-style-sample"} -->
<p class="is-style-sample">Draft weights</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-secondary"} -->
<div class="wp-block-button is-style-secondary"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( fxt_tt_url( 'methodology' ) ); ?>">Read Our Research Methodology</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:fxt/score-weights {"variant":"list"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
