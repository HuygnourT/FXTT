<?php
/**
 * Broker data: final score summary (the verdict text itself is editorial content).
 *
 * @var array $args { broker: array }
 * @package FXT\Core
 */

defined( 'ABSPATH' ) || exit;

$broker = $args['broker'];
?>
<div class="final-score">
	<p class="final-score__value"><?php echo fxt_core_score( $broker ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
	<p class="final-score__meta">
		<?php if ( ! fxt_core_score_is_pending( $broker ) ) : ?>
			<span class="u-label"><?php esc_html_e( 'Research score', 'fxt-core' ); ?></span>
		<?php endif; ?>
		<?php echo fxt_core_badge( 'status', $broker['status'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</p>
</div>
