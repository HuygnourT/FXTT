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
?>
<ul class="platform-grid">
	<?php foreach ( $tiles as $slug => $name ) : ?>
		<?php $on = isset( $broker['platforms'][ $slug ] ); ?>
		<li class="platform-tile<?php echo $on ? '' : ' platform-tile--off'; ?>">
			<?php echo fxt_core_yesno( $on ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<span class="platform-tile__name"><?php echo esc_html( $name ); ?></span>
			<span class="platform-tile__state"><?php echo $on ? esc_html__( 'Available', 'fxt-core' ) : esc_html__( 'Not offered', 'fxt-core' ); ?></span>
		</li>
	<?php endforeach; ?>
</ul>
<?php if ( $broker['other_platforms'] ) : ?>
	<p class="doc-note">
		<?php
		/* translators: %s: list of other platforms */
		printf( esc_html__( 'Other platforms: %s.', 'fxt-core' ), esc_html( $broker['other_platforms'] ) );
		?>
	</p>
<?php endif; ?>
