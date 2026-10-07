<?php
/**
 * Broker comparison widget (homepage preview and full Compare page).
 *
 * Works without JavaScript: the slot selectors are a GET form and "Compare All"
 * is a link. compare.js swaps the widget in place via the render endpoint.
 *
 * @var array $args {
 *     mode:     'preview'|'full',
 *     slots:    int,
 *     selected: string[] broker slugs,
 *     all:      bool,
 *     sync:     bool  whether JS mirrors the selection in the page URL
 *     base_url: string URL of the page holding the widget (links and form action)
 * }
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$mode     = 'full' === $args['mode'] ? 'full' : 'preview';
$slots    = max( 2, min( 4, (int) $args['slots'] ) );
$selected = array_slice( (array) $args['selected'], 0, $slots );
$show_all = ! empty( $args['all'] );
$code     = Repository::current_market();
$market   = Repository::market( $code );
$country  = $market ? $market['name'] : $code;
$all      = Repository::brokers();
$brokers  = $show_all ? $all : Repository::brokers_by_slug( $selected );
$selected = wp_list_pluck( Repository::brokers_by_slug( $selected ), 'slug' );
$compare  = fxt_core_page_url( 'compare' );
$here     = remove_query_arg( array( 'brokers', 'all', 'remove' ), $args['base_url'] );
$uid      = wp_unique_id( 'cmp-' );

/* Row helpers ------------------------------------------------------------ */
$entity  = static function ( $b ) use ( $code ) {
	return Repository::entity( $b, $code );
};
$row     = static function ( $b ) use ( $code ) {
	$r = Repository::market_row( $b, $code );
	return $r ? $r : array();
};
$yes     = static function ( $taxonomy_key, $slug ) {
	return static function ( $b ) use ( $taxonomy_key, $slug ) {
		return fxt_core_yesno( 'platforms' === $taxonomy_key ? isset( $b['platforms'][ $slug ] ) : in_array( $slug, $b['account_types'], true ) );
	};
};
$pay     = static function ( $key ) {
	return static function ( $b ) use ( $key ) {
		return fxt_core_yesno( ! empty( $b['payments'][ $key ] ) );
	};
};
$text    = static function ( $value ) {
	return fxt_core_text( $value );
};

// Platform and account rows follow the taxonomy terms (no fixed list).
$platform_rows = array();
foreach ( Repository::terms( \FXT\Core\Content_Types::PLATFORM ) as $term_row ) {
	$platform_rows[] = array( $term_row['name'], $yes( 'platforms', $term_row['slug'] ) );
}
$account_rows = array();
foreach ( Repository::terms( \FXT\Core\Content_Types::ACCOUNT_TYPE ) as $term_row ) {
	$account_rows[] = array( $term_row['name'], $yes( 'accounts', $term_row['slug'] ) );
}

/* translators: %s: country */
$in_country = sprintf( __( 'Available in %s', 'fxt-core' ), $country );

$preview_rows = array(
	'score'        => array( __( 'Research score', 'fxt-core' ), 'fxt_core_score' ),
	'availability' => array( $in_country, static function ( $b ) use ( $code ) {
		return fxt_core_badge( 'availability', Repository::availability( $b, $code ) );
	} ),
	/* translators: %s: country */
	'regulator'    => array( sprintf( __( 'Regulator for %s', 'fxt-core' ), $country ), static function ( $b ) use ( $entity ) {
		$e = $entity( $b );
		return fxt_core_unverified( $e['regulator'], $e['verified'] );
	} ),
	'deposit'      => array( __( 'Minimum deposit', 'fxt-core' ), static function ( $b ) {
		return esc_html( fxt_core_min_deposit( $b ) );
	} ),
	'cost'         => array( __( 'Typical EUR/USD cost', 'fxt-core' ), static function ( $b ) use ( $text ) {
		return $text( $b['costs']['spread'] );
	} ),
	'platforms'    => array( __( 'Platforms', 'fxt-core' ), static function ( $b ) use ( $text ) {
		return $text( implode( ', ', fxt_core_platform_names( $b ) ) );
	} ),
	/* translators: %s: ISO country code */
	'local'        => array( sprintf( __( 'Local payments (%s)', 'fxt-core' ), $code ), static function ( $b ) use ( $row, $text ) {
		$r = $row( $b );
		return $text( isset( $r['local_payments'] ) ? $r['local_payments'] : '' );
	} ),
	'researched'   => array( __( 'Countries researched', 'fxt-core' ), static function ( $b ) {
		/* translators: 1: researched, 2: total */
		return esc_html( sprintf( __( '%1$d of %2$d', 'fxt-core' ), count( Repository::tested_markets( $b ) ), count( Repository::researched_markets() ) ) );
	} ),
);

$groups = array(
	'overview'   => array(
		__( 'Overview', 'fxt-core' ),
		false,
		array(
			array( __( 'Research score', 'fxt-core' ), 'fxt_core_score' ),
			array( __( 'Review status', 'fxt-core' ), static function ( $b ) {
				return fxt_core_badge( 'status', $b['status'] );
			} ),
			array( __( 'Founded', 'fxt-core' ), static function ( $b ) {
				return fxt_core_unverified( $b['founded'], false );
			} ),
			array( __( 'Minimum deposit', 'fxt-core' ), static function ( $b ) {
				return esc_html( fxt_core_min_deposit( $b ) );
			} ),
			$preview_rows['availability'],
		),
	),
	'regulation' => array(
		__( 'Regulation', 'fxt-core' ),
		true,
		array(
			array( __( 'Legal entity', 'fxt-core' ), static function ( $b ) use ( $entity, $text ) {
				return $text( $entity( $b )['name'] );
			} ),
			array( __( 'Regulator', 'fxt-core' ), static function ( $b ) use ( $entity ) {
				$e = $entity( $b );
				return fxt_core_unverified( $e['regulator'], $e['verified'] );
			} ),
			array( __( 'Licence', 'fxt-core' ), static function ( $b ) use ( $entity, $text ) {
				return $text( $entity( $b )['licence'] );
			} ),
			array( __( 'Jurisdiction', 'fxt-core' ), static function ( $b ) use ( $entity, $text ) {
				return $text( $entity( $b )['jurisdiction'] );
			} ),
			array( __( 'Client protection', 'fxt-core' ), static function ( $b ) use ( $entity, $text ) {
				return $text( $entity( $b )['protection'] );
			} ),
			array( __( 'Group regulators', 'fxt-core' ), static function ( $b ) {
				return fxt_core_regulators( $b['regulators'], 3 );
			} ),
		),
	),
	'costs'      => array(
		__( 'Trading costs', 'fxt-core' ),
		false,
		array(
			array( __( 'Typical spread', 'fxt-core' ), static function ( $b ) use ( $text ) {
				return $text( $b['costs']['spread'] );
			} ),
			array( __( 'Commission', 'fxt-core' ), static function ( $b ) use ( $text ) {
				return $text( $b['costs']['commission'] );
			} ),
			array( __( 'Swap', 'fxt-core' ), static function ( $b ) use ( $text ) {
				return $text( $b['costs']['swap'] );
			} ),
			array( __( 'Other fees', 'fxt-core' ), static function ( $b ) use ( $text ) {
				return $text( $b['costs']['other_fees'] );
			} ),
		),
	),
	'trading'    => array(
		__( 'Trading', 'fxt-core' ),
		false,
		array(
			array( __( 'Maximum leverage', 'fxt-core' ), static function ( $b ) use ( $text ) {
				return $text( $b['trading']['leverage'] );
			} ),
			array( __( 'Execution', 'fxt-core' ), static function ( $b ) use ( $text ) {
				return $text( $b['trading']['execution'] );
			} ),
			array( __( 'Order types', 'fxt-core' ), static function ( $b ) use ( $text ) {
				return $text( $b['trading']['order_types'] );
			} ),
		),
	),
	'platforms'  => array(
		__( 'Platforms', 'fxt-core' ),
		false,
		array(
			...$platform_rows,
			array( __( 'Other', 'fxt-core' ), static function ( $b ) use ( $text ) {
				return $text( $b['other_platforms'] );
			} ),
		),
	),
	'accounts'   => array(
		__( 'Accounts', 'fxt-core' ),
		false,
		$account_rows,
	),
	'payments'   => array(
		__( 'Payments', 'fxt-core' ),
		true,
		array(
			array( __( 'Bank transfer', 'fxt-core' ), $pay( 'bank' ) ),
			array( __( 'Cards', 'fxt-core' ), $pay( 'cards' ) ),
			array( __( 'E-wallets', 'fxt-core' ), $pay( 'ewallets' ) ),
			array( __( 'Local payments', 'fxt-core' ), $preview_rows['local'][1] ),
			array( __( 'Crypto', 'fxt-core' ), $pay( 'crypto' ) ),
		),
	),
	'research'   => array(
		__( 'Research', 'fxt-core' ),
		true,
		array(
			array( __( 'Last tested', 'fxt-core' ), static function ( $b ) use ( $row ) {
				$r = $row( $b );
				return empty( $r['last_tested'] ) ? '<span class="muted">' . esc_html__( 'Not tested', 'fxt-core' ) . '</span>' : esc_html( fxt_core_date( $r['last_tested'] ) );
			} ),
			array( __( 'Deposit tested', 'fxt-core' ), static function ( $b ) use ( $row ) {
				$r = $row( $b );
				return fxt_core_badge( 'test', isset( $r['deposit'] ) ? (string) $r['deposit'] : '' );
			} ),
			array( __( 'Withdrawal tested', 'fxt-core' ), static function ( $b ) use ( $row ) {
				$r = $row( $b );
				return fxt_core_badge( 'test', isset( $r['withdrawal'] ) ? (string) $r['withdrawal'] : '' );
			} ),
			array( __( 'Country tests available', 'fxt-core' ), static function ( $b ) use ( $code ) {
				$codes = Repository::tested_markets( $b );
				if ( ! $codes ) {
					return '<span class="muted">' . esc_html__( 'None yet', 'fxt-core' ) . '</span>';
				}
				$evidence = Repository::evidence_for( $b['id'], $code );
				$out      = esc_html( implode( ', ', $codes ) );
				if ( $evidence ) {
					/* translators: %s: ISO country code */
					$out .= '<br><a class="cmp__evidence" href="' . esc_url( $evidence['url'] ) . '">' . esc_html( sprintf( __( 'View %s evidence', 'fxt-core' ), $code ) ) . '</a>';
				}
				return $out;
			} ),
		),
	),
);

// Admin choice (block settings): which preview rows and full-table groups to show, in order.
$row_keys     = isset( $args['rows'] ) ? (array) $args['rows'] : array_keys( $preview_rows );
$group_keys   = isset( $args['groups'] ) ? (array) $args['groups'] : array_keys( $groups );
$preview_rows = array_intersect_key( array_replace( array_flip( $row_keys ), $preview_rows ), array_flip( $row_keys ) );
$groups       = array_intersect_key( array_replace( array_flip( $group_keys ), $groups ), array_flip( $group_keys ) );

$cell = static function ( $render, $b ) {
	// Every renderer returns escaped HTML.
	return call_user_func( $render, $b );
};
?>
<div class="cmp-widget__inner" data-fxt-compare data-mode="<?php echo esc_attr( $mode ); ?>" data-slots="<?php echo (int) $slots; ?>" data-all="<?php echo $show_all ? '1' : '0'; ?>" data-sync="<?php echo empty( $args['sync'] ) ? '0' : '1'; ?>" data-selected="<?php echo esc_attr( implode( ',', $selected ) ); ?>" data-base="<?php echo esc_url( $here ); ?>" data-rows="<?php echo esc_attr( implode( ',', $row_keys ) ); ?>" data-groups="<?php echo esc_attr( implode( ',', $group_keys ) ); ?>">
	<div class="cmp-toolbar">
		<p class="cmp-context">
			<?php echo fxt_core_icon( 'map-pin', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php
			/* translators: %s: country */
			printf( esc_html__( 'Conditions for clients in %s', 'fxt-core' ), '<strong>' . esc_html( $country ) . '</strong>' );
			?>
			<button class="link-button" type="button" data-open-country><?php esc_html_e( 'Change country', 'fxt-core' ); ?></button>
		</p>
		<div class="cmp-toolbar__actions">
			<?php echo fxt_core_sample(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php if ( 'full' === $mode && $brokers ) : ?>
				<button class="btn btn--secondary btn--sm" type="button" data-cmp-expand hidden><?php esc_html_e( 'Expand all', 'fxt-core' ); ?></button>
				<button class="btn btn--secondary btn--sm" type="button" data-cmp-collapse hidden><?php esc_html_e( 'Collapse all', 'fxt-core' ); ?></button>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( $show_all ) : ?>
		<div class="cmp-all-note">
			<p>
				<?php
				/* translators: %d: number of brokers */
				printf( esc_html__( 'Showing all %d brokers. The attribute column stays fixed while you scroll sideways.', 'fxt-core' ), count( $all ) );
				?>
			</p>
			<a class="btn btn--secondary btn--sm" href="<?php echo esc_url( add_query_arg( 'brokers', implode( ',', $selected ), $here ) ); ?>" data-cmp-selection><?php esc_html_e( 'Back to my selection', 'fxt-core' ); ?></a>
		</div>
	<?php else : ?>
		<form class="cmp-slots" method="get" action="<?php echo esc_url( $here ); ?>" aria-label="<?php esc_attr_e( 'Brokers to compare', 'fxt-core' ); ?>" data-cmp-form>
			<?php
			for ( $i = 0; $i < $slots; $i++ ) :
				$slug    = isset( $selected[ $i ] ) ? $selected[ $i ] : '';
				$current = $slug ? Repository::brokers_by_slug( array( $slug ) ) : array();
				$current = $current ? $current[0] : null;
				$id      = $uid . '-slot-' . $i;
				?>
				<div class="cmp-slot <?php echo $current ? 'cmp-slot--filled' : 'cmp-slot--empty'; ?>">
					<?php echo $current ? fxt_core_logo( $current, 'sm' ) : fxt_core_icon( 'plus', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<label class="u-visually-hidden" for="<?php echo esc_attr( $id ); ?>">
						<?php
						/* translators: %s: broker name */
						echo $current ? esc_html( sprintf( __( 'Replace %s', 'fxt-core' ), $current['name'] ) ) : esc_html__( 'Add a broker to compare', 'fxt-core' );
						?>
					</label>
					<select class="cmp-slot__select" id="<?php echo esc_attr( $id ); ?>" name="brokers[]" data-slot="<?php echo (int) $i; ?>">
						<?php if ( ! $current ) : ?>
							<option value=""><?php esc_html_e( 'Select broker', 'fxt-core' ); ?></option>
						<?php endif; ?>
						<?php foreach ( $all as $option ) : ?>
							<?php
							if ( $option['slug'] !== $slug && in_array( $option['slug'], $selected, true ) ) {
								continue;
							}
							?>
							<option value="<?php echo esc_attr( $option['slug'] ); ?>"<?php selected( $option['slug'], $slug ); ?>><?php echo esc_html( $option['name'] ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php if ( $current ) : ?>
						<button class="cmp-slot__remove" type="submit" name="remove" value="<?php echo (int) $i; ?>" data-remove="<?php echo (int) $i; ?>">
							<?php echo fxt_core_icon( 'close', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span class="u-visually-hidden">
								<?php
								/* translators: %s: broker name */
								echo esc_html( sprintf( __( 'Remove %s from comparison', 'fxt-core' ), $current['name'] ) );
								?>
							</span>
						</button>
					<?php endif; ?>
				</div>
			<?php endfor; ?>
			<button class="btn btn--secondary btn--sm cmp-slots__submit" type="submit" data-cmp-submit><?php esc_html_e( 'Update comparison', 'fxt-core' ); ?></button>
		</form>
	<?php endif; ?>

	<?php if ( $brokers ) : ?>
		<div class="cmp-scroll" tabindex="0" role="region" aria-label="<?php esc_attr_e( 'Comparison table, scrolls horizontally', 'fxt-core' ); ?>">
			<table class="cmp">
				<caption class="u-visually-hidden">
					<?php
					/* translators: %s: country */
					echo esc_html( sprintf( __( 'Broker comparison for clients in %s', 'fxt-core' ), $country ) );
					?>
				</caption>
				<thead>
					<tr>
						<th scope="col" class="cmp__corner"><span class="u-label">
							<?php
							/* translators: %d: number of brokers */
							echo esc_html( sprintf( _n( '%d broker', '%d brokers', count( $brokers ), 'fxt-core' ), count( $brokers ) ) );
							?>
						</span></th>
						<?php foreach ( $brokers as $b ) : ?>
							<th scope="col" class="cmp__broker">
								<div class="cmp__broker-inner">
									<?php echo fxt_core_logo( $b, 'sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
									<div>
										<a class="cmp__broker-name" href="<?php echo esc_url( $b['url'] ); ?>"><?php echo esc_html( $b['name'] ); ?></a>
										<span class="cmp__broker-score"><?php echo esc_html( fxt_core_score_label( $b ) . ( null === $b['score'] ? '' : ' / 5' ) ); ?></span>
									</div>
								</div>
							</th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<?php if ( 'preview' === $mode ) : ?>
					<tbody>
						<?php foreach ( $preview_rows as $r ) : ?>
							<tr><th scope="row"><?php echo esc_html( $r[0] ); ?></th>
								<?php foreach ( $brokers as $b ) : ?>
									<td><?php echo $cell( $r[1], $b ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
						<tr class="cmp__actions-row"><th scope="row"><span class="u-visually-hidden"><?php esc_html_e( 'Review', 'fxt-core' ); ?></span></th>
							<?php foreach ( $brokers as $b ) : ?>
								<td><a class="link-strong" href="<?php echo esc_url( $b['url'] ); ?>"><?php esc_html_e( 'Read review', 'fxt-core' ); ?><span class="u-visually-hidden">: <?php echo esc_html( $b['name'] ); ?></span></a></td>
							<?php endforeach; ?>
						</tr>
					</tbody>
				<?php else : ?>
					<?php foreach ( $groups as $gid => $group ) : ?>
						<tbody class="cmp__group" data-group="<?php echo esc_attr( $gid ); ?>">
							<tr class="cmp__group-row">
								<th scope="colgroup" colspan="<?php echo (int) ( count( $brokers ) + 1 ); ?>">
									<button class="cmp__group-toggle" type="button" aria-expanded="true" data-toggle-group="<?php echo esc_attr( $gid ); ?>">
										<?php echo fxt_core_icon( 'chevron-down', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
										<span><?php echo esc_html( $group[0] ); ?></span>
										<?php if ( $group[1] ) : ?>
											<span class="cmp__group-context">
												<?php
												/* translators: %s: country */
												echo esc_html( sprintf( __( 'for %s', 'fxt-core' ), $country ) );
												?>
											</span>
										<?php endif; ?>
									</button>
								</th>
							</tr>
							<?php foreach ( $group[2] as $r ) : ?>
								<tr><th scope="row"><?php echo esc_html( $r[0] ); ?></th>
									<?php foreach ( $brokers as $b ) : ?>
										<td><?php echo $cell( $r[1], $b ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
									<?php endforeach; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					<?php endforeach; ?>
				<?php endif; ?>
			</table>
		</div>
	<?php else : ?>
		<div class="empty-state">
			<p class="empty-state__title"><?php esc_html_e( 'Select a broker to start comparing.', 'fxt-core' ); ?></p>
			<p>
				<?php
				/* translators: %d: number of slots */
				printf( esc_html__( 'Use the selectors above to add up to %d brokers, or compare every broker at once.', 'fxt-core' ), (int) $slots );
				?>
			</p>
		</div>
	<?php endif; ?>

	<div class="cmp-footer">
		<p class="cmp-footer__note"><?php esc_html_e( 'Costs are averages from our test sessions, not advertised minimums. Regulators marked unverified have not been checked against official registers.', 'fxt-core' ); ?></p>
		<div class="cmp-footer__actions">
			<?php if ( ! $show_all ) : ?>
				<a class="btn btn--secondary" href="<?php echo esc_url( add_query_arg( array( 'brokers' => implode( ',', $selected ), 'all' => 1 ), $here ) ); ?>" data-cmp-all><?php esc_html_e( 'Compare All Brokers', 'fxt-core' ); ?></a>
			<?php endif; ?>
			<?php if ( 'preview' === $mode && $compare ) : ?>
				<a class="btn btn--primary" href="<?php echo esc_url( fxt_core_compare_url( $selected, $show_all ) ); ?>"><?php esc_html_e( 'Open detailed comparison', 'fxt-core' ); ?> <?php echo fxt_core_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			<?php endif; ?>
		</div>
	</div>
</div>
