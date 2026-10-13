<?php
/**
 * Review hero badges: review status, availability in the visitor's country
 * and the sample-data label. Refreshed in place when the country changes.
 *
 * @var array $args { broker: array, code: string }
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$broker  = $args['broker'];
$code    = $args['code'];
$market  = Repository::market( $code );
$country = $market ? $market['name'] : $code;

echo fxt_core_badge( 'status', $broker['status'] ); // phpcs:ignore WordPress.Security.EscapeOutput
/* translators: %s: country */
echo fxt_core_badge( 'availability', Repository::availability( $broker, $code ), sprintf( __( ' in %s', 'fxt-core' ), $country ) ); // phpcs:ignore WordPress.Security.EscapeOutput
echo fxt_core_sample(); // phpcs:ignore WordPress.Security.EscapeOutput
