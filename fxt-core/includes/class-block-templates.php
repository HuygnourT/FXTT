<?php
/**
 * Default block layouts for new posts.
 *
 * A new Broker Review opens in Gutenberg with the same eleven-section structure
 * as the prototype: editorial core blocks (headings, paragraphs, lists) with
 * fxt/broker-data blocks where structured data is shown. Editors can reorder,
 * remove or add blocks; the template is not locked.
 *
 * @package FXT\Core
 */

namespace FXT\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Block templates for the custom post types.
 */
final class Block_Templates {

	/**
	 * Helper: a level-2 heading with a stable anchor (used by the table of contents).
	 *
	 * @param string $text   Heading text.
	 * @param string $anchor Anchor id.
	 * @return array
	 */
	private static function h2( $text, $anchor ) {
		return array( 'core/heading', array( 'level' => 2, 'content' => $text, 'anchor' => $anchor ) );
	}

	/**
	 * Helper: a paragraph with placeholder guidance.
	 *
	 * @param string $placeholder Placeholder text.
	 * @param string $class       Optional class name (block style).
	 * @return array
	 */
	private static function p( $placeholder, $class = '' ) {
		$attrs = array( 'placeholder' => $placeholder );
		if ( $class ) {
			$attrs['className'] = $class;
		}
		return array( 'core/paragraph', $attrs );
	}

	/**
	 * Broker review structure.
	 *
	 * @return array
	 */
	public static function broker() {
		return array(
			self::h2( __( 'Quick facts', 'fxt-core' ), 'quick-facts' ),
			array( 'fxt/broker-data', array( 'section' => 'quick-facts' ) ),

			self::h2( __( 'Trader verdict', 'fxt-core' ), 'verdict' ),
			array( 'core/quote', array( 'className' => 'is-style-verdict' ), array( self::p( __( 'Two or three analytical sentences: what we found and the main trade-off.', 'fxt-core' ) ) ) ),

			self::h2( __( 'Regulation and client protection', 'fxt-core' ), 'regulation' ),
			self::p( __( 'Explain which entity serves clients and what that means for protection.', 'fxt-core' ), 'is-style-lead' ),
			array( 'fxt/broker-data', array( 'section' => 'regulation' ) ),

			self::h2( __( 'Trading costs and execution', 'fxt-core' ), 'costs' ),
			self::p( __( 'How costs were measured (sessions, instruments).', 'fxt-core' ), 'is-style-lead' ),
			array( 'fxt/broker-data', array( 'section' => 'costs' ) ),

			self::h2( __( 'Platforms', 'fxt-core' ), 'platforms' ),
			array( 'fxt/broker-data', array( 'section' => 'platforms' ) ),

			self::h2( __( 'Deposit and withdrawal', 'fxt-core' ), 'payments' ),
			self::p( __( 'Payment options depend on the entity and the country.', 'fxt-core' ), 'is-style-lead' ),
			array( 'fxt/broker-data', array( 'section' => 'payments' ) ),

			self::h2( __( 'Account types', 'fxt-core' ), 'accounts' ),
			array( 'fxt/broker-data', array( 'section' => 'accounts' ) ),

			self::h2( __( 'Pros and cons', 'fxt-core' ), 'pros-cons' ),
			array(
				'core/columns',
				array( 'className' => 'is-style-pros-cons' ),
				array(
					array(
						'core/column',
						array(),
						array(
							array( 'core/heading', array( 'level' => 3, 'content' => __( 'What worked in our research', 'fxt-core' ) ) ),
							array( 'core/list', array( 'className' => 'is-style-pros' ) ),
						),
					),
					array(
						'core/column',
						array(),
						array(
							array( 'core/heading', array( 'level' => 3, 'content' => __( 'What to weigh carefully', 'fxt-core' ) ) ),
							array( 'core/list', array( 'className' => 'is-style-cons' ) ),
						),
					),
				),
			),

			self::h2( __( 'Research evidence', 'fxt-core' ), 'evidence' ),
			array( 'fxt/broker-data', array( 'section' => 'evidence' ) ),

			self::h2( __( 'How we evaluated this broker', 'fxt-core' ), 'method' ),
			array( 'core/list', array( 'className' => 'is-style-check' ) ),

			self::h2( __( 'Final verdict', 'fxt-core' ), 'final-verdict' ),
			array( 'fxt/broker-data', array( 'section' => 'final-score' ) ),
			self::p( __( 'Summarise the research without promotional language.', 'fxt-core' ), 'is-style-lead' ),
		);
	}

	/**
	 * Test evidence structure.
	 *
	 * @return array
	 */
	public static function evidence() {
		return array(
			self::h2( __( 'Test summary', 'fxt-core' ), 'summary' ),
			array( 'fxt/evidence-data', array( 'section' => 'summary' ) ),
			self::h2( __( 'Test timeline', 'fxt-core' ), 'timeline' ),
			array( 'fxt/evidence-data', array( 'section' => 'timeline' ) ),
			self::h2( __( 'Deposit tests', 'fxt-core' ), 'deposits' ),
			array( 'fxt/evidence-data', array( 'section' => 'deposits' ) ),
			self::h2( __( 'Withdrawal tests', 'fxt-core' ), 'withdrawals' ),
			array( 'fxt/evidence-data', array( 'section' => 'withdrawals' ) ),
			self::h2( __( 'What happened during our test?', 'fxt-core' ), 'notes' ),
			array( 'core/list', array( 'className' => 'is-style-notes' ) ),
		);
	}
}
