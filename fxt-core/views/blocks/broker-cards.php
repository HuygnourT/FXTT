<?php
/**
 * Featured broker cards (highest research scores).
 *
 * @var array $args { count: int }
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$code    = Repository::current_market();
$brokers = array_slice(
	array_values(
		array_filter(
			Repository::brokers(),
			static function ( $b ) {
				return null !== $b['score'];
			}
		)
	),
	0,
	max( 1, (int) $args['count'] )
);
if ( ! $brokers ) {
	echo '<p class="empty-state">' . esc_html__( 'No published broker reviews yet.', 'fxt-core' ) . '</p>';
	return;
}
echo '<div class="broker-grid">';
foreach ( $brokers as $broker ) {
	fxt_core_view( 'cards/broker-card', array( 'broker' => $broker, 'code' => $code ) );
}
echo '</div>';
