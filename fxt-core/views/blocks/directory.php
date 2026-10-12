<?php
/**
 * Broker directory: search, filters and sort as a GET form (works without JS),
 * results rendered server-side. directory.js refreshes results in place.
 *
 * All visible text comes from Settings > FX Trading Today > Broker Reviews
 * filters. Filter keys (query parameters) never depend on that text.
 *
 * @var array $args { base_url: string, filters: array }
 * @package FXT\Core
 */

use FXT\Core\Repository;
use FXT\Core\Blocks;

defined( 'ABSPATH' ) || exit;

$filters = $args['filters'];
$base    = $args['base_url'];
$fields  = Blocks::directory_fields();
$uid     = wp_unique_id( 'dir-' );
$ui      = static function ( $key ) {
	return Repository::ui_text( $key );
};
?>
<div class="reviews-layout" data-fxt-directory data-base="<?php echo esc_url( $base ); ?>">
	<aside class="reviews-filters" aria-labelledby="<?php echo esc_attr( $uid ); ?>-title">
		<div class="reviews-filters__head">
			<span class="reviews-filters__title" id="<?php echo esc_attr( $uid ); ?>-title"><?php echo esc_html( $ui( 'panel_title' ) ); ?></span>
			<a class="link-button" href="<?php echo esc_url( $base ); ?>" data-dir-clear><?php echo esc_html( $ui( 'clear_all' ) ); ?></a>
		</div>
		<form class="reviews-filters__form" method="get" action="<?php echo esc_url( $base ); ?>" id="<?php echo esc_attr( $uid ); ?>-form" role="search" data-dir-form>
			<label class="search-field">
				<?php echo fxt_core_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span class="u-visually-hidden"><?php echo esc_html( $ui( 'search_label' ) ); ?></span>
				<input class="search-field__input" type="search" name="q" value="<?php echo esc_attr( $filters['q'] ); ?>" placeholder="<?php echo esc_attr( $ui( 'search_placeholder' ) ); ?>" autocomplete="off">
			</label>
			<div class="reviews-filters__fields">
				<?php
				foreach ( $fields as $name => $field ) :
					$field_id = $uid . '-' . $name;
					// Availability depends on a country; it is disabled until one is chosen.
					$disabled = 'availability' === $name && '' === $filters['market'];
					$hint     = '' !== (string) $field['description'] && ( 'availability' !== $name || $disabled );
					?>
					<div class="field" data-dir-field="<?php echo esc_attr( $name ); ?>">
						<label class="field__label" for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
						<select class="select" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>"<?php echo $disabled ? ' disabled' : ''; ?><?php echo $hint ? ' aria-describedby="' . esc_attr( $field_id ) . '-hint"' : ''; ?>>
							<option value=""><?php echo esc_html( $field['placeholder'] ); ?></option>
							<?php foreach ( $field['options'] as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>"<?php selected( (string) $value, $filters[ $name ] ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<?php if ( '' !== (string) $field['description'] ) : ?>
							<p class="field__hint" id="<?php echo esc_attr( $field_id ); ?>-hint"<?php echo $hint ? '' : ' hidden'; ?>><?php echo esc_html( $field['description'] ); ?></p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
			<button class="btn btn--secondary btn--sm" type="submit" data-dir-submit><?php echo esc_html( $ui( 'apply' ) ); ?></button>
		</form>
	</aside>

	<section class="reviews-results" aria-labelledby="<?php echo esc_attr( $uid ); ?>-count">
		<div class="results-bar">
			<h2 class="results-bar__title" id="<?php echo esc_attr( $uid ); ?>-count" aria-live="polite" data-dir-count><?php echo esc_html( fxt_core_directory_heading( count( Blocks::filter_brokers( $filters, $filters['market'] ) ) ) ); ?></h2>
			<div class="results-bar__sort">
				<label class="field__label" for="<?php echo esc_attr( $uid ); ?>-sort"><?php echo esc_html( $ui( 'sort_label' ) ); ?></label>
				<select class="select" id="<?php echo esc_attr( $uid ); ?>-sort" name="sort" form="<?php echo esc_attr( $uid ); ?>-form">
					<option value="score"<?php selected( $filters['sort'], 'score' ); ?>><?php echo esc_html( $ui( 'sort_score' ) ); ?></option>
					<option value="recent"<?php selected( $filters['sort'], 'recent' ); ?>><?php echo esc_html( $ui( 'sort_recent' ) ); ?></option>
					<option value="deposit"<?php selected( $filters['sort'], 'deposit' ); ?>><?php echo esc_html( $ui( 'sort_deposit' ) ); ?></option>
				</select>
			</div>
		</div>
		<div data-dir-results>
			<?php fxt_core_view( 'blocks/directory-results', array( 'filters' => $filters, 'base_url' => $base, 'fields' => $fields ) ); ?>
		</div>
	</section>
</div>
