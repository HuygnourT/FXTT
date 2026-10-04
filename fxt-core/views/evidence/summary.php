<?php
/**
 * Evidence data: summary cards.
 *
 * @var array $args { evidence: array }
 * @package FXT\Core
 */

defined( 'ABSPATH' ) || exit;

$items = $args['evidence']['summary'];
if ( ! $items ) {
	return;
}
?>
<ul class="summary-grid">
	<?php foreach ( $items as $item ) : ?>
		<li class="summary-card">
			<span class="summary-card__icon"><?php echo fxt_core_icon( ! empty( $item['icon'] ) ? $item['icon'] : 'check', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<span class="u-label"><?php echo esc_html( (string) $item['title'] ); ?></span>
			<span class="summary-card__value"><?php echo esc_html( (string) $item['value'] ); ?></span>
			<?php if ( ! empty( $item['detail'] ) ) : ?>
				<span class="summary-card__detail"><?php echo esc_html( $item['detail'] ); ?></span>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>
