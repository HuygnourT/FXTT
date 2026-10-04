<?php
/**
 * Fallback template: lists of posts (archives without a specific template).
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;

get_header();
get_template_part( 'template-parts/archive', null, array( 'title' => wp_strip_all_tags( get_the_archive_title() ), 'lead' => wp_strip_all_tags( get_the_archive_description() ) ) );
get_footer();
