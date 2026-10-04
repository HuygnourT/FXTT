<?php
/**
 * Broker directory: search, filters and sort as a GET form (works without JS),
 * results rendered server-side. directory.js refreshes results in place.
 *
 * @var array $args { base_url: string, filters: array }
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$filters = $args['filters'];
$code    = Repository::current_market();
$base    = $args['base_url'];

$fields = \FXT\Core\Blocks::directory_fields();
$uid = wp_unique_id( 'dir-' );
?>
<div class="reviews-layout" data-fxt-directory data-base="<?php echo esc_url( $base ); ?>">
	<aside class="reviews-filters" aria-labelledby="<?php echo esc_attr( $uid ); ?>-title">
		<div class="reviews-filters__head">
			<h2 class="reviews-filters__title" id="<?php echo esc_attr( $uid ); ?>-title"><?php esc_html_e( 'Filter brokers', 'fxt-core' ); ?></h2>
			<a class="link-button" href="<?php echo esc_url( $base ); ?>" data-dir-clear><?php esc_html_e( 'Clear all', 'fxt-core' ); ?></a>
		</div>
		<form class="reviews-filters__form" method="get" action="<?php echo esc_url( $base ); ?>" id="<?php echo esc_attr( $uid ); ?>-form" role="search" data-dir-form>
			<label class="search-field">
				<?php echo fxt_core_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span class="u-visually-hidden"><?php esc_html_e( 'Search brokers', 'fxt-core' ); ?></span>
				<input class="search-field__input" type="search" name="q" value="<?php echo esc_attr( $filters['q'] ); ?>" placeholder="<?php esc_attr_e( 'Search brokers', 'fxt-core' ); ?>" autocomplete="off">
			</label>
			<div class="reviews-filters__fields">
				<div class="field">
					<label class="field__label" for="<?php echo esc_attr( $uid ); ?>-country"><?php esc_html_e( 'Country', 'fxt-core' ); ?></label>
					<select class="select" id="<?php echo esc_attr( $uid ); ?>-country" name="country" data-dir-country>
						<?php foreach ( Repository::markets() as $market ) : ?>
							<option value="<?php echo esc_attr( $market['code'] ); ?>"<?php selected( $market['code'], $code ); ?>><?php echo esc_html( $market['name'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php foreach ( $fields as $name => $field ) : ?>
					<div class="field">
						<label class="field__label" for="<?php echo esc_attr( $uid . '-' . $name ); ?>"><?php echo esc_html( $field[0] ); ?></label>
						<select class="select" id="<?php echo esc_attr( $uid . '-' . $name ); ?>" name="<?php echo esc_attr( $name ); ?>">
							<option value=""><?php echo esc_html( $field[1] ); ?></option>
							<?php foreach ( $field[2] as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>"<?php selected( (string) $value, $filters[ $name ] ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endforeach; ?>
			</div>
			<button class="btn btn--secondary btn--sm" type="submit" data-dir-submit><?php esc_html_e( 'Apply filters', 'fxt-core' ); ?></button>
		</form>
	</aside>

	<section class="reviews-results" aria-labelledby="<?php echo esc_attr( $uid ); ?>-count">
		<div class="results-bar">
			<h2 class="results-bar__title" id="<?php echo esc_attr( $uid ); ?>-count" aria-live="polite" data-dir-count>
				<?php
				$fxt_count  = count( \FXT\Core\Blocks::filter_brokers( $filters, $code ) );
				$fxt_market = Repository::market( $code );
				/* translators: 1: number of brokers, 2: country */
				echo esc_html( sprintf( _n( '%1$d broker for %2$s', '%1$d brokers for %2$s', $fxt_count, 'fxt-core' ), $fxt_count, $fxt_market ? $fxt_market['name'] : $code ) );
				?>
			</h2>
			<div class="results-bar__sort">
				<label class="field__label" for="<?php echo esc_attr( $uid ); ?>-sort"><?php esc_html_e( 'Sort by', 'fxt-core' ); ?></label>
				<select class="select" id="<?php echo esc_attr( $uid ); ?>-sort" name="sort" form="<?php echo esc_attr( $uid ); ?>-form">
					<option value="score"<?php selected( $filters['sort'], 'score' ); ?>><?php esc_html_e( 'Research score', 'fxt-core' ); ?></option>
					<option value="recent"<?php selected( $filters['sort'], 'recent' ); ?>><?php esc_html_e( 'Recently reviewed', 'fxt-core' ); ?></option>
					<option value="deposit"<?php selected( $filters['sort'], 'deposit' ); ?>><?php esc_html_e( 'Minimum deposit', 'fxt-core' ); ?></option>
				</select>
			</div>
		</div>
		<div data-dir-results>
			<?php fxt_core_view( 'blocks/directory-results', array( 'filters' => $filters, 'base_url' => $base, 'fields' => $fields ) ); ?>
		</div>
		<p class="results-note"><?php esc_html_e( 'Regulators are shown as listed by the brand and are marked unverified until checked against official registers.', 'fxt-core' ); ?></p>
	</section>
</div>
