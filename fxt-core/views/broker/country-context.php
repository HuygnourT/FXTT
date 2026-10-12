<?php
/**
 * Review hero: country context for the visitor's country.
 *
 * Every text comes from Settings > FX Trading Today > Review page, a
 * country's own message (Broker Reviews > Countries) or the broker's country
 * row (Broker data: conditions by country), in reverse order of priority.
 * Refreshed in place by country.js when the visitor changes country.
 *
 * @var array $args { broker: array, code: string }
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$broker  = $args['broker'];
$code    = $args['code'];
$context = Repository::country_context( $broker, $code );
$vars    = fxt_core_context_vars( $broker, $code );
?>
<div class="country-context country-context--<?php echo esc_attr( $context['status'] ); ?>" data-eligibility="<?php echo esc_attr( $context['status'] ); ?>">
	<div class="country-context__head">
		<span class="u-label u-label--accent">
			<?php echo fxt_core_icon( 'map-pin', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo fxt_core_fill( $context['heading'], $vars ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in fxt_core_fill(). ?>
		</span>
		<button class="link-button" type="button" data-open-country><?php echo esc_html( $context['change'] ); ?></button>
	</div>
	<?php if ( '' !== trim( (string) $context['description'] ) ) : ?>
		<p class="country-context__description"><?php echo fxt_core_fill( $context['description'], $vars ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
	<?php endif; ?>
	<p><?php echo fxt_core_fill( $context['message'], $vars ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
</div>
