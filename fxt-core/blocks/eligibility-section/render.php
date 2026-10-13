<?php
/**
 * Render: fxt/eligibility-section.
 *
 * Prints the inner blocks unchanged (no wrapper element, so the review's
 * typography and spacing stay identical), or nothing when the broker does
 * not accept residents of the visitor's country. Unknown or unverified
 * eligibility never hides content.
 *
 * @var array  $attributes Block attributes.
 * @var string $content    Rendered inner blocks.
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$fxt_broker = fxt_core_review_broker();
if ( $fxt_broker && ! empty( $attributes['hideWhenRestricted'] ) && 'restricted' === Repository::eligibility( $fxt_broker, Repository::current_market() ) ) {
	return;
}
echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- rendered inner blocks.
