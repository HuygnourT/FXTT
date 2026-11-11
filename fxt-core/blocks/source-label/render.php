<?php
/**
 * Render: fxt/source-label.
 *
 * One line "Source: [label] note" with the same label styles as the review
 * data. 1.4.8: several labels on the same line, e.g.
 *
 *   Source: [Tested] on 2 Sep and [Verified at source] at the regulator, checked monthly
 *   ^prefix  ^label  ^after       ^label (before "and")   ^after        ^note
 *
 * Each label: source type (tested, verified, stated, unverified or custom:
 * the classification, kept apart from the shown name), name (empty: the
 * site name of its type), link (http, https or mailto; a new tab adds
 * rel="noopener noreferrer"), text before, text after (text between labels)
 * and show / hide. Blocks saved before 1.4.8 (one label in kind / text /
 * url / newTab) are read as one label.
 *
 * @var array $attributes Block attributes.
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$fxt_preview = defined( 'REST_REQUEST' ) && REST_REQUEST;

// Show / hide: this block's choice, else Settings > Source labels.
$fxt_visibility = isset( $attributes['visibility'] ) ? (string) $attributes['visibility'] : '';
if ( 'hide' === $fxt_visibility || ( 'show' !== $fxt_visibility && ! Repository::source_display()['show'] ) ) {
	if ( $fxt_preview ) {
		// Editor preview only.
		echo '<p class="empty-state">' . esc_html__( 'Source label hidden on the site (block settings or Settings > FX Trading Today > Source labels).', 'fxt-core' ) . '</p>';
	}
	return;
}

$fxt_labels = fxt_core_source_block_labels( $attributes );
$fxt_parts  = array();
foreach ( $fxt_labels as $fxt_label ) {
	if ( $fxt_label['hidden'] ) {
		continue;
	}
	$fxt_parts[] = trim(
		( '' !== $fxt_label['before'] ? '<span class="data-source__text">' . esc_html( $fxt_label['before'] ) . '</span> ' : '' )
		. fxt_core_source_block_tag( $fxt_label )
		. ( '' !== $fxt_label['after'] ? ' <span class="data-source__text">' . esc_html( $fxt_label['after'] ) . '</span>' : '' )
	);
}
if ( ! $fxt_parts ) {
	if ( $fxt_preview ) {
		echo '<p class="empty-state">' . esc_html__( 'Every label of this block is hidden.', 'fxt-core' ) . '</p>';
	}
	return;
}
?>
<p <?php echo get_block_wrapper_attributes( array( 'class' => 'data-source' . ( count( $fxt_parts ) > 1 ? ' data-source--multi' : '' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php if ( '' !== trim( (string) $attributes['prefix'] ) ) : ?>
		<?php echo esc_html( $attributes['prefix'] ); ?>
	<?php endif; ?>
	<?php echo implode( ' ', $fxt_parts ); // phpcs:ignore WordPress.Security.EscapeOutput -- built and escaped above. ?>
	<?php if ( '' !== trim( (string) $attributes['note'] ) ) : ?>
		<span class="data-source__note"><?php echo esc_html( $attributes['note'] ); ?></span>
	<?php endif; ?>
</p>
