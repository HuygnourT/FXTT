<?php
/**
 * Broker data: platform tiles.
 *
 * @var array $args { broker: array }
 * @package FXT\Core
 */

defined( 'ABSPATH' ) || exit;

$broker = $args['broker'];
$tiles  = array();
// Every platform in Broker Reviews > Platforms; offered ones are ticked.
foreach ( \FXT\Core\Repository::terms( \FXT\Core\Content_Types::PLATFORM ) as $platform_term ) {
	$tiles[ $platform_term['slug'] ] = $platform_term['full_name'];
}
$source = \FXT\Core\Repository::source( $broker, 'platforms' );
$place  = fxt_core_source_position( $args, 'above' );
if ( 'above' === $place['position'] ) {
	echo fxt_core_source_line( $source ); // phpcs:ignore WordPress.Security.EscapeOutput
}
$index = 0;
?>
<ul class="platform-grid">
	<?php foreach ( $tiles as $slug => $name ) : ?>
		<?php
		$on = isset( $broker['platforms'][ $slug ] );
		if ( 'after' === $place['position'] && $index === $place['after'] ) {
			$fxt_line = fxt_core_source_line( $source );
			echo '' !== $fxt_line ? '<li class="platform-grid__source">' . $fxt_line . '</li>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		++$index;
		?>
		<li class="platform-tile<?php echo $on ? '' : ' platform-tile--off'; ?>">
			<?php echo fxt_core_yesno( $on ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<span class="platform-tile__name"><?php echo esc_html( $name ); ?></span>
			<span class="platform-tile__state"><?php echo $on ? esc_html__( 'Available', 'fxt-core' ) : esc_html__( 'Not offered', 'fxt-core' ); ?></span>
			<?php echo ( $on && 'rows' === $place['position'] ) ? fxt_core_source_tag( fxt_core_source_named( $source ) ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</li>
	<?php endforeach; ?>
	<?php
	if ( 'after' === $place['position'] && $place['after'] >= $index ) {
		$fxt_line = fxt_core_source_line( $source );
		echo '' !== $fxt_line ? '<li class="platform-grid__source">' . $fxt_line . '</li>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	?>
</ul>
<?php
if ( 'below' === $place['position'] ) {
	echo fxt_core_source_line( $source ); // phpcs:ignore WordPress.Security.EscapeOutput
}
?>
<?php if ( $broker['other_platforms'] ) : ?>
	<p class="doc-note">
		<?php
		/* translators: %s: list of other platforms */
		printf( esc_html__( 'Other platforms: %s.', 'fxt-core' ), esc_html( $broker['other_platforms'] ) );
		?>
	</p>
<?php endif; ?>
