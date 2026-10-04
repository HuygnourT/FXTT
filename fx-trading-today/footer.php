<?php
/**
 * Site footer: brand, menus, risk warning, disclosure.
 *
 * @package FX_Trading_Today
 */

defined( 'ABSPATH' ) || exit;

$fxt_tagline    = (string) fxt_tt_setting( 'footer_tagline', get_bloginfo( 'description' ) );
$fxt_risk       = (string) fxt_tt_setting( 'risk_warning', __( 'Forex and CFDs are complex financial products and involve a high risk of losing money. Make sure you understand the risks before trading. Nothing on this site is financial advice.', 'fx-trading-today' ) );
$fxt_disclosure = (string) fxt_tt_setting( 'affiliate_disclosure' );
$fxt_social     = (array) fxt_tt_setting( 'social', array() );
?>
</main>

<footer class="site-footer">
	<div class="container">
		<div class="site-footer__top">
			<div class="site-footer__brand">
				<span class="brand__name"><?php bloginfo( 'name' ); ?></span>
				<?php if ( $fxt_tagline ) : ?>
					<p class="site-footer__tagline"><?php echo esc_html( $fxt_tagline ); ?></p>
				<?php endif; ?>
				<?php if ( $fxt_social ) : ?>
					<ul class="site-footer__social">
						<?php foreach ( $fxt_social as $fxt_link ) : ?>
							<?php if ( ! empty( $fxt_link['url'] ) ) : ?>
								<li><a href="<?php echo esc_url( $fxt_link['url'] ); ?>" rel="noopener"><?php echo esc_html( $fxt_link['label'] ); ?></a></li>
							<?php endif; ?>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
			<?php fxt_tt_the_footer_nav(); ?>
		</div>

		<?php if ( $fxt_risk ) : ?>
			<div class="risk-warning" role="note" aria-labelledby="risk-title">
				<?php echo fxt_tt_icon( 'alert' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<div>
					<p class="risk-warning__title" id="risk-title"><?php esc_html_e( 'Risk warning', 'fx-trading-today' ); ?></p>
					<p class="risk-warning__text"><?php echo esc_html( $fxt_risk ); ?></p>
				</div>
			</div>
		<?php endif; ?>

		<div class="site-footer__bottom">
			<?php if ( $fxt_disclosure ) : ?>
				<p>
					<?php
					/* translators: %s: affiliate disclosure text */
					echo esc_html( sprintf( __( 'Affiliate disclosure: %s', 'fx-trading-today' ), $fxt_disclosure ) );
					?>
				</p>
			<?php endif; ?>
			<p>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></p>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
