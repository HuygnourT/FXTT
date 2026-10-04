<?php
/**
 * Evidence viewer: one <template> per record plus the shared <dialog>.
 * Values are stored pre-masked (see Fields::mask), never unmasked.
 *
 * @var array $args { evidence: array }
 * @package FXT\Core
 */

defined( 'ABSPATH' ) || exit;

$evidence = $args['evidence'];
if ( ! $evidence['records'] ) {
	return;
}
$tests = array();
foreach ( array_merge( $evidence['deposits'], $evidence['withdrawals'] ) as $test ) {
	if ( ! empty( $test['id'] ) ) {
		$tests[ $test['id'] ] = $test;
	}
}
?>
<dialog class="evidence-dialog" aria-labelledby="evidence-dialog-title" data-evidence-dialog></dialog>
<?php
foreach ( $evidence['records'] as $id => $record ) :
	$test       = isset( $tests[ $id ] ) ? $tests[ $id ] : array();
	$is_deposit = 0 === strpos( strtoupper( $id ), 'DEP' );
	$get        = static function ( $arr, $key ) {
		return isset( $arr[ $key ] ) ? (string) $arr[ $key ] : '';
	};
	$masked     = array(
		__( 'Trading account', 'fxt-core' )                                                           => $get( $record, 'account' ),
		__( 'Account holder', 'fxt-core' )                                                            => $get( $record, 'holder' ),
		$is_deposit ? __( 'Paying account', 'fxt-core' ) : __( 'Receiving account', 'fxt-core' ) => $get( $record, 'bank' ),
		__( 'Transaction reference', 'fxt-core' )                                                     => $get( $record, 'reference' ),
	);
	$first      = $record['steps'] ? $record['steps'][0]['at'] : '';
	?>
	<template data-evidence-record="<?php echo esc_attr( $id ); ?>">
		<div class="evidence-dialog__head">
			<div>
				<p class="u-label"><?php esc_html_e( 'Test record', 'fxt-core' ); ?> <?php echo fxt_core_sample(); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
				<h2 class="evidence-dialog__title" id="evidence-dialog-title">
					<?php
					/* translators: 1: test ID, 2: payment method, 3: deposit|withdrawal */
					echo esc_html( sprintf( __( '%1$s: %2$s %3$s', 'fxt-core' ), $id, $get( $test, 'method' ), $is_deposit ? __( 'deposit', 'fxt-core' ) : __( 'withdrawal', 'fxt-core' ) ) );
					?>
				</h2>
			</div>
			<button class="icon-btn" type="button" data-close-evidence>
				<?php echo fxt_core_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span class="u-visually-hidden"><?php esc_html_e( 'Close evidence', 'fxt-core' ); ?></span>
			</button>
		</div>
		<div class="evidence-dialog__body">
			<figure class="placeholder evidence-shot">
				<?php echo fxt_core_icon( 'image', 'icon--lg' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<figcaption><?php esc_html_e( 'Screenshot placeholder. Names, account numbers and IDs are redacted before publication.', 'fxt-core' ); ?></figcaption>
				<span class="evidence-shot__redact" aria-hidden="true"></span>
				<span class="evidence-shot__redact evidence-shot__redact--short" aria-hidden="true"></span>
			</figure>
			<dl class="evidence-facts">
				<div><dt><?php esc_html_e( 'Amount', 'fxt-core' ); ?></dt><dd class="u-mono"><?php echo esc_html( trim( $get( $test, 'amount' ) . ' ' . $get( $test, 'currency' ) ) ); ?></dd></div>
				<div><dt><?php esc_html_e( 'Timestamp', 'fxt-core' ); ?></dt><dd class="u-mono"><?php echo esc_html( fxt_core_datetime( $first ) ); ?></dd></div>
				<div><dt><?php esc_html_e( 'Payment method', 'fxt-core' ); ?></dt><dd><?php echo esc_html( $get( $test, 'method' ) ); ?></dd></div>
				<div><dt><?php esc_html_e( 'Processing time', 'fxt-core' ); ?></dt><dd class="u-mono"><?php echo esc_html( $get( $test, 'time' ) ); ?></dd></div>
				<div><dt><?php esc_html_e( 'Broker status', 'fxt-core' ); ?></dt><dd><?php echo esc_html( $get( $record, 'broker_status' ) ); ?></dd></div>
				<div><dt><?php esc_html_e( 'Bank / payment status', 'fxt-core' ); ?></dt><dd><?php echo esc_html( $get( $record, 'bank_status' ) ); ?></dd></div>
				<?php foreach ( $masked as $label => $value ) : ?>
					<div><dt><?php echo esc_html( $label ); ?></dt><dd class="u-mono masked"><?php echo fxt_core_icon( 'lock', 'icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $value ); ?></dd></div>
				<?php endforeach; ?>
			</dl>
			<?php if ( $record['steps'] ) : ?>
				<h3 class="doc-subtitle"><?php esc_html_e( 'Transaction timeline', 'fxt-core' ); ?></h3>
				<ol class="v-timeline v-timeline--compact">
					<?php foreach ( $record['steps'] as $step ) : ?>
						<li class="v-timeline__item"><span class="v-timeline__dot" aria-hidden="true"></span><div class="v-timeline__body"><p class="v-timeline__title"><?php echo esc_html( $step['label'] ); ?></p><p class="v-timeline__meta"><time datetime="<?php echo esc_attr( $step['at'] ); ?>"><?php echo esc_html( fxt_core_datetime( $step['at'] ) ); ?></time></p></div></li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
			<?php if ( $get( $record, 'note' ) ) : ?>
				<h3 class="doc-subtitle"><?php esc_html_e( 'Researcher notes', 'fxt-core' ); ?></h3>
				<p><?php echo esc_html( $get( $record, 'note' ) ); ?></p>
			<?php endif; ?>
		</div>
	</template>
<?php endforeach; ?>
