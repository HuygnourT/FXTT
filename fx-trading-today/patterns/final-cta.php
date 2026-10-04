<?php
/**
 * Title: Final call to action
 * Slug: fx-trading-today/final-cta
 * Categories: fxt-home
 * Description: Closing band with two buttons.
 * Viewport width: 1400
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","align":"full","className":"final-cta"} -->
<section class="wp-block-group alignfull final-cta"><!-- wp:group {"className":"container final-cta__inner"} -->
<div class="wp-block-group container final-cta__inner"><!-- wp:group {"className":"final-cta__copy"} -->
<div class="wp-block-group final-cta__copy"><!-- wp:heading {"className":"final-cta__title"} -->
<h2 class="wp-block-heading final-cta__title">Research before you deposit.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"final-cta__text"} -->
<p class="final-cta__text">Compare brokers, regulation, costs and local account conditions before opening your next trading account.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:buttons {"className":"final-cta__actions"} -->
<div class="wp-block-buttons final-cta__actions"><!-- wp:button {"className":"is-style-secondary"} -->
<div class="wp-block-button is-style-secondary"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( fxt_tt_url( 'directory' ) ); ?>">Browse Broker Reviews</a></div>
<!-- /wp:button -->
<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( fxt_tt_url( 'compare' ) ); ?>">Compare Brokers</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
