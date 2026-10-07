<?php
/**
 * Test evidence index: published evidence records (Broker x Country) and,
 * optionally, tests still in progress (from each broker's country data).
 *
 * @var array $args { scope: all|country, showPending: bool, publishedTitle: string, pendingTitle: string, filter: string ISO, base: string }
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$scope   = $args['scope'];
$current = Repository::current_market();
$only    = 'country' === $scope ? $current : (string) $args['filter'];
$base    = $args['base'] ? $args['base'] : home_url( '/' );

// Countries that have published evidence, A to Z (filter chips).
$index     = Repository::evidence_index();
$countries = array();
foreach ( $index as $row ) {
	$market = Repository::market( $row['market'] );
	if ( $market ) {
		$countries[ $market['code'] ] = $market;
	}
}
uasort(
	$countries,
	static function ( $a, $b ) {
		return strcasecmp( remove_accents( $a['name'] ), remove_accents( $b['name'] ) );
	}
);

$published = array_values(
	array_filter(
		$index,
		static function ( $row ) use ( $only ) {
			return '' === $only || $row['market'] === $only;
		}
	)
);
usort(
	$published,
	static function ( $a, $b ) {
		return strcmp( (string) $b['date'], (string) $a['date'] );
	}
);

$pending = array();
if ( $args['showPending'] ) {
	foreach ( Repository::brokers() as $broker ) {
		foreach ( Repository::tested_markets( $broker ) as $code ) {
			if ( ( '' === $only || $code === $only ) && ! Repository::evidence_for( $broker['id'], $code ) ) {
				$pending[] = array( $broker, $code );
			}
		}
	}
}

$only_market     = $only ? Repository::market( $only ) : null;
$published_title = $args['publishedTitle'] ? $args['publishedTitle'] : __( 'Published evidence', 'fxt-core' );
$pending_title   = $args['pendingTitle'] ? $args['pendingTitle'] : __( 'Testing in progress', 'fxt-core' );
?>
<div class="evidence-index">
	<?php if ( 'all' === $scope && count( $countries ) > 1 ) : ?>
		<nav class="filter-chips" aria-label="<?php esc_attr_e( 'Filter evidence by country', 'fxt-core' ); ?>">
			<a class="filter-chip<?php echo '' === $only ? ' is-active' : ''; ?>" href="<?php echo esc_url( remove_query_arg( 'evidence_country', $base ) ); ?>"<?php echo '' === $only ? ' aria-current="true"' : ''; ?>><?php esc_html_e( 'All countries', 'fxt-core' ); ?></a>
			<?php foreach ( $countries as $code => $market ) : ?>
				<a class="filter-chip<?php echo $only === $code ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'evidence_country', $code, $base ) ); ?>"<?php echo $only === $code ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $market['name'] ); ?></a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

	<h2 class="doc-section__title">
		<?php
		echo esc_html( $only_market ? sprintf( /* translators: 1: heading, 2: country */ __( '%1$s: %2$s', 'fxt-core' ), $published_title, $only_market['name'] ) : $published_title );
		?>
		<span class="count"><?php echo (int) count( $published ); ?></span>
	</h2>
	<?php if ( $published ) : ?>
		<ul class="link-cards">
			<?php foreach ( $published as $row ) : ?>
				<?php
				$market = Repository::market( $row['market'] );
				$record = Repository::evidence( $row['id'] );
				$tests  = $record ? count( $record['deposits'] ) + count( $record['withdrawals'] ) : 0;
				?>
				<li>
					<a class="link-card" href="<?php echo esc_url( $row['url'] ); ?>">
						<span class="u-label">
							<?php
							echo esc_html( $market ? $market['name'] : $row['market'] );
							if ( $tests ) {
								/* translators: %d: number of payment tests */
								echo ' &middot; ' . esc_html( sprintf( _n( '%d payment test', '%d payment tests', $tests, 'fxt-core' ), $tests ) );
							}
							if ( $record && $record['period']['to'] ) {
								echo ' &middot; ' . esc_html( fxt_core_date( $record['period']['to'] ) );
							}
							?>
						</span>
						<span class="link-card__text"><?php echo esc_html( $row['title'] ); ?></span>
						<?php echo fxt_core_icon( 'arrow-right', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p class="empty-state">
			<?php
			echo esc_html(
				$only_market
					/* translators: %s: country */
					? sprintf( __( 'No evidence has been published for %s yet.', 'fxt-core' ), $only_market['name'] )
					: __( 'No evidence has been published yet.', 'fxt-core' )
			);
			?>
		</p>
	<?php endif; ?>

	<?php if ( $pending ) : ?>
		<h2 class="doc-section__title"><?php echo esc_html( $pending_title ); ?> <span class="count"><?php echo (int) count( $pending ); ?></span></h2>
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
						<th scope="row"><a href="<?php echo esc_url( add_query_arg( 'country', $code, $broker['url'] ) ); ?>"><?php echo esc_html( $broker['name'] ); ?></a></th>
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
