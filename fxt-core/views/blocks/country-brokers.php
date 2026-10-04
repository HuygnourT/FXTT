<?php
/**
 * Country-aware hero module: brokers available in the visitor's market.
 * Rendered server-side; refreshed in place by country.js when the country changes.
 *
 * @var array $args { limit: int }
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$limit     = max( 1, (int) $args['limit'] );
$code      = Repository::current_market();
$market    = Repository::market( $code );
if ( ! $market ) {
	echo '<p class="empty-state">' . esc_html__( 'Add markets under Broker Reviews > Markets to enable this block.', 'fxt-core' ) . '</p>';
	return;
}
$country   = $market['name'];
$available = Repository::available_in( $code );
$counts    = array( 'yes' => 0, 'restricted' => 0, 'pending' => 0 );
foreach ( Repository::brokers() as $broker ) {
	++$counts[ Repository::availability( $broker, $code ) ];
}
$chosen    = Repository::market_is_chosen();
$directory = fxt_core_page_url( 'directory' );
$method    = fxt_core_page_url( 'methodology' );
?>
<div class="country-hero__head">
	<p class="country-hero__detect">
		<?php echo fxt_core_icon( 'map-pin', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php if ( $chosen ) : ?>
			<?php /* translators: %s: country */ printf( esc_html__( 'You chose %s', 'fxt-core' ), '<strong>' . esc_html( $country ) . '</strong>' ); ?>
		<?php else : ?>
			<?php /* translators: %s: country */ printf( esc_html__( 'Your location appears to be %s', 'fxt-core' ), '<strong>' . esc_html( $country ) . '</strong>' ); ?>
			<span class="country-hero__sim"><?php esc_html_e( '(default, no location data used)', 'fxt-core' ); ?></span>
		<?php endif; ?>
	</p>
	<div class="country-hero__title-row">
		<h2 class="country-hero__title">
			<?php
			/* translators: %s: country */
			printf( esc_html__( 'Brokers available in %s', 'fxt-core' ), esc_html( $country ) );
			?>
		</h2>
		<?php echo fxt_core_sample(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<button class="link-button" type="button" data-open-country>
		<?php
		if ( $chosen ) {
			esc_html_e( 'Change country', 'fxt-core' );
		} else {
			/* translators: %s: country */
			printf( esc_html__( 'Not in %s? Change country', 'fxt-core' ), esc_html( $country ) );
		}
		?>
	</button>
</div>

<?php if ( $available ) : ?>
	<ul class="country-hero__list">
		<?php
		foreach ( array_slice( $available, 0, $limit ) as $broker ) {
			fxt_core_view( 'cards/hero-broker', array( 'broker' => $broker, 'code' => $code ) );
		}
		?>
	</ul>
<?php else : ?>
	<div class="country-hero__empty">
		<p class="country-hero__empty-title">
			<?php
			/* translators: %s: country */
			printf( esc_html__( 'We have not confirmed any broker for %s yet.', 'fxt-core' ), esc_html( $country ) );
			?>
		</p>
		<p><?php esc_html_e( 'We only list a broker as available once we have identified the entity that accepts residents.', 'fxt-core' ); ?></p>
		<?php if ( $method ) : ?>
			<a class="link-strong" href="<?php echo esc_url( $method . '#country' ); ?>"><?php esc_html_e( 'How we verify country availability', 'fxt-core' ); ?></a>
		<?php endif; ?>
	</div>
<?php endif; ?>

<div class="country-hero__foot">
	<p class="country-hero__counts">
		<span><?php /* translators: %s: number of brokers */ printf( esc_html__( '%s available', 'fxt-core' ), '<strong>' . (int) $counts['yes'] . '</strong>' ); ?></span>
		<span><?php /* translators: %s: number of brokers */ printf( esc_html__( '%s not accepting residents', 'fxt-core' ), '<strong>' . (int) $counts['restricted'] . '</strong>' ); ?></span>
		<span><?php /* translators: %s: number of brokers */ printf( esc_html__( '%s pending', 'fxt-core' ), '<strong>' . (int) $counts['pending'] . '</strong>' ); ?></span>
	</p>
	<?php if ( $directory ) : ?>
		<a class="link-strong" href="<?php echo esc_url( add_query_arg( 'country', $code, $directory ) ); ?>">
			<?php
			/* translators: %s: country */
			printf( esc_html__( 'All brokers for %s', 'fxt-core' ), esc_html( $country ) );
			?>
			<?php echo fxt_core_icon( 'arrow-right', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</a>
	<?php endif; ?>
</div>
