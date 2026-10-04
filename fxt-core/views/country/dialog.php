<?php
/**
 * Country picker dialog (printed once in the footer when a page uses it).
 * Choosing a country stores a preference cookie; no location data is read.
 *
 * @var array $args {}
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$current = Repository::current_market();
?>
<dialog class="country-dialog" aria-labelledby="fxt-country-title" data-country-dialog>
	<div class="country-dialog__head">
		<h2 class="country-dialog__title" id="fxt-country-title"><?php esc_html_e( 'Select your country', 'fxt-core' ); ?></h2>
		<button class="icon-btn" type="button" data-country-close>
			<?php echo fxt_core_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<span class="u-visually-hidden"><?php esc_html_e( 'Close', 'fxt-core' ); ?></span>
		</button>
	</div>
	<p class="country-dialog__lead"><?php esc_html_e( 'Brokers, legal entities and payment methods change by country. Choose where you live to see conditions for your market.', 'fxt-core' ); ?></p>
	<label class="search-field country-dialog__search">
		<?php echo fxt_core_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<span class="u-visually-hidden"><?php esc_html_e( 'Search country', 'fxt-core' ); ?></span>
		<input class="search-field__input" type="search" placeholder="<?php esc_attr_e( 'Search country...', 'fxt-core' ); ?>" autocomplete="off" data-country-search>
	</label>
	<ul class="country-dialog__list" data-country-list>
		<?php
		$status_labels = array( 'published' => __( 'research published', 'fxt-core' ), 'in-progress' => __( 'research in progress', 'fxt-core' ), 'planned' => __( 'research planned', 'fxt-core' ) );
		foreach ( Repository::markets() as $market ) :
			$is_current = $market['code'] === $current;
			$count      = count( Repository::available_in( $market['code'] ) );
			?>
			<li data-country-item data-name="<?php echo esc_attr( strtolower( $market['name'] . ' ' . $market['code'] ) ); ?>">
				<button class="country-option" type="button" data-country-pick="<?php echo esc_attr( $market['code'] ); ?>" aria-pressed="<?php echo $is_current ? 'true' : 'false'; ?>">
					<span class="country-code"><?php echo esc_html( $market['code'] ); ?></span>
					<span class="country-option__text">
						<span class="country-option__name"><?php echo esc_html( $market['name'] ); ?></span>
						<span class="country-option__meta">
							<?php
							/* translators: 1: number of brokers, 2: research status */
							echo esc_html( sprintf( _n( '%1$d broker available, %2$s', '%1$d brokers available, %2$s', $count, 'fxt-core' ), $count, isset( $status_labels[ $market['status'] ] ) ? $status_labels[ $market['status'] ] : '' ) );
							?>
						</span>
					</span>
					<?php echo $is_current ? fxt_core_icon( 'check', 'country-option__check' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</button>
			</li>
		<?php endforeach; ?>
		<li class="country-dialog__empty" data-country-empty hidden><?php esc_html_e( 'No country matches your search.', 'fxt-core' ); ?></li>
	</ul>
	<p class="country-dialog__note">
		<?php echo fxt_core_icon( 'lock', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php esc_html_e( 'We never read your device location. Your choice is stored in a cookie on this device.', 'fxt-core' ); ?>
	</p>
</dialog>
