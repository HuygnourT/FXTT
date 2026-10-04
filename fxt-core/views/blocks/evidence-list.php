<?php
/**
 * All published test evidence, grouped by broker, plus tests still in progress.
 *
 * @var array $args {}
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$index   = Repository::evidence_index();
$pending = array();
foreach ( Repository::brokers() as $broker ) {
	foreach ( Repository::tested_markets( $broker ) as $code ) {
		if ( ! Repository::evidence_for( $broker['id'], $code ) ) {
			$pending[] = array( $broker, $code );
		}
	}
}
?>
<div class="evidence-index">
	<h2 class="doc-section__title"><?php esc_html_e( 'Published evidence', 'fxt-core' ); ?> <span class="count"><?php echo (int) count( $index ); ?></span></h2>
	<?php if ( $index ) : ?>
		<ul class="link-cards">
			<?php foreach ( $index as $row ) : ?>
				<?php
				$broker = Repository::broker( $row['broker_id'] );
				$market = Repository::market( $row['market'] );
				?>
				<li>
					<a class="link-card" href="<?php echo esc_url( $row['url'] ); ?>">
						<span class="u-label"><?php echo esc_html( $market ? $market['name'] : $row['market'] ); ?></span>
						<span class="link-card__text"><?php echo esc_html( $row['title'] ); ?></span>
						<?php echo fxt_core_icon( 'arrow-right', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p class="empty-state"><?php esc_html_e( 'No evidence has been published yet.', 'fxt-core' ); ?></p>
	<?php endif; ?>

	<?php if ( $pending ) : ?>
		<h2 class="doc-section__title"><?php esc_html_e( 'Testing in progress', 'fxt-core' ); ?> <span class="count"><?php echo (int) count( $pending ); ?></span></h2>
		<div class="table-scroll">
			<table class="data-table">
				<thead><tr>
					<th scope="col"><?php esc_html_e( 'Broker', 'fxt-core' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Country', 'fxt-core' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Deposit', 'fxt-core' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Withdrawal', 'fxt-core' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Last tested', 'fxt-core' ); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $pending as $item ) : ?>
					<?php
					list( $broker, $code ) = $item;
					$row    = Repository::market_row( $broker, $code );
					$market = Repository::market( $code );
					?>
					<tr>
						<th scope="row"><a href="<?php echo esc_url( $broker['url'] ); ?>"><?php echo esc_html( $broker['name'] ); ?></a></th>
						<td><?php echo esc_html( $market ? $market['name'] : $code ); ?></td>
						<td><?php echo fxt_core_badge( 'test', isset( $row['deposit'] ) ? (string) $row['deposit'] : '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
						<td><?php echo fxt_core_badge( 'test', isset( $row['withdrawal'] ) ? (string) $row['withdrawal'] : '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
						<td class="u-mono"><?php echo esc_html( fxt_core_date( isset( $row['last_tested'] ) ? $row['last_tested'] : '' ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
