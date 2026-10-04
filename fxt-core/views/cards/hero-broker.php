<?php
/**
 * Compact broker row in the country hero module.
 *
 * @var array $args { broker: array, code: string }
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$broker = $args['broker'];
$code   = $args['code'];
$entity = Repository::entity( $broker, $code );
$row    = Repository::market_row( $broker, $code );
$tested = $row && isset( $row['deposit'] ) && 'complete' === $row['deposit'];
?>
<li>
	<a class="hero-broker" href="<?php echo esc_url( $broker['url'] ); ?>">
		<?php echo fxt_core_logo( $broker, 'sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<span class="hero-broker__main">
			<span class="hero-broker__name"><?php echo esc_html( $broker['name'] ); ?></span>
			<span class="hero-broker__meta">
				<?php
				/* translators: %s: regulator */
				printf( esc_html__( 'Entity regulator: %s', 'fxt-core' ), esc_html( $entity['regulator'] ? $entity['regulator'] : __( 'Research pending', 'fxt-core' ) ) );
				?>
			</span>
			<span class="hero-broker__meta">
				<?php
				/* translators: %s: local payment methods */
				printf( esc_html__( 'Local payments: %s', 'fxt-core' ), esc_html( ! empty( $row['local_payments'] ) ? $row['local_payments'] : __( 'Research pending', 'fxt-core' ) ) );
				?>
			</span>
		</span>
		<span class="hero-broker__side">
			<span class="hero-broker__score"><?php echo esc_html( fxt_core_score_label( $broker ) ); ?></span>
			<?php echo $tested ? '<span class="badge badge--test-complete">' . esc_html__( 'Tested', 'fxt-core' ) . '</span>' : '<span class="badge badge--test-none">' . esc_html__( 'Not tested', 'fxt-core' ) . '</span>'; ?>
		</span>
	</a>
</li>
