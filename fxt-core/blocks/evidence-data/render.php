<?php
/**
 * Render: fxt/evidence-data.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 * @package FXT\Core
 */

use FXT\Core\Blocks;
use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$fxt_post_id  = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
$fxt_evidence = Repository::evidence( $fxt_post_id );
$fxt_section  = in_array( $attributes['section'], array( 'summary', 'timeline', 'deposits', 'withdrawals', 'spreads', 'execution', 'support' ), true ) ? $attributes['section'] : 'summary';
// 1.4.4 categories: own heading, nothing at all when the category is empty.
$fxt_more     = array(
	'spreads'   => array( 'spreads', __( 'Spread tests', 'fxt-core' ), 'spread-tests' ),
	'execution' => array( 'executions', __( 'Execution tests', 'fxt-core' ), 'execution-tests' ),
	'support'   => array( 'support', __( 'Customer support tests', 'fxt-core' ), 'support-tests' ),
);

if ( ! $fxt_evidence ) {
	if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		echo '<p class="empty-state">' . esc_html__( 'Evidence data appears here when this block is used inside a Test Evidence post.', 'fxt-core' ) . '</p>';
	}
	return;
}
if ( isset( $fxt_more[ $fxt_section ] ) ) {
	if ( ! $fxt_evidence[ $fxt_more[ $fxt_section ][0] ] ) {
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			echo '<p class="empty-state">' . esc_html( sprintf( /* translators: %s: section name */ __( '%s: no rows yet, so this section is hidden on the site.', 'fxt-core' ), $fxt_more[ $fxt_section ][1] ) ) . '</p>';
		}
		return;
	}
	$fxt_heading = isset( $attributes['heading'] ) && '' !== trim( $attributes['heading'] ) ? trim( $attributes['heading'] ) : $fxt_more[ $fxt_section ][1];
	printf( '<h2 class="wp-block-heading" id="%s">%s</h2>', esc_attr( $fxt_more[ $fxt_section ][2] ), esc_html( $fxt_heading ) );
	printf( '<div %s>', get_block_wrapper_attributes( array( 'class' => 'evidence-data evidence-data--' . $fxt_section ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	fxt_core_view( 'evidence/category', array( 'evidence' => $fxt_evidence, 'kind' => $fxt_section ) );
	echo '</div>';
	return;
}
if ( 'summary' !== $fxt_section && ! is_admin() ) {
	Blocks::queue_viewer( $fxt_evidence );
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'evidence-data evidence-data--' . $fxt_section ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php
	if ( in_array( $fxt_section, array( 'deposits', 'withdrawals' ), true ) ) {
		fxt_core_view( 'evidence/tests', array( 'evidence' => $fxt_evidence, 'kind' => $fxt_section ) );
	} else {
		fxt_core_view( 'evidence/' . $fxt_section, array( 'evidence' => $fxt_evidence ) );
	}
	?>
</div>
