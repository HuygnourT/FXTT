<?php
/**
 * Evidence header: meta strip and links to the other test countries.
 *
 * @var array $args { evidence: array }
 * @package FXT\Core
 */

use FXT\Core\Repository;

defined( 'ABSPATH' ) || exit;

$evidence = $args['evidence'];
$market   = Repository::market( $evidence['market'] );
$broker   = Repository::broker( $evidence['broker_id'] );
$author   = Repository::author( $evidence['author_id'] );
$statuses = array( 'completed' => __( 'Completed', 'fxt-core' ), 'in-progress' => __( 'In progress', 'fxt-core' ) );
?>
<dl class="meta-strip">
	<div><dt><?php esc_html_e( 'Country', 'fxt-core' ); ?></dt><dd><?php echo esc_html( $market ? $market['name'] : $evidence['market'] ); ?></dd></div>
	<div><dt><?php esc_html_e( 'Test period', 'fxt-core' ); ?></dt><dd>
		<?php
		/* translators: 1: start date, 2: end date */
		echo esc_html( sprintf( __( '%1$s to %2$s', 'fxt-core' ), fxt_core_date( $evidence['period']['from'] ), fxt_core_date( $evidence['period']['to'] ) ) );
		?>
	</dd></div>
	<div><dt><?php esc_html_e( 'Account type', 'fxt-core' ); ?></dt><dd><?php echo fxt_core_text( $evidence['account'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div>
	<div><dt><?php esc_html_e( 'Research status', 'fxt-core' ); ?></dt><dd><?php echo esc_html( isset( $statuses[ $evidence['status'] ] ) ? $statuses[ $evidence['status'] ] : $evidence['status'] ); ?></dd></div>
	<?php if ( $author ) : ?>
		<div><dt><?php esc_html_e( 'Tested by', 'fxt-core' ); ?></dt><dd><a href="<?php echo esc_url( $author['url'] ); ?>" rel="author"><?php echo esc_html( $author['name'] ); ?></a></dd></div>
	<?php endif; ?>
</dl>

<?php
if ( $broker ) :
	$others = array_diff( Repository::tested_markets( $broker ), array( $evidence['market'] ) );
	if ( $others ) :
		?>
		<p class="other-tests">
			<span class="u-label">
				<?php
				/* translators: %s: broker name */
				echo esc_html( sprintf( __( 'Other test countries for %s', 'fxt-core' ), $broker['name'] ) );
				?>
			</span>
			<?php
			$labels = \FXT\Core\Schema::test_status();
			foreach ( $others as $code ) :
				$other_market = Repository::market( $code );
				$other_ev     = Repository::evidence_for( $broker['id'], $code );
				$row          = Repository::market_row( $broker, $code );
				$state        = $other_ev ? __( 'evidence', 'fxt-core' ) : strtolower( $labels[ isset( $row['deposit'] ) ? (string) $row['deposit'] : '' ] );
				$href         = $other_ev ? $other_ev['url'] : add_query_arg( 'country', $code, $broker['url'] ) . '#payments';
				?>
				<a class="filter-chip" href="<?php echo esc_url( $href ); ?>"><?php echo esc_html( ( $other_market ? $other_market['name'] : $code ) . ': ' . $state ); ?></a>
			<?php endforeach; ?>
		</p>
		<?php
	endif;
endif;
