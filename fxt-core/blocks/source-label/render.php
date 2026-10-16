<?php
/**
 * Render: fxt/source-label.
 *
 * "Source: [label] note" with the same label styles as the review data.
 *
 * @var array $attributes Block attributes.
 * @package FXT\Core
 */

use FXT\Core\Schema;

defined( 'ABSPATH' ) || exit;

$fxt_kinds = Schema::label_kinds();
$fxt_kind  = isset( $attributes['kind'], $fxt_kinds[ $attributes['kind'] ] ) ? $attributes['kind'] : 'tested';
$fxt_text  = '' !== trim( (string) $attributes['text'] ) ? (string) $attributes['text'] : $fxt_kinds[ $fxt_kind ];
$fxt_class = 'source-tag source-tag--' . ( 'unverified' === $fxt_kind ? 'none' : $fxt_kind ) . ' fxt-source-label';
$fxt_url   = trim( (string) $attributes['url'] );
$fxt_tag   = $fxt_url
	? sprintf( '<a class="%1$s" data-kind="%2$s" href="%3$s"%4$s>%5$s</a>', esc_attr( $fxt_class ), esc_attr( $fxt_kind ), esc_url( $fxt_url ), ! empty( $attributes['newTab'] ) ? ' target="_blank" rel="noopener"' : '', esc_html( $fxt_text ) )
	: sprintf( '<span class="%1$s" data-kind="%2$s">%3$s</span>', esc_attr( $fxt_class ), esc_attr( $fxt_kind ), esc_html( $fxt_text ) );
?>
<p <?php echo get_block_wrapper_attributes( array( 'class' => 'data-source' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php if ( '' !== trim( (string) $attributes['prefix'] ) ) : ?>
		<?php echo esc_html( $attributes['prefix'] ); ?>
	<?php endif; ?>
	<?php echo $fxt_tag; // phpcs:ignore WordPress.Security.EscapeOutput -- built and escaped above. ?>
	<?php if ( '' !== trim( (string) $attributes['note'] ) ) : ?>
		<span class="data-source__note"><?php echo esc_html( $attributes['note'] ); ?></span>
	<?php endif; ?>
</p>
