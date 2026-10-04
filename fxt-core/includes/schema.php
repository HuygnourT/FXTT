<?php
/**
 * Field schemas: the single source of truth for structured data.
 *
 * Each schema drives three things from one definition:
 *   1. register_post_meta / register_term_meta / user meta + REST schema
 *   2. the admin form (meta boxes, term fields, profile fields, settings page)
 *   3. sanitization on save
 *
 * Field keys:
 *   type      text|textarea|number|url|date|datetime|select|checkbox|list|group|repeater|market_map|post|media
 *   label     admin label
 *   help      optional description under the field
 *   options   select options [value => label]
 *   fields    sub-fields for group / repeater / market_map
 *   mask      'digits' | 'name' : values are masked before they are stored
 *   min, max, step for numbers; post_type for post; default value
 *
 * @package FXT\Core
 */

namespace FXT\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Schema registry.
 */
final class Schema {

	/**
	 * Availability of a broker in one market.
	 *
	 * @return array
	 */
	public static function availability() {
		return array(
			'pending'    => __( 'Availability pending', 'fxt-core' ),
			'yes'        => __( 'Available', 'fxt-core' ),
			'restricted' => __( 'Not accepting residents', 'fxt-core' ),
		);
	}

	/**
	 * Status of a single test.
	 *
	 * @return array
	 */
	public static function test_status() {
		return array(
			''            => __( 'Not tested', 'fxt-core' ),
			'planned'     => __( 'Planned', 'fxt-core' ),
			'in-progress' => __( 'In progress', 'fxt-core' ),
			'complete'    => __( 'Completed', 'fxt-core' ),
		);
	}

	/**
	 * Overall status of a review.
	 *
	 * @return array
	 */
	public static function review_status() {
		return array(
			'published' => __( 'Published', 'fxt-core' ),
			'updating'  => __( 'Update in progress', 'fxt-core' ),
			'pending'   => __( 'Research pending', 'fxt-core' ),
		);
	}

	/**
	 * Score categories, in display order. Weights live in plugin settings.
	 *
	 * @return array
	 */
	public static function categories() {
		return array(
			'regulation'   => __( 'Regulation and client protection', 'fxt-core' ),
			'availability' => __( 'Country availability', 'fxt-core' ),
			'costs'        => __( 'Trading costs and execution', 'fxt-core' ),
			'platforms'    => __( 'Trading platforms', 'fxt-core' ),
			'local'        => __( 'Local services and language', 'fxt-core' ),
			'support'      => __( 'Customer support', 'fxt-core' ),
		);
	}

	/**
	 * Broker review meta.
	 *
	 * @return array
	 */
	public static function broker() {
		$score_fields = array();
		foreach ( self::categories() as $key => $label ) {
			$score_fields[ $key ] = array( 'type' => 'number', 'label' => $label, 'min' => 0, 'max' => 5, 'step' => 0.1 );
		}

		return array(
			'fxt_monogram'        => array( 'type' => 'text', 'label' => __( 'Monogram', 'fxt-core' ), 'help' => __( 'Two or three letters shown when no logo is set.', 'fxt-core' ) ),
			'fxt_status'          => array( 'type' => 'select', 'label' => __( 'Review status', 'fxt-core' ), 'options' => self::review_status(), 'default' => 'published' ),
			'fxt_score'           => array( 'type' => 'number', 'label' => __( 'Research score (0 to 5)', 'fxt-core' ), 'min' => 0, 'max' => 5, 'step' => 0.1, 'help' => __( 'Leave empty to show "Pending".', 'fxt-core' ) ),
			'fxt_score_breakdown' => array( 'type' => 'group', 'label' => __( 'Category scores', 'fxt-core' ), 'fields' => $score_fields ),
			'fxt_reviewed'        => array( 'type' => 'date', 'label' => __( 'Last reviewed', 'fxt-core' ), 'help' => __( 'Shown in bylines. Update only when the research is re-checked, not for typo fixes.', 'fxt-core' ) ),
			'fxt_founded'         => array( 'type' => 'text', 'label' => __( 'Founded', 'fxt-core' ) ),
			'fxt_min_deposit'     => array( 'type' => 'number', 'label' => __( 'Minimum deposit (USD)', 'fxt-core' ), 'min' => 0, 'step' => 1 ),
			'fxt_currencies'      => array( 'type' => 'list', 'label' => __( 'Base currencies', 'fxt-core' ), 'help' => __( 'Comma separated, e.g. USD, EUR, VND', 'fxt-core' ) ),
			'fxt_other_platforms' => array( 'type' => 'text', 'label' => __( 'Other platforms', 'fxt-core' ) ),
			'fxt_trading'         => array(
				'type'   => 'group',
				'label'  => __( 'Trading conditions', 'fxt-core' ),
				'fields' => array(
					'leverage'    => array( 'type' => 'text', 'label' => __( 'Maximum leverage', 'fxt-core' ) ),
					'execution'   => array( 'type' => 'text', 'label' => __( 'Execution model', 'fxt-core' ) ),
					'order_types' => array( 'type' => 'text', 'label' => __( 'Order types', 'fxt-core' ) ),
				),
			),
			'fxt_costs'           => array(
				'type'   => 'group',
				'label'  => __( 'Trading costs (observed)', 'fxt-core' ),
				'fields' => array(
					'spread'     => array( 'type' => 'text', 'label' => __( 'Typical spread', 'fxt-core' ) ),
					'commission' => array( 'type' => 'text', 'label' => __( 'Commission', 'fxt-core' ) ),
					'swap'       => array( 'type' => 'text', 'label' => __( 'Swap', 'fxt-core' ) ),
					'other_fees' => array( 'type' => 'text', 'label' => __( 'Other fees', 'fxt-core' ) ),
				),
			),
			'fxt_payments'        => array(
				'type'   => 'group',
				'label'  => __( 'Payment rails', 'fxt-core' ),
				'fields' => array(
					'bank'          => array( 'type' => 'checkbox', 'label' => __( 'Bank transfer', 'fxt-core' ) ),
					'cards'         => array( 'type' => 'checkbox', 'label' => __( 'Cards', 'fxt-core' ) ),
					'ewallets'      => array( 'type' => 'checkbox', 'label' => __( 'E-wallets', 'fxt-core' ) ),
					'crypto'        => array( 'type' => 'checkbox', 'label' => __( 'Crypto', 'fxt-core' ) ),
					'time_local'    => array( 'type' => 'text', 'label' => __( 'Processing time: local methods', 'fxt-core' ) ),
					'time_cards'    => array( 'type' => 'text', 'label' => __( 'Processing time: cards', 'fxt-core' ) ),
					'time_ewallets' => array( 'type' => 'text', 'label' => __( 'Processing time: e-wallets', 'fxt-core' ) ),
					'time_crypto'   => array( 'type' => 'text', 'label' => __( 'Processing time: crypto', 'fxt-core' ) ),
				),
			),
			'fxt_accounts'        => array(
				'type'   => 'repeater',
				'label'  => __( 'Account types', 'fxt-core' ),
				'fields' => array(
					'name'        => array( 'type' => 'text', 'label' => __( 'Account', 'fxt-core' ) ),
					'min_deposit' => array( 'type' => 'text', 'label' => __( 'Min deposit', 'fxt-core' ) ),
					'spread'      => array( 'type' => 'text', 'label' => __( 'Spread from', 'fxt-core' ) ),
					'commission'  => array( 'type' => 'text', 'label' => __( 'Commission', 'fxt-core' ) ),
					'execution'   => array( 'type' => 'text', 'label' => __( 'Execution', 'fxt-core' ) ),
					'best_for'    => array( 'type' => 'text', 'label' => __( 'Most relevant for', 'fxt-core' ) ),
				),
			),
			'fxt_entities'        => array(
				'type'   => 'repeater',
				'label'  => __( 'Legal entities', 'fxt-core' ),
				'help'   => __( 'Every company the brand uses to onboard clients. "Key" is referenced from the market table below.', 'fxt-core' ),
				'fields' => array(
					'key'          => array( 'type' => 'text', 'label' => __( 'Key', 'fxt-core' ) ),
					'name'         => array( 'type' => 'text', 'label' => __( 'Entity name', 'fxt-core' ) ),
					'regulator'    => array( 'type' => 'text', 'label' => __( 'Regulator', 'fxt-core' ) ),
					'licence'      => array( 'type' => 'text', 'label' => __( 'Licence number', 'fxt-core' ) ),
					'jurisdiction' => array( 'type' => 'text', 'label' => __( 'Jurisdiction', 'fxt-core' ) ),
					'protection'   => array( 'type' => 'text', 'label' => __( 'Client protection', 'fxt-core' ) ),
					'verified'     => array( 'type' => 'checkbox', 'label' => __( 'Verified on official register', 'fxt-core' ) ),
				),
			),
			'fxt_markets'         => array(
				'type'   => 'market_map',
				'label'  => __( 'Conditions by country', 'fxt-core' ),
				'help'   => __( 'Availability, entity and test status for each market. Countries are managed under Broker Reviews > Markets.', 'fxt-core' ),
				'fields' => array(
					'availability'   => array( 'type' => 'select', 'label' => __( 'Availability', 'fxt-core' ), 'options' => self::availability() ),
					'entity_key'     => array( 'type' => 'text', 'label' => __( 'Entity key', 'fxt-core' ) ),
					'local_payments' => array( 'type' => 'text', 'label' => __( 'Local payment methods', 'fxt-core' ) ),
					'deposit'        => array( 'type' => 'select', 'label' => __( 'Deposit tests', 'fxt-core' ), 'options' => self::test_status() ),
					'withdrawal'     => array( 'type' => 'select', 'label' => __( 'Withdrawal tests', 'fxt-core' ), 'options' => self::test_status() ),
					'platform'       => array( 'type' => 'select', 'label' => __( 'Platform tests', 'fxt-core' ), 'options' => self::test_status() ),
					'support'        => array( 'type' => 'select', 'label' => __( 'Support tests', 'fxt-core' ), 'options' => self::test_status() ),
					'last_tested'    => array( 'type' => 'date', 'label' => __( 'Last tested', 'fxt-core' ) ),
				),
			),
			'fxt_affiliate_url'   => array( 'type' => 'url', 'label' => __( 'Affiliate URL', 'fxt-core' ), 'help' => __( 'Rendered with rel="sponsored nofollow" and an "affiliate link" label.', 'fxt-core' ) ),
		);
	}

	/**
	 * Test evidence meta. Sensitive identifiers are masked on save.
	 *
	 * @return array
	 */
	public static function evidence() {
		return array(
			'fxt_broker_id'     => array( 'type' => 'post', 'label' => __( 'Broker', 'fxt-core' ), 'post_type' => 'fxt_broker' ),
			'fxt_market'        => array( 'type' => 'select', 'label' => __( 'Country', 'fxt-core' ), 'options' => 'markets' ),
			'fxt_status'        => array( 'type' => 'select', 'label' => __( 'Research status', 'fxt-core' ), 'options' => array( 'completed' => __( 'Completed', 'fxt-core' ), 'in-progress' => __( 'In progress', 'fxt-core' ) ) ),
			'fxt_period'        => array(
				'type'   => 'group',
				'label'  => __( 'Test period', 'fxt-core' ),
				'fields' => array(
					'from' => array( 'type' => 'date', 'label' => __( 'From', 'fxt-core' ) ),
					'to'   => array( 'type' => 'date', 'label' => __( 'To', 'fxt-core' ) ),
				),
			),
			'fxt_account_label' => array( 'type' => 'text', 'label' => __( 'Account type tested', 'fxt-core' ) ),
			'fxt_summary'       => array(
				'type'   => 'repeater',
				'label'  => __( 'Test summary cards', 'fxt-core' ),
				'fields' => array(
					'icon'   => array( 'type' => 'select', 'label' => __( 'Icon', 'fxt-core' ), 'options' => array( 'file' => __( 'Document', 'fxt-core' ), 'shield-check' => __( 'Shield', 'fxt-core' ), 'arrow-right' => __( 'Arrow', 'fxt-core' ), 'chart' => __( 'Chart', 'fxt-core' ), 'book' => __( 'Book', 'fxt-core' ), 'check' => __( 'Check', 'fxt-core' ) ) ),
					'title'  => array( 'type' => 'text', 'label' => __( 'Title', 'fxt-core' ) ),
					'value'  => array( 'type' => 'text', 'label' => __( 'Value', 'fxt-core' ) ),
					'detail' => array( 'type' => 'text', 'label' => __( 'Detail', 'fxt-core' ) ),
				),
			),
			'fxt_timeline'      => array(
				'type'   => 'repeater',
				'label'  => __( 'Test timeline', 'fxt-core' ),
				'fields' => array(
					'at'    => array( 'type' => 'datetime', 'label' => __( 'Date and time', 'fxt-core' ) ),
					'title' => array( 'type' => 'text', 'label' => __( 'Step', 'fxt-core' ) ),
					'note'  => array( 'type' => 'text', 'label' => __( 'Note', 'fxt-core' ) ),
					'ref'   => array( 'type' => 'text', 'label' => __( 'Test ID (optional)', 'fxt-core' ) ),
				),
			),
			'fxt_deposits'      => array(
				'type'   => 'repeater',
				'label'  => __( 'Deposit tests', 'fxt-core' ),
				'fields' => array(
					'id'       => array( 'type' => 'text', 'label' => __( 'Test ID', 'fxt-core' ) ),
					'date'     => array( 'type' => 'date', 'label' => __( 'Date', 'fxt-core' ) ),
					'method'   => array( 'type' => 'text', 'label' => __( 'Method', 'fxt-core' ) ),
					'currency' => array( 'type' => 'text', 'label' => __( 'Currency', 'fxt-core' ) ),
					'amount'   => array( 'type' => 'text', 'label' => __( 'Amount', 'fxt-core' ) ),
					'time'     => array( 'type' => 'text', 'label' => __( 'Processing time', 'fxt-core' ) ),
					'fee'      => array( 'type' => 'text', 'label' => __( 'Fee', 'fxt-core' ) ),
					'result'   => array( 'type' => 'text', 'label' => __( 'Result', 'fxt-core' ) ),
				),
			),
			'fxt_withdrawals'   => array(
				'type'   => 'repeater',
				'label'  => __( 'Withdrawal tests', 'fxt-core' ),
				'fields' => array(
					'id'        => array( 'type' => 'text', 'label' => __( 'Test ID', 'fxt-core' ) ),
					'date'      => array( 'type' => 'date', 'label' => __( 'Date', 'fxt-core' ) ),
					'method'    => array( 'type' => 'text', 'label' => __( 'Method', 'fxt-core' ) ),
					'currency'  => array( 'type' => 'text', 'label' => __( 'Currency', 'fxt-core' ) ),
					'amount'    => array( 'type' => 'text', 'label' => __( 'Amount', 'fxt-core' ) ),
					'requested' => array( 'type' => 'datetime', 'label' => __( 'Requested', 'fxt-core' ) ),
					'received'  => array( 'type' => 'datetime', 'label' => __( 'Received', 'fxt-core' ) ),
					'time'      => array( 'type' => 'text', 'label' => __( 'Processing time', 'fxt-core' ) ),
					'fee'       => array( 'type' => 'text', 'label' => __( 'Fee', 'fxt-core' ) ),
					'result'    => array( 'type' => 'text', 'label' => __( 'Result', 'fxt-core' ) ),
				),
			),
			'fxt_records'       => array(
				'type'   => 'repeater',
				'label'  => __( 'Evidence records (viewer)', 'fxt-core' ),
				'help'   => __( 'Account numbers, names, bank details and references are masked automatically when saved. Steps: one per line as "YYYY-MM-DD HH:MM | description".', 'fxt-core' ),
				'fields' => array(
					'test_id'       => array( 'type' => 'text', 'label' => __( 'Test ID', 'fxt-core' ) ),
					'steps'         => array( 'type' => 'textarea', 'label' => __( 'Transaction steps', 'fxt-core' ) ),
					'broker_status' => array( 'type' => 'text', 'label' => __( 'Broker status', 'fxt-core' ) ),
					'bank_status'   => array( 'type' => 'text', 'label' => __( 'Bank / payment status', 'fxt-core' ) ),
					'account'       => array( 'type' => 'text', 'label' => __( 'Trading account', 'fxt-core' ), 'mask' => 'digits' ),
					'holder'        => array( 'type' => 'text', 'label' => __( 'Account holder', 'fxt-core' ), 'mask' => 'name' ),
					'bank'          => array( 'type' => 'text', 'label' => __( 'Bank account / card', 'fxt-core' ), 'mask' => 'digits' ),
					'reference'     => array( 'type' => 'text', 'label' => __( 'Transaction reference', 'fxt-core' ), 'mask' => 'digits' ),
					'note'          => array( 'type' => 'textarea', 'label' => __( 'Researcher note', 'fxt-core' ) ),
				),
			),
		);
	}

	/**
	 * Market (country) term meta.
	 *
	 * @return array
	 */
	public static function market() {
		return array(
			'fxt_iso'      => array( 'type' => 'text', 'label' => __( 'ISO code', 'fxt-core' ), 'help' => __( 'Two letters, e.g. VN', 'fxt-core' ) ),
			'fxt_currency' => array( 'type' => 'text', 'label' => __( 'Local currency', 'fxt-core' ) ),
			'fxt_status'   => array( 'type' => 'select', 'label' => __( 'Research status', 'fxt-core' ), 'options' => array( 'published' => __( 'Published', 'fxt-core' ), 'in-progress' => __( 'In progress', 'fxt-core' ), 'planned' => __( 'Planned', 'fxt-core' ) ) ),
			'fxt_order'    => array( 'type' => 'number', 'label' => __( 'Display order', 'fxt-core' ), 'step' => 1 ),
		);
	}

	/**
	 * Author profile (user meta). The core "Biographical Info" field is the long bio.
	 *
	 * @return array
	 */
	public static function author() {
		return array(
			'fxt_role_title' => array( 'type' => 'text', 'label' => __( 'Role / title', 'fxt-core' ) ),
			'fxt_short_bio'  => array( 'type' => 'textarea', 'label' => __( 'Short bio (bylines and author boxes)', 'fxt-core' ) ),
			'fxt_avatar_id'  => array( 'type' => 'media', 'label' => __( 'Profile photo', 'fxt-core' ) ),
			'fxt_location'   => array( 'type' => 'text', 'label' => __( 'Based in', 'fxt-core' ) ),
			'fxt_since'      => array( 'type' => 'text', 'label' => __( 'Reviewing since', 'fxt-core' ) ),
			'fxt_languages'  => array( 'type' => 'list', 'label' => __( 'Languages', 'fxt-core' ) ),
			'fxt_markets'    => array( 'type' => 'list', 'label' => __( 'Markets tested in person (ISO codes)', 'fxt-core' ) ),
			'fxt_expertise'  => array( 'type' => 'list', 'label' => __( 'Areas of expertise', 'fxt-core' ) ),
			'fxt_stats'      => array(
				'type'   => 'repeater',
				'label'  => __( 'Research record figures', 'fxt-core' ),
				'fields' => array(
					'value' => array( 'type' => 'text', 'label' => __( 'Value', 'fxt-core' ) ),
					'label' => array( 'type' => 'text', 'label' => __( 'Label', 'fxt-core' ) ),
				),
			),
			'fxt_principles' => array( 'type' => 'list', 'label' => __( 'Review principles (one per line)', 'fxt-core' ), 'lines' => true ),
			'fxt_disclosure' => array( 'type' => 'textarea', 'label' => __( 'Disclosure', 'fxt-core' ) ),
			'fxt_simulated'  => array( 'type' => 'checkbox', 'label' => __( 'Show "Simulated profile" label', 'fxt-core' ) ),
		);
	}

	/**
	 * Site-wide settings (one option: fxt_settings).
	 *
	 * @return array
	 */
	public static function settings() {
		$weights = array();
		foreach ( self::categories() as $key => $label ) {
			$weights[ $key ] = array( 'type' => 'number', 'label' => $label, 'min' => 0, 'max' => 100, 'step' => 1 );
		}

		return array(
			'banner_enabled'       => array( 'type' => 'checkbox', 'label' => __( 'Show prototype banner', 'fxt-core' ) ),
			'banner_text'          => array( 'type' => 'textarea', 'label' => __( 'Banner text', 'fxt-core' ) ),
			'sample_labels'        => array( 'type' => 'checkbox', 'label' => __( 'Show "Sample data" labels', 'fxt-core' ), 'help' => __( 'Turn off once research data is verified.', 'fxt-core' ) ),
			'default_market'       => array( 'type' => 'select', 'label' => __( 'Default country', 'fxt-core' ), 'options' => 'markets', 'help' => __( 'Used until a visitor chooses a country. No location data is read.', 'fxt-core' ) ),
			'header_cta_label'     => array( 'type' => 'text', 'label' => __( 'Header button label', 'fxt-core' ) ),
			'header_cta_url'       => array( 'type' => 'url', 'label' => __( 'Header button URL', 'fxt-core' ) ),
			'footer_tagline'       => array( 'type' => 'textarea', 'label' => __( 'Footer tagline', 'fxt-core' ) ),
			'risk_warning'         => array( 'type' => 'textarea', 'label' => __( 'Risk warning', 'fxt-core' ) ),
			'affiliate_disclosure' => array( 'type' => 'textarea', 'label' => __( 'Affiliate disclosure', 'fxt-core' ) ),
			'social'               => array(
				'type'   => 'repeater',
				'label'  => __( 'Social links', 'fxt-core' ),
				'fields' => array(
					'label' => array( 'type' => 'text', 'label' => __( 'Label', 'fxt-core' ) ),
					'url'   => array( 'type' => 'url', 'label' => __( 'URL', 'fxt-core' ) ),
				),
			),
			'weights'              => array( 'type' => 'group', 'label' => __( 'Score weights (%)', 'fxt-core' ), 'help' => __( 'Must add up to 100.', 'fxt-core' ), 'fields' => $weights ),
			'pages'                => array(
				'type'   => 'group',
				'label'  => __( 'Key pages', 'fxt-core' ),
				'help'   => __( 'Used for links generated by blocks and templates.', 'fxt-core' ),
				'fields' => array(
					'directory'   => array( 'type' => 'post', 'post_type' => 'page', 'label' => __( 'Broker Reviews directory', 'fxt-core' ) ),
					'compare'     => array( 'type' => 'post', 'post_type' => 'page', 'label' => __( 'Compare Brokers', 'fxt-core' ) ),
					'evidence'    => array( 'type' => 'post', 'post_type' => 'page', 'label' => __( 'Test Evidence', 'fxt-core' ) ),
					'methodology' => array( 'type' => 'post', 'post_type' => 'page', 'label' => __( 'Research Methodology', 'fxt-core' ) ),
				),
			),
		);
	}

	/**
	 * Default settings, used before the settings page is saved.
	 *
	 * @return array
	 */
	public static function settings_defaults() {
		return array(
			'banner_enabled'       => false,
			'banner_text'          => '',
			'sample_labels'        => true,
			'default_market'       => 'VN',
			'header_cta_label'     => __( 'Find a Broker', 'fxt-core' ),
			'header_cta_url'       => '',
			'footer_tagline'       => '',
			'risk_warning'         => __( 'Forex and CFDs are complex financial products and involve a high risk of losing money. Make sure you understand the risks before trading. Nothing on this site is financial advice.', 'fxt-core' ),
			'affiliate_disclosure' => __( 'We may receive a commission when you open an account through some links on this site. This never affects ratings, rankings or research findings.', 'fxt-core' ),
			'social'               => array(),
			'weights'              => array( 'regulation' => 30, 'availability' => 20, 'costs' => 20, 'platforms' => 15, 'local' => 10, 'support' => 5 ),
			'pages'                => array( 'directory' => null, 'compare' => null, 'evidence' => null, 'methodology' => null ),
		);
	}
}
