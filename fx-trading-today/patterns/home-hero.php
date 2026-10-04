<?php
/**
 * Title: Home: hero with country brokers
 * Slug: fx-trading-today/home-hero
 * Categories: fxt-home
 * Description: Headline, two calls to action and the country-aware broker list.
 * Viewport width: 1400
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","align":"full","className":"hero"} -->
<section class="wp-block-group alignfull hero"><!-- wp:group {"className":"container hero__grid"} -->
<div class="wp-block-group container hero__grid"><!-- wp:group {"className":"hero__copy"} -->
<div class="wp-block-group hero__copy"><!-- wp:paragraph {"className":"is-style-eyebrow"} -->
<p class="is-style-eyebrow">Independent broker research for Southeast Asia</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"className":"hero__title"} -->
<h1 class="wp-block-heading hero__title">Forex brokers, researched beyond the marketing claims.</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"hero__lead"} -->
<p class="hero__lead">We verify the legal entity, licence, costs and real withdrawals for your country before we publish a rating.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"className":"hero__actions"} -->
<div class="wp-block-buttons hero__actions"><!-- wp:button {"className":"has-arrow"} -->
<div class="wp-block-button has-arrow"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( fxt_tt_url( 'compare' ) ); ?>">Compare Brokers</a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"is-style-secondary"} -->
<div class="wp-block-button is-style-secondary"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( fxt_tt_url( 'directory' ) ); ?>">Explore Broker Reviews</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:fxt/country-brokers {"limit":4} /--></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
