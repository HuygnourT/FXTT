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
 *   type      text|textarea|number|url|date|datetime|select|checkbox|list|group|repeater|market_map|sources|post|media
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
	 * Data provenance: how a fact was obtained. Stored keys never change;
	 * labels are display text.
	 *
	 * @return array key => label ('' = not classified yet)
	 */
	public static function sources() {
		return array(
			''         => __( 'Not classified', 'fxt-core' ),
			'tested'   => __( 'Tested', 'fxt-core' ),
			'verified' => __( 'Verified at source', 'fxt-core' ),
			'stated'   => __( 'Broker-stated', 'fxt-core' ),
		);
	}

	/**
	 * Broker facts that carry their own source classification
	 * (rows of the review page). Key => admin label.
	 *
	 * Per-entity facts (legal entity, regulator, licence) use the "Source"
	 * field of each legal entity; per-country availability uses the
	 * "Availability source" field of each country row.
	 *
	 * @return array
	 */
	public static function source_rows() {
		return array(
			'regulators'       => __( 'Regulators (group)', 'fxt-core' ),
			'min_deposit'      => __( 'Minimum deposit', 'fxt-core' ),
			'platforms'        => __( 'Platforms', 'fxt-core' ),
			'accounts'         => __( 'Account types', 'fxt-core' ),
			'currencies'       => __( 'Base currencies', 'fxt-core' ),
			'leverage'         => __( 'Maximum leverage', 'fxt-core' ),
			'spread'           => __( 'Typical spread', 'fxt-core' ),
			'commission'       => __( 'Commission', 'fxt-core' ),
			'swap'             => __( 'Swap', 'fxt-core' ),
			'execution'        => __( 'Execution model', 'fxt-core' ),
			'order_types'      => __( 'Order types', 'fxt-core' ),
			'other_fees'       => __( 'Other fees', 'fxt-core' ),
			'payment_methods'  => __( 'Payment methods and processing times', 'fxt-core' ),
		);
	}

	/**
	 * Sub-fields of one source classification.
	 *
	 * @return array
	 */
	public static function source_fields() {
		return array(
			'source'  => array( 'type' => 'select', 'label' => __( 'Source', 'fxt-core' ), 'options' => self::sources() ),
			'ref'     => array( 'type' => 'text', 'label' => __( 'Reference (URL or note)', 'fxt-core' ) ),
			'checked' => array( 'type' => 'date', 'label' => __( 'Checked on', 'fxt-core' ) ),
		);
	}

	/**
	 * Source labels that editors can insert in content (inline format and
	 * the "Source label" block). Key => default text.
	 *
	 * @return array
	 */
	public static function label_kinds() {
		return array(
			'tested'     => __( 'Tested', 'fxt-core' ),
			'verified'   => __( 'Verified at source', 'fxt-core' ),
			'stated'     => __( 'Broker-stated', 'fxt-core' ),
			'unverified' => __( 'Not tested / Not verified', 'fxt-core' ),
		);
	}

	/**
	 * Per-post note under the byline (posts, pages, broker reviews, evidence).
	 *
	 * @return array
	 */
	public static function byline() {
		return array(
			'fxt_byline_note_mode' => array(
				'type'    => 'select',
				'label'   => __( 'Content under the author line', 'fxt-core' ),
				'options' => array(
					''       => __( 'Use the default (Settings)', 'fxt-core' ),
					'custom' => __( 'Custom for this page', 'fxt-core' ),
					'hide'   => __( 'Hide on this page', 'fxt-core' ),
				),
			),
			'fxt_byline_note'      => array(
				'type'  => 'html',
				'label' => __( 'Custom content', 'fxt-core' ),
				'help'  => __( 'Shown when "Custom for this page" is selected. Allowed: links, bold, italic, line breaks and source labels.', 'fxt-core' ),
			),
		);
	}

	/**
	 * Post types that show the author line in their header.
	 *
	 * @return string[]
	 */
	public static function byline_types() {
		return array( 'post', 'page', Content_Types::BROKER, Content_Types::EVIDENCE );
	}

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
	 * Score categories in display order: key => label. Editable under
	 * Settings > FX Trading Today (single source for every score view).
	 *
	 * @return array<string, string>
	 */
	public static function categories() {
		$out = array();
		foreach ( Repository::categories() as $category ) {
			$out[ $category['key'] ] = $category['label'];
		}
		return $out;
	}

	/**
	 * Default score categories (used until the settings are saved).
	 *
	 * @return array[]
	 */
	public static function default_categories() {
		return array(
			array( 'key' => 'regulation', 'label' => __( 'Regulation and client protection', 'fxt-core' ), 'weight' => 30, 'description' => __( 'Entity, licence and permissions checked on official registers.', 'fxt-core' ), 'evidence' => __( 'Register lookups, client agreement', 'fxt-core' ) ),
			array( 'key' => 'availability', 'label' => __( 'Country availability', 'fxt-core' ), 'weight' => 20, 'description' => __( 'Which entity accepts residents, and whether local deposits and withdrawals work in our tests.', 'fxt-core' ), 'evidence' => __( 'Sign-up flow, deposit and withdrawal tests', 'fxt-core' ) ),
			array( 'key' => 'costs', 'label' => __( 'Trading costs and execution', 'fxt-core' ), 'weight' => 20, 'description' => __( 'Spreads, commissions and swaps measured in live test sessions.', 'fxt-core' ), 'evidence' => __( 'Cost logs from five or more sessions', 'fxt-core' ) ),
			array( 'key' => 'platforms', 'label' => __( 'Trading platforms', 'fxt-core' ), 'weight' => 15, 'description' => __( 'Platform versions, stability, order types and tools.', 'fxt-core' ), 'evidence' => __( 'Platform test notes', 'fxt-core' ) ),
			array( 'key' => 'local', 'label' => __( 'Local services and language', 'fxt-core' ), 'weight' => 10, 'description' => __( 'Local-language site, support hours and local payment options.', 'fxt-core' ), 'evidence' => __( 'Support transcripts, payment tests', 'fxt-core' ) ),
			array( 'key' => 'support', 'label' => __( 'Customer support', 'fxt-core' ), 'weight' => 5, 'description' => __( 'Scripted questions by chat and email, scored for accuracy.', 'fxt-core' ), 'evidence' => __( 'Chat and email transcripts', 'fxt-core' ) ),
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
			'fxt_name'            => array( 'type' => 'text', 'label' => __( 'Broker name', 'fxt-core' ), 'help' => __( 'Short brand name used on buttons, in tables and in headings, e.g. "Exness". Empty: the post title without "review" and anything after it.', 'fxt-core' ) ),
			'fxt_monogram'        => array( 'type' => 'text', 'label' => __( 'Monogram', 'fxt-core' ), 'help' => __( 'Two or three letters shown when no logo is set.', 'fxt-core' ) ),
			'fxt_status'          => array( 'type' => 'select', 'label' => __( 'Review status', 'fxt-core' ), 'options' => self::review_status(), 'default' => 'published' ),
			'fxt_score'           => array( 'type' => 'number', 'label' => __( 'Overall score override (0 to 5)', 'fxt-core' ), 'min' => 0, 'max' => 5, 'step' => 0.1, 'help' => __( 'Leave empty: the score is calculated from the category scores and the weights in Settings. Shown as "Pending" until every category has a score.', 'fxt-core' ) ),
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
			// Account types (v1.4.2): field definitions, rows and display options,
			// edited in their own boxes (see Accounts).
			'fxt_account_fields'  => array( 'type' => 'account_fields', 'label' => __( 'Account type fields', 'fxt-core' ) ),
			'fxt_accounts'        => array( 'type' => 'account_rows', 'label' => __( 'Account types', 'fxt-core' ) ),
			'fxt_account_display' => array( 'type' => 'account_display', 'label' => __( 'Account types display', 'fxt-core' ) ),
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
					'source'       => array( 'type' => 'select', 'label' => __( 'Source of entity and licence data', 'fxt-core' ), 'options' => self::sources(), 'help' => __( 'Use "Verified at source" only after checking the official register.', 'fxt-core' ) ),
					'source_ref'   => array( 'type' => 'text', 'label' => __( 'Register link or reference', 'fxt-core' ) ),
					'checked'      => array( 'type' => 'date', 'label' => __( 'Checked on', 'fxt-core' ) ),
				),
			),
			// Single source of truth for where the broker accepts residents.
			'fxt_accepting'       => array(
				'type'   => 'group',
				'label'  => __( 'Accepting residents', 'fxt-core' ),
				'help'   => __( 'Tick every country whose residents this broker accepts. Countries left unticked are shown as "Not accepting residents" (only once at least one country is ticked; until then eligibility shows as pending). The source below applies to this whole list and is shown in Quick Facts.', 'fxt-core' ),
				'fields' => array(
					'countries' => array( 'type' => 'country_checklist', 'label' => __( 'Countries where residents are accepted', 'fxt-core' ) ),
					'source'    => array( 'type' => 'select', 'label' => __( 'Availability source', 'fxt-core' ), 'options' => self::sources(), 'help' => __( 'Tested is shown only for countries with published test evidence; elsewhere it shows "Needs review".', 'fxt-core' ) ),
					'ref'       => array( 'type' => 'text', 'label' => __( 'Reference (URL or note)', 'fxt-core' ) ),
					'checked'   => array( 'type' => 'date', 'label' => __( 'Checked on', 'fxt-core' ) ),
				),
			),
			'fxt_markets'         => array(
				'type'   => 'market_map',
				'label'  => __( 'Tested residents', 'fxt-core' ),
				'help'   => __( 'Only countries where this broker was tested or researched in detail: legal entity, local payments, test status and optional texts. Independent from Accepting residents: an accepted country is not tested until it is added here.', 'fxt-core' ),
				'fields' => array(
					'entity_key'     => array( 'type' => 'text', 'label' => __( 'Entity key', 'fxt-core' ) ),
					'local_payments' => array( 'type' => 'text', 'label' => __( 'Local payment methods', 'fxt-core' ) ),
					'deposit'        => array( 'type' => 'select', 'label' => __( 'Deposit tests', 'fxt-core' ), 'options' => self::test_status() ),
					'withdrawal'     => array( 'type' => 'select', 'label' => __( 'Withdrawal tests', 'fxt-core' ), 'options' => self::test_status() ),
					'platform'       => array( 'type' => 'select', 'label' => __( 'Platform tests', 'fxt-core' ), 'options' => self::test_status() ),
					'support'        => array( 'type' => 'select', 'label' => __( 'Support tests', 'fxt-core' ), 'options' => self::test_status() ),
					'last_tested'    => array( 'type' => 'date', 'label' => __( 'Last tested', 'fxt-core' ) ),
					'context'        => array( 'type' => 'textarea', 'label' => __( 'Country context message (optional)', 'fxt-core' ), 'help' => __( 'Replaces the default country message on this review for this country. Placeholders: {broker}, {country}, {entity}, {regulator}, {jurisdiction}.', 'fxt-core' ) ),
					'verdict'        => array( 'type' => 'textarea', 'label' => __( 'Trader verdict for this country (optional)', 'fxt-core' ), 'help' => __( 'Replaces the Trader verdict for visitors from this country. Empty: the review verdict is used, or the default "not accepting residents" verdict (Settings) when this broker does not accept residents.', 'fxt-core' ) ),
				),
			),
			'fxt_affiliate_url'   => array( 'type' => 'url', 'label' => __( 'Affiliate URL', 'fxt-core' ), 'help' => __( 'Rendered with rel="sponsored nofollow" and an "affiliate link" label. The button is hidden while this is empty.', 'fxt-core' ) ),
			'fxt_cta_label'       => array( 'type' => 'text', 'label' => __( 'Affiliate button label', 'fxt-core' ), 'help' => __( 'Default: "Visit {broker}". {broker} is replaced with the Broker name (never the post title). The link keeps rel="sponsored nofollow"; the affiliate disclosure is shown elsewhere on the page.', 'fxt-core' ) ),
			'fxt_sources'         => array(
				'type'   => 'sources',
				'label'  => __( 'Data sources', 'fxt-core' ),
				'help'   => __( 'How each fact on the review page was obtained. Tested: our own documented test (needs published test evidence for this broker). Verified at source: checked against an official or primary source. Broker-stated: published by the broker, not checked. Rows left "Not classified" show a neutral "Source not classified" label.', 'fxt-core' ),
				'rows'   => self::source_rows(),
				'fields' => self::source_fields(),
			),
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
				'help'   => __( 'One row per test. Account numbers, names, bank details and references are masked automatically when saved. Steps: one per line as "YYYY-MM-DD HH:MM | description".', 'fxt-core' ),
				'fields' => self::test_fields( false ),
			),
			'fxt_withdrawals'   => array(
				'type'   => 'repeater',
				'label'  => __( 'Withdrawal tests', 'fxt-core' ),
				'help'   => __( 'One row per test. Sensitive values are masked automatically when saved.', 'fxt-core' ),
				'fields' => self::test_fields( true ),
			),
		);
	}

	/**
	 * One deposit or withdrawal test: everything the table, the evidence
	 * viewer and the timeline need, in a single record.
	 *
	 * @param bool $withdrawal Add request/receipt timestamps.
	 * @return array
	 */
	public static function test_fields( $withdrawal ) {
		$fields = array(
			'id'       => array( 'type' => 'text', 'label' => __( 'Test ID', 'fxt-core' ), 'help' => __( 'e.g. DEP-VN-001. Timeline steps can link to it.', 'fxt-core' ) ),
			'date'     => array( 'type' => 'date', 'label' => __( 'Test date', 'fxt-core' ) ),
			'method'   => array( 'type' => 'text', 'label' => __( 'Payment method', 'fxt-core' ) ),
			'currency' => array( 'type' => 'text', 'label' => __( 'Currency', 'fxt-core' ) ),
			'amount'   => array( 'type' => 'text', 'label' => __( 'Amount', 'fxt-core' ) ),
		);
		if ( $withdrawal ) {
			$fields['requested'] = array( 'type' => 'datetime', 'label' => __( 'Requested', 'fxt-core' ) );
			$fields['received']  = array( 'type' => 'datetime', 'label' => __( 'Received', 'fxt-core' ) );
		}
		return $fields + array(
			'time'          => array( 'type' => 'text', 'label' => __( 'Processing time', 'fxt-core' ) ),
			'fee'           => array( 'type' => 'text', 'label' => __( 'Fee', 'fxt-core' ) ),
			'result'        => array( 'type' => 'text', 'label' => __( 'Result', 'fxt-core' ), 'help' => __( 'e.g. Successful, Rejected, Partial', 'fxt-core' ) ),
			'status'        => array( 'type' => 'select', 'label' => __( 'Test status', 'fxt-core' ), 'options' => array( 'completed' => __( 'Completed', 'fxt-core' ), 'in-progress' => __( 'In progress', 'fxt-core' ), 'failed' => __( 'Failed', 'fxt-core' ) ), 'default' => 'completed' ),
			'steps'         => array( 'type' => 'textarea', 'label' => __( 'Transaction steps', 'fxt-core' ) ),
			'broker_status' => array( 'type' => 'text', 'label' => __( 'Broker status', 'fxt-core' ) ),
			'bank_status'   => array( 'type' => 'text', 'label' => __( 'Bank / payment status', 'fxt-core' ) ),
			'account'       => array( 'type' => 'text', 'label' => __( 'Trading account', 'fxt-core' ), 'mask' => 'digits' ),
			'holder'        => array( 'type' => 'text', 'label' => __( 'Account holder', 'fxt-core' ), 'mask' => 'name' ),
			'bank'          => array( 'type' => 'text', 'label' => __( 'Bank account / card', 'fxt-core' ), 'mask' => 'digits' ),
			'reference'     => array( 'type' => 'text', 'label' => __( 'Transaction reference', 'fxt-core' ), 'mask' => 'digits' ),
			'screenshot'    => array( 'type' => 'media', 'label' => __( 'Screenshot (redacted)', 'fxt-core' ), 'help' => __( 'Upload only images with personal data already blurred.', 'fxt-core' ) ),
			'verified_by'   => array( 'type' => 'text', 'label' => __( 'Verified by', 'fxt-core' ) ),
			'verified_on'   => array( 'type' => 'date', 'label' => __( 'Verified on', 'fxt-core' ) ),
			'note'          => array( 'type' => 'textarea', 'label' => __( 'Researcher note', 'fxt-core' ) ),
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
			'fxt_ctx_accepting' => array( 'type' => 'textarea', 'label' => __( 'Country context: broker accepts residents (optional)', 'fxt-core' ), 'help' => __( 'Replaces the default message on every review for this country. Placeholders: {broker}, {country}, {entity}, {regulator}, {jurisdiction}.', 'fxt-core' ) ),
			'fxt_ctx_restricted' => array( 'type' => 'textarea', 'label' => __( 'Country context: broker does not accept residents (optional)', 'fxt-core' ) ),
			'fxt_ctx_unknown'   => array( 'type' => 'textarea', 'label' => __( 'Country context: eligibility not confirmed (optional)', 'fxt-core' ) ),
			'fxt_status'   => array( 'type' => 'select', 'label' => __( 'Research status', 'fxt-core' ), 'options' => array( '' => __( 'Not researched', 'fxt-core' ), 'published' => __( 'Published', 'fxt-core' ), 'in-progress' => __( 'In progress', 'fxt-core' ), 'planned' => __( 'Planned', 'fxt-core' ) ), 'help' => __( 'Researched countries appear in country shortcuts and in "N of M countries researched".', 'fxt-core' ) ),
		);
	}

	/**
	 * Platform term fields.
	 *
	 * @return array
	 */
	public static function platform() {
		return array(
			'fxt_full_name' => array( 'type' => 'text', 'label' => __( 'Full name', 'fxt-core' ), 'help' => __( 'Shown on review platform tiles, e.g. "MetaTrader 4". The term name is the short label used in tables.', 'fxt-core' ) ),
			'fxt_compact'   => array( 'type' => 'checkbox', 'label' => __( 'Show in compact lists (broker cards)', 'fxt-core' ) ),
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
			'fxt_social'     => array(
				'type'   => 'repeater',
				'label'  => __( 'Profile links', 'fxt-core' ),
				'help'   => __( 'Public profiles (LinkedIn, X, YouTube…). Shown on the author page and in structured data.', 'fxt-core' ),
				'fields' => array(
					'label' => array( 'type' => 'text', 'label' => __( 'Label', 'fxt-core' ) ),
					'url'   => array( 'type' => 'url', 'label' => __( 'URL', 'fxt-core' ) ),
				),
			),
			'fxt_simulated'  => array( 'type' => 'checkbox', 'label' => __( 'Show "Simulated profile" label', 'fxt-core' ) ),
		);
	}

	/**
	 * Site-wide settings (one option: fxt_settings).
	 *
	 * @return array
	 */
	public static function settings() {
		return array(
			'banner_enabled'       => array( 'type' => 'checkbox', 'label' => __( 'Show prototype banner', 'fxt-core' ) ),
			'banner_text'          => array( 'type' => 'textarea', 'label' => __( 'Banner text', 'fxt-core' ) ),
			'byline_note'          => array(
				'type'   => 'group',
				'label'  => __( 'Content under the author line (default)', 'fxt-core' ),
				'help'   => __( 'Shown right under the author line at the top of the selected content types. Each page can use this default, its own content or hide it (sidebar box "Under the author line"). Empty: nothing is shown. Allowed: links, bold, italic, line breaks and source labels.', 'fxt-core' ),
				'fields' => array(
					'text'     => array( 'type' => 'html', 'label' => __( 'Default content', 'fxt-core' ) ),
					'reviews'  => array( 'type' => 'checkbox', 'label' => __( 'Show on broker reviews', 'fxt-core' ) ),
					'evidence' => array( 'type' => 'checkbox', 'label' => __( 'Show on test evidence', 'fxt-core' ) ),
					'posts'    => array( 'type' => 'checkbox', 'label' => __( 'Show on articles', 'fxt-core' ) ),
					'pages'    => array( 'type' => 'checkbox', 'label' => __( 'Show on pages with an author line', 'fxt-core' ) ),
				),
			),
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
			'score_categories'     => array(
				'type'   => 'repeater',
				'label'  => __( 'Score categories', 'fxt-core' ),
				'help'   => __( 'Order here is the display order everywhere. Weights must add up to 100. The key links broker category scores to a row: change a label freely, but keep the key.', 'fxt-core' ),
				'fields' => array(
					'key'         => array( 'type' => 'text', 'label' => __( 'Key (letters, numbers, dashes)', 'fxt-core' ) ),
					'label'       => array( 'type' => 'text', 'label' => __( 'Category', 'fxt-core' ) ),
					'weight'      => array( 'type' => 'number', 'label' => __( 'Weight (%)', 'fxt-core' ), 'min' => 0, 'max' => 100, 'step' => 1 ),
					'description' => array( 'type' => 'textarea', 'label' => __( 'What we measure', 'fxt-core' ) ),
					'evidence'    => array( 'type' => 'text', 'label' => __( 'Evidence used', 'fxt-core' ) ),
				),
			),
			'deposit_bands'        => array( 'type' => 'list', 'label' => __( 'Minimum deposit filter (USD)', 'fxt-core' ), 'help' => __( 'Amounts offered in the directory filter, comma separated, e.g. 0, 50, 100, 200', 'fxt-core' ) ),
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

			// Tab "Review page": country context and eligibility texts.
			'country_context'      => array(
				'type'   => 'group',
				'tab'    => 'review',
				'label'  => __( 'Country context on broker reviews', 'fxt-core' ),
				'help'   => __( 'Shown under the review title for the visitor\'s country. Placeholders: {broker}, {country}, {entity}, {regulator}, {jurisdiction}. Order of priority: the message of the broker\'s country row (Broker data: conditions by country), then the country\'s own message (Broker Reviews > Countries), then these defaults. Empty fields use the default wording.', 'fxt-core' ),
				'fields' => array(
					'heading'             => array( 'type' => 'text', 'label' => __( 'Heading', 'fxt-core' ) ),
					'description'         => array( 'type' => 'textarea', 'label' => __( 'Description (optional, shown under the heading)', 'fxt-core' ) ),
					'change'              => array( 'type' => 'text', 'label' => __( 'Change country link', 'fxt-core' ) ),
					'accepting'           => array( 'type' => 'textarea', 'label' => __( 'Accepting residents', 'fxt-core' ) ),
					'accepting_no_entity' => array( 'type' => 'textarea', 'label' => __( 'Accepting residents, entity not researched yet', 'fxt-core' ) ),
					'restricted'          => array( 'type' => 'textarea', 'label' => __( 'Not accepting residents', 'fxt-core' ) ),
					'unknown'             => array( 'type' => 'textarea', 'label' => __( 'Unknown / unverified eligibility', 'fxt-core' ) ),
					'restricted_verdict'  => array( 'type' => 'textarea', 'label' => __( 'Trader verdict when the broker does not accept residents', 'fxt-core' ), 'help' => __( 'Used when the broker\'s country row has no verdict of its own.', 'fxt-core' ) ),
				),
			),

			// Tab "Broker Reviews filters": display text only. Internal filter keys,
			// query parameters and broker data never change with these labels.
			'directory_filters'    => array(
				'type'   => 'repeater',
				'tab'    => 'directory',
				'label'  => __( 'Filters', 'fxt-core' ),
				'help'   => __( 'One row per filter. Row order is the order on the page. "Filter" is the internal key and is used once; the other fields are display text only. Empty text fields use the default wording.', 'fxt-core' ),
				'fields' => array(
					'key'         => array( 'type' => 'select', 'label' => __( 'Filter', 'fxt-core' ), 'options' => self::directory_filter_keys() ),
					'label'       => array( 'type' => 'text', 'label' => __( 'Label', 'fxt-core' ) ),
					'placeholder' => array( 'type' => 'text', 'label' => __( 'Placeholder (first option)', 'fxt-core' ) ),
					'description' => array( 'type' => 'text', 'label' => __( 'Description under the filter', 'fxt-core' ) ),
					'hidden'      => array( 'type' => 'checkbox', 'label' => __( 'Hide this filter', 'fxt-core' ) ),
				),
			),
			'directory_options'    => array(
				'type'   => 'group',
				'tab'    => 'directory',
				'label'  => __( 'Filter option names', 'fxt-core' ),
				'help'   => __( 'Names shown inside the Availability, Research status and Minimum deposit filters. Regulators, platforms and account types use their names under Broker Reviews. {amount} is replaced by the amount.', 'fxt-core' ),
				'fields' => array(
					'availability_yes'        => array( 'type' => 'text', 'label' => __( 'Availability: available', 'fxt-core' ) ),
					'availability_restricted' => array( 'type' => 'text', 'label' => __( 'Availability: not accepting residents', 'fxt-core' ) ),
					'availability_pending'    => array( 'type' => 'text', 'label' => __( 'Availability: pending', 'fxt-core' ) ),
					'status_published'        => array( 'type' => 'text', 'label' => __( 'Research status: published', 'fxt-core' ) ),
					'status_updating'         => array( 'type' => 'text', 'label' => __( 'Research status: update in progress', 'fxt-core' ) ),
					'status_pending'          => array( 'type' => 'text', 'label' => __( 'Research status: research pending', 'fxt-core' ) ),
					'deposit_zero'            => array( 'type' => 'text', 'label' => __( 'Minimum deposit: no minimum', 'fxt-core' ) ),
					'deposit_band'            => array( 'type' => 'text', 'label' => __( 'Minimum deposit: other amounts', 'fxt-core' ) ),
				),
			),
			'directory_ui'         => array(
				'type'   => 'group',
				'tab'    => 'directory',
				'label'  => __( 'Text on the Broker Reviews page', 'fxt-core' ),
				'help'   => __( 'Placeholders: {count} = number of matching brokers, {country} = the selected country (or the "No country selected" text). Other braces are shown as typed; HTML is not allowed.', 'fxt-core' ),
				'fields' => array(
					'panel_title'         => array( 'type' => 'text', 'label' => __( 'Filter panel title', 'fxt-core' ) ),
					'clear_all'           => array( 'type' => 'text', 'label' => __( 'Clear filters link', 'fxt-core' ) ),
					'search_label'        => array( 'type' => 'text', 'label' => __( 'Search label (screen readers)', 'fxt-core' ) ),
					'search_placeholder'  => array( 'type' => 'text', 'label' => __( 'Search placeholder', 'fxt-core' ) ),
					'apply'               => array( 'type' => 'text', 'label' => __( 'Apply button (shown without JavaScript)', 'fxt-core' ) ),
					'sort_label'          => array( 'type' => 'text', 'label' => __( 'Sort label', 'fxt-core' ) ),
					'sort_score'          => array( 'type' => 'text', 'label' => __( 'Sort option: research score', 'fxt-core' ) ),
					'sort_recent'         => array( 'type' => 'text', 'label' => __( 'Sort option: recently reviewed', 'fxt-core' ) ),
					'sort_deposit'        => array( 'type' => 'text', 'label' => __( 'Sort option: minimum deposit', 'fxt-core' ) ),
					'results_heading'     => array( 'type' => 'text', 'label' => __( 'Result heading', 'fxt-core' ) ),
					'results_heading_one' => array( 'type' => 'text', 'label' => __( 'Result heading for exactly one broker', 'fxt-core' ) ),
					'no_country'          => array( 'type' => 'text', 'label' => __( '"No country selected" text for {country}', 'fxt-core' ) ),
					'search_chip'         => array( 'type' => 'text', 'label' => __( 'Active search chip prefix', 'fxt-core' ) ),
					'remove_filter'       => array( 'type' => 'text', 'label' => __( 'Remove filter (screen readers)', 'fxt-core' ) ),
					'empty_title'         => array( 'type' => 'text', 'label' => __( 'No results: title', 'fxt-core' ) ),
					'empty_text'          => array( 'type' => 'textarea', 'label' => __( 'No results: message', 'fxt-core' ) ),
					'empty_button'        => array( 'type' => 'text', 'label' => __( 'No results: button', 'fxt-core' ) ),
					'result_note'         => array( 'type' => 'textarea', 'label' => __( 'Result note (below the list)', 'fxt-core' ) ),
				),
			),
		);
	}

	/**
	 * Directory filters: internal key => admin name. Keys are query parameters
	 * and never change.
	 *
	 * @return array
	 */
	public static function directory_filter_keys() {
		return array(
			'country'      => __( 'Country', 'fxt-core' ),
			'availability' => __( 'Availability', 'fxt-core' ),
			'regulator'    => __( 'Regulation', 'fxt-core' ),
			'platform'     => __( 'Trading platform', 'fxt-core' ),
			'deposit'      => __( 'Minimum deposit', 'fxt-core' ),
			'account'      => __( 'Account type', 'fxt-core' ),
			'status'       => __( 'Research status', 'fxt-core' ),
		);
	}

	/**
	 * Default directory filter rows.
	 *
	 * @return array[]
	 */
	public static function default_directory_filters() {
		return array(
			array( 'key' => 'country', 'label' => __( 'Country', 'fxt-core' ), 'placeholder' => __( 'Select a country', 'fxt-core' ), 'description' => '', 'hidden' => false ),
			array( 'key' => 'availability', 'label' => __( 'Availability', 'fxt-core' ), 'placeholder' => __( 'Any availability', 'fxt-core' ), 'description' => __( 'Select a country to use this filter.', 'fxt-core' ), 'hidden' => false ),
			array( 'key' => 'regulator', 'label' => __( 'Regulation', 'fxt-core' ), 'placeholder' => __( 'Any regulator', 'fxt-core' ), 'description' => '', 'hidden' => false ),
			array( 'key' => 'platform', 'label' => __( 'Trading platform', 'fxt-core' ), 'placeholder' => __( 'Any platform', 'fxt-core' ), 'description' => '', 'hidden' => false ),
			array( 'key' => 'deposit', 'label' => __( 'Minimum deposit', 'fxt-core' ), 'placeholder' => __( 'Any amount', 'fxt-core' ), 'description' => '', 'hidden' => false ),
			array( 'key' => 'account', 'label' => __( 'Account type', 'fxt-core' ), 'placeholder' => __( 'Any account type', 'fxt-core' ), 'description' => '', 'hidden' => false ),
			array( 'key' => 'status', 'label' => __( 'Research status', 'fxt-core' ), 'placeholder' => __( 'Any status', 'fxt-core' ), 'description' => '', 'hidden' => false ),
		);
	}

	/**
	 * Default filter option names.
	 *
	 * @return array
	 */
	public static function default_directory_options() {
		$availability = self::availability();
		$status       = self::review_status();
		return array(
			'availability_yes'        => $availability['yes'],
			'availability_restricted' => $availability['restricted'],
			'availability_pending'    => $availability['pending'],
			'status_published'        => $status['published'],
			'status_updating'         => $status['updating'],
			'status_pending'          => $status['pending'],
			'deposit_zero'            => '$0',
			/* translators: {amount} is a placeholder replaced by an amount in USD. Keep it as is. */
			'deposit_band'            => __( 'Up to {amount}', 'fxt-core' ),
		);
	}

	/**
	 * Default country context texts. The defaults reproduce the wording of
	 * version 1.2, so nothing changes until an editor edits them.
	 *
	 * @return array
	 */
	public static function default_country_context() {
		return array(
			/* translators: Keep {country} as is. */
			'heading'             => __( 'Reviewing for {country}', 'fxt-core' ),
			'description'         => '',
			'change'              => __( 'Change country', 'fxt-core' ),
			/* translators: Keep the {placeholders} as they are. */
			'accepting'           => __( 'Clients in {country} are onboarded by {entity}, regulated by {regulator} in {jurisdiction}.', 'fxt-core' ),
			/* translators: Keep {country} as is. */
			'accepting_no_entity' => __( 'Available for {country}. Entity, payments and tests below may not apply to residents of this country.', 'fxt-core' ),
			/* translators: Keep the {placeholders} as they are. */
			'restricted'          => __( '{broker} does not accept residents of {country}. Regulation, client protection and payment details are hidden because they do not apply to you.', 'fxt-core' ),
			/* translators: Keep {country} as is. */
			'unknown'             => __( 'Availability pending for {country}. Entity, payments and tests below may not apply to residents of this country.', 'fxt-core' ),
			/* translators: Keep the {placeholders} as they are. */
			'restricted_verdict'  => __( '{broker} does not accept residents of {country}, so we do not recommend trying to open an account from there. Compare brokers that accept clients in {country} instead.', 'fxt-core' ),
		);
	}

	/**
	 * Default text of the Broker Reviews page.
	 *
	 * @return array
	 */
	public static function default_directory_ui() {
		return array(
			'panel_title'         => __( 'Filter brokers', 'fxt-core' ),
			'clear_all'           => __( 'Clear all', 'fxt-core' ),
			'search_label'        => __( 'Search brokers', 'fxt-core' ),
			'search_placeholder'  => __( 'Search brokers', 'fxt-core' ),
			'apply'               => __( 'Apply filters', 'fxt-core' ),
			'sort_label'          => __( 'Sort by', 'fxt-core' ),
			'sort_score'          => __( 'Research score', 'fxt-core' ),
			'sort_recent'         => __( 'Recently reviewed', 'fxt-core' ),
			'sort_deposit'        => __( 'Minimum deposit', 'fxt-core' ),
			/* translators: Keep {count} as is. */
			'results_heading'     => __( '{count} forex brokers reviewed', 'fxt-core' ),
			/* translators: Keep {count} as is. */
			'results_heading_one' => __( '{count} forex broker reviewed', 'fxt-core' ),
			'no_country'          => __( 'all countries', 'fxt-core' ),
			'search_chip'         => __( 'Search', 'fxt-core' ),
			'remove_filter'       => __( 'Remove filter', 'fxt-core' ),
			'empty_title'         => __( 'No brokers match these filters.', 'fxt-core' ),
			'empty_text'          => __( 'Try removing a filter, or choose another country. Some markets still have few verified brokers.', 'fxt-core' ),
			'empty_button'        => __( 'Clear all filters', 'fxt-core' ),
			'result_note'         => __( 'Each fact on a review is labelled by source: Tested, Verified at source or Broker-stated. Regulators are listed as the brand states them until checked against official registers.', 'fxt-core' ),
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
			'byline_note'          => array( 'text' => '', 'reviews' => true, 'evidence' => true, 'posts' => true, 'pages' => true ),
			'sample_labels'        => true,
			'default_market'       => 'VN',
			'header_cta_label'     => __( 'Find a Broker', 'fxt-core' ),
			'header_cta_url'       => '',
			'footer_tagline'       => '',
			'risk_warning'         => __( 'Forex and CFDs are complex financial products and involve a high risk of losing money. Make sure you understand the risks before trading. Nothing on this site is financial advice.', 'fxt-core' ),
			'affiliate_disclosure' => __( 'We may receive a commission when you open an account through some links on this site. This never affects ratings, rankings or research findings.', 'fxt-core' ),
			'social'               => array(),
			'score_categories'     => self::default_categories(),
			'deposit_bands'        => array( '0', '50', '100', '200' ),
			'pages'                => array( 'directory' => null, 'compare' => null, 'evidence' => null, 'methodology' => null ),
			'country_context'      => self::default_country_context(),
			'directory_filters'    => self::default_directory_filters(),
			'directory_options'    => self::default_directory_options(),
			'directory_ui'         => self::default_directory_ui(),
		);
	}
}
