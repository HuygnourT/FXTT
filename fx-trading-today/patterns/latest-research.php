<?php
/**
 * Title: Latest research
 * Slug: fx-trading-today/latest-research
 * Categories: fxt-home
 * Description: Newest articles: one featured, four listed.
 * Viewport width: 1400
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","align":"full","className":"section"} -->
<section class="wp-block-group alignfull section" id="latest"><!-- wp:group {"className":"container"} -->
<div class="wp-block-group container"><!-- wp:group {"className":"latest__head"} -->
<div class="wp-block-group latest__head"><!-- wp:group {"className":"latest__title-row"} -->
<div class="wp-block-group latest__title-row"><!-- wp:group {"className":"section-head"} -->
<div class="wp-block-group section-head"><!-- wp:heading {"className":"section-head__title"} -->
<h2 class="wp-block-heading section-head__title">Latest research</h2>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"className":"is-style-sample"} -->
<p class="is-style-sample">Sample articles</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"className":"is-style-link-arrow"} -->
<p class="is-style-link-arrow"><a href="<?php echo esc_url( fxt_tt_url( 'posts' ) ); ?>">All research and reviews</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"latest__grid"} -->
<div class="wp-block-group latest__grid"><!-- wp:query {"queryId":11,"query":{"perPage":1,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"className":"latest__feature"} -->
<div class="wp-block-query latest__feature"><!-- wp:post-template -->
<!-- wp:group {"tagName":"article","className":"feature-article"} -->
<article class="wp-block-group feature-article"><!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/8","className":"feature-article__image"} /-->

<!-- wp:post-terms {"term":"category","className":"is-style-eyebrow"} /-->

<!-- wp:post-title {"level":3,"isLink":true,"className":"feature-article__title"} /-->

<!-- wp:post-excerpt {"className":"feature-article__excerpt","excerptLength":40} /-->

<!-- wp:fxt/post-byline {"dateLabel":"Updated"} /--></article>
<!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query -->

<!-- wp:query {"queryId":12,"query":{"perPage":4,"pages":0,"offset":1,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"className":"latest__list"} -->
<div class="wp-block-query latest__list"><!-- wp:post-template -->
<!-- wp:group {"tagName":"article","className":"article-item"} -->
<article class="wp-block-group article-item"><!-- wp:post-terms {"term":"category","className":"is-style-eyebrow"} /-->

<!-- wp:post-title {"level":3,"isLink":true,"className":"article-item__title"} /-->

<!-- wp:fxt/post-byline {"showRole":false,"readTime":"short"} /--></article>
<!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
