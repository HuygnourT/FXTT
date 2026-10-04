<?php
/**
 * Evidence data: test timeline. Steps with a test ID open the evidence viewer.
 *
 * @var array $args { evidence: array }
 * @package FXT\Core
 */

defined( 'ABSPATH' ) || exit;

$evidence = $args['evidence'];
if ( ! $evidence['timeline'] ) {
	return;
}
?>
<ol class="v-timeline">
	<?php foreach ( $evidence['timeline'] as $step ) : ?>
		<li class="v-timeline__item">
			<span class="v-timeline__dot" aria-hidden="true"></span>
			<div class="v-timeline__body">
				<p class="v-timeline__title"><?php echo esc_html( (string) $step['title'] ); ?></p>
				<?php if ( ! empty( $step['note'] ) ) : ?>
					<p class="v-timeline__note"><?php echo esc_html( $step['note'] ); ?></p>
				<?php endif; ?>
				<p class="v-timeline__meta">
					<time datetime="<?php echo esc_attr( (string) $step['at'] ); ?>"><?php echo esc_html( fxt_core_datetime( (string) $step['at'] ) ); ?></time>
					<?php if ( ! empty( $step['ref'] ) && isset( $evidence['records'][ $step['ref'] ] ) ) : ?>
						<button class="link-button" type="button" data-view-evidence="<?php echo esc_attr( $step['ref'] ); ?>">
							<?php
							/* translators: %s: test ID */
							echo esc_html( sprintf( __( 'View %s', 'fxt-core' ), $step['ref'] ) );
							?>
						</button>
					<?php endif; ?>
				</p>
			</div>
		</li>
	<?php endforeach; ?>
</ol>
