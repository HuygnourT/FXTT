<?php
/**
 * Broker hero: which entity serves the visitor's country.
 *
 * @var array $args { broker: array, code: string }
 * @package FXT\Core
 */

use FXT\Core\Repository;
use FXT\Core\Schema;

defined( 'ABSPATH' ) || exit;

$broker       = $args['broker'];
$code         = $args['code'];
$market       = Repository::market( $code );
$country      = $market ? $market['name'] : $code;
$entity       = Repository::entity( $broker, $code );
$availability = Repository::availability( $broker, $code );
$labels       = Schema::availability();
?>
<div class="country-context">
	<div class="country-context__head">
		<span class="u-label u-label--accent">
			<?php echo fxt_core_icon( 'map-pin', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php
			/* translators: %s: country */
			printf( esc_html__( 'Reviewing for %s', 'fxt-core' ), esc_html( $country ) );
			?>
		</span>
		<button class="link-button" type="button" data-open-country><?php esc_html_e( 'Change country', 'fxt-core' ); ?></button>
	</div>
	<?php if ( 'yes' === $availability && $entity['name'] ) : ?>
		<p>
			<?php
			printf(
				/* translators: 1: country, 2: entity name, 3: regulator, 4: jurisdiction */
				esc_html__( 'Clients in %1$s are onboarded by %2$s, regulated by %3$s in %4$s.', 'fxt-core' ),
				esc_html( $country ),
				'<strong>' . esc_html( $entity['name'] ) . '</strong>',
				'<strong>' . fxt_core_unverified( $entity['regulator'], $entity['verified'] ) . '</strong>', // phpcs:ignore WordPress.Security.EscapeOutput
				esc_html( $entity['jurisdiction'] ? $entity['jurisdiction'] : __( 'TBD', 'fxt-core' ) )
			);
			?>
		</p>
	<?php else : ?>
		<p>
			<?php
			/* translators: 1: availability label, 2: country */
			printf( esc_html__( '%1$s for %2$s. Entity, payments and tests below may not apply to residents of this country.', 'fxt-core' ), esc_html( $labels[ $availability ] ), esc_html( $country ) );
			?>
		</p>
	<?php endif; ?>
</div>
