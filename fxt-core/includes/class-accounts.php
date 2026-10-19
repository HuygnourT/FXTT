<?php
/**
 * Account types with customizable fields.
 *
 * Three post meta records per broker:
 *   fxt_account_fields  Field definitions in editing order:
 *                       [ { id, label, type, instrument, unit } ]
 *                       "id" is a stable internal identifier; renaming the
 *                       label never changes it, so values and calculations
 *                       keep working.
 *   fxt_accounts        One row per account type:
 *                       { name, source, source_ref, source_checked, lot_units,
 *                         <id>: value, <id>__pips, <id>__model, <id>__amount, _v }
 *   fxt_account_display Frontend table: { account_label, columns: [ { id, show } ] }
 *                       in display order (independent from the editing order).
 *
 * Brokers saved before 1.4.2 have no field definitions: they read as the
 * built-in set, whose ids are the old keys (min_deposit, spread, commission,
 * execution, best_for), so existing rows display unchanged. Numbers for the
 * cost calculator are read from the old texts ("From 1.0 pips",
 * "$7 per lot round turn", "None") until the broker is saved.
 *
 * @package FXT\Core
 */

namespace FXT\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Account types: field definitions, rows, display options, admin UI.
 */
final class Accounts {

	/** Row keys that are not field values. */
	const SYSTEM_KEYS = array( 'name', 'source', 'source_ref', 'source_checked', 'lot_units', '_v' );

	/** Storage version written on every save (no text parsing after that). */
	const VERSION = 2;

	/**
	 * Field types.
	 *
	 * @return array type => label
	 */
	public static function types() {
		return array(
			'text'       => __( 'Text', 'fxt-core' ),
			'number'     => __( 'Number', 'fxt-core' ),
			'spread'     => __( 'Spread (pips)', 'fxt-core' ),
			'commission' => __( 'Commission', 'fxt-core' ),
		);
	}

	/**
	 * Commission models.
	 *
	 * @return array model => label
	 */
	public static function models() {
		return array(
			''      => __( 'Not set (calculator shows N/A)', 'fxt-core' ),
			'none'  => __( 'No commission', 'fxt-core' ),
			'side'  => __( 'Per lot, per side (charged on open and on close)', 'fxt-core' ),
			'round' => __( 'Per lot, round turn (open + close together)', 'fxt-core' ),
		);
	}

	/**
	 * Built-in fields (the 1.4.1 columns, same ids).
	 *
	 * @return array[]
	 */
	public static function default_fields() {
		return array(
			array( 'id' => 'min_deposit', 'label' => __( 'Min deposit', 'fxt-core' ), 'type' => 'text', 'instrument' => '', 'unit' => '' ),
			array( 'id' => 'spread', 'label' => __( 'Spread from', 'fxt-core' ), 'type' => 'spread', 'instrument' => 'eurusd', 'unit' => '' ),
			array( 'id' => 'commission', 'label' => __( 'Commission', 'fxt-core' ), 'type' => 'commission', 'instrument' => '', 'unit' => '' ),
			array( 'id' => 'execution', 'label' => __( 'Execution', 'fxt-core' ), 'type' => 'text', 'instrument' => '', 'unit' => '' ),
			array( 'id' => 'best_for', 'label' => __( 'Most relevant for', 'fxt-core' ), 'type' => 'text', 'instrument' => '', 'unit' => '' ),
		);
	}

	/* ---------------------------------------------------------------------
	 * Read model
	 * ------------------------------------------------------------------ */

	/**
	 * Field definitions of a broker (built-in set until saved).
	 *
	 * @param int $post_id Broker post ID.
	 * @return array[]
	 */
	public static function fields( $post_id ) {
		if ( ! metadata_exists( 'post', $post_id, 'fxt_account_fields' ) ) {
			return self::default_fields();
		}
		$saved = self::sanitize_fields( get_post_meta( $post_id, 'fxt_account_fields', true ) );
		return null === $saved ? array() : $saved;
	}

	/**
	 * Display options of a broker, reconciled with its fields: unknown ids
	 * are dropped, fields missing from the list are appended (shown).
	 *
	 * @param int        $post_id Broker post ID.
	 * @param array|null $fields  Field definitions (default: the broker's).
	 * @return array { account_label: string, columns: [ { id, show } ] }
	 */
	public static function display( $post_id, $fields = null ) {
		$fields = null === $fields ? self::fields( $post_id ) : $fields;
		$saved  = metadata_exists( 'post', $post_id, 'fxt_account_display' ) ? get_post_meta( $post_id, 'fxt_account_display', true ) : array();
		return self::reconcile_display( is_array( $saved ) ? $saved : array(), $fields );
	}

	/**
	 * Reconcile a display record with a set of fields.
	 *
	 * @param array $display Saved display options.
	 * @param array $fields  Field definitions.
	 * @return array
	 */
	public static function reconcile_display( array $display, array $fields ) {
		$known = array();
		foreach ( $fields as $field ) {
			$known[ $field['id'] ] = true;
		}
		$columns = array();
		foreach ( isset( $display['columns'] ) && is_array( $display['columns'] ) ? $display['columns'] : array() as $column ) {
			$id = is_array( $column ) && isset( $column['id'] ) ? (string) $column['id'] : '';
			if ( isset( $known[ $id ] ) ) {
				$columns[ $id ] = array( 'id' => $id, 'show' => ! empty( $column['show'] ) );
			}
		}
		foreach ( $fields as $field ) {
			if ( ! isset( $columns[ $field['id'] ] ) ) {
				$columns[ $field['id'] ] = array( 'id' => $field['id'], 'show' => true );
			}
		}
		$label = isset( $display['account_label'] ) ? trim( (string) $display['account_label'] ) : '';
		return array(
			'account_label' => '' !== $label ? $label : __( 'Account', 'fxt-core' ),
			'columns'       => array_values( $columns ),
		);
	}

	/**
	 * Visible columns in display order.
	 *
	 * @param array $broker Normalized broker.
	 * @return array[] Field definitions.
	 */
	public static function columns( array $broker ) {
		$by_id = array();
		foreach ( $broker['account_fields'] as $field ) {
			$by_id[ $field['id'] ] = $field;
		}
		$out = array();
		foreach ( $broker['account_display']['columns'] as $column ) {
			if ( $column['show'] && isset( $by_id[ $column['id'] ] ) ) {
				$out[] = $by_id[ $column['id'] ];
			}
		}
		return $out;
	}

	/**
	 * Account rows with calculator values filled from text for rows saved
	 * before 1.4.2.
	 *
	 * @param int        $post_id Broker post ID.
	 * @param array|null $fields  Field definitions.
	 * @return array[]
	 */
	public static function rows( $post_id, $fields = null ) {
		$fields = null === $fields ? self::fields( $post_id ) : $fields;
		$rows   = get_post_meta( $post_id, 'fxt_accounts', true );
		$out    = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$row = wp_parse_args( $row, array( 'name' => '', 'source' => '', 'source_ref' => '', 'source_checked' => '', 'lot_units' => null ) );
			if ( empty( $row['_v'] ) ) {
				$row = self::fill_from_text( $row, $fields );
			}
			$out[] = $row;
		}
		return $out;
	}

	/**
	 * Fill calculator values from display texts (legacy rows, import).
	 *
	 * @param array $row    Row.
	 * @param array $fields Field definitions.
	 * @return array
	 */
	public static function fill_from_text( array $row, array $fields ) {
		foreach ( $fields as $field ) {
			$id   = $field['id'];
			$text = isset( $row[ $id ] ) && is_scalar( $row[ $id ] ) ? (string) $row[ $id ] : '';
			if ( 'spread' === $field['type'] && ! isset( $row[ $id . '__pips' ] ) ) {
				$row[ $id . '__pips' ] = self::parse_spread( $text );
			}
			if ( 'commission' === $field['type'] && ! isset( $row[ $id . '__model' ] ) ) {
				$parsed                  = self::parse_commission( $text );
				$row[ $id . '__model' ]  = $parsed['model'];
				$row[ $id . '__amount' ] = $parsed['amount'];
			}
		}
		return $row;
	}

	/**
	 * Pips from a spread text: "From 1.0 pips" -> 1.0. Anything else
	 * ("Interbank + commission", "TBD") -> null (not guessed).
	 *
	 * @param string $text Spread text.
	 * @return float|null
	 */
	public static function parse_spread( $text ) {
		if ( preg_match( '/(\d+(?:[.,]\d+)?)\s*pips?\b/i', (string) $text, $m ) ) {
			return (float) str_replace( ',', '.', $m[1] );
		}
		return null;
	}

	/**
	 * Commission model and amount (USD) from a text:
	 *   "None" / "No commission" / "$0"        -> none, 0
	 *   "$7 per lot round turn" / "$7 RT"       -> round, 7
	 *   "$3.50 per lot per side" / "per side"   -> side, 3.5
	 * Anything else ("Tiered", "Reduced", "TBD", other currencies) is left
	 * unset, so the calculator shows N/A instead of guessing.
	 *
	 * @param string $text Commission text.
	 * @return array { model: string, amount: float|null }
	 */
	public static function parse_commission( $text ) {
		$text = strtolower( trim( (string) $text ) );
		if ( preg_match( '/^(none|no commission|zero|commission[- ]free|\$?\s*0(?:\.0+)?)$/', $text ) ) {
			return array( 'model' => 'none', 'amount' => 0.0 );
		}
		if ( preg_match( '/^(?:us)?\$\s*(\d+(?:\.\d+)?)\b(.*)$/', $text, $m ) || preg_match( '/^(\d+(?:\.\d+)?)\s*(?:usd)\b(.*)$/', $text, $m ) ) {
			$rest = $m[2];
			if ( preg_match( '/round[\s-]*turn|\brt\b|round[\s-]*trip/', $rest ) ) {
				return array( 'model' => 'round', 'amount' => (float) $m[1] );
			}
			if ( preg_match( '/per\s+side|each\s+side|\/\s*side|per\s+trade\s+side/', $rest ) ) {
				return array( 'model' => 'side', 'amount' => (float) $m[1] );
			}
		}
		return array( 'model' => '', 'amount' => null );
	}

	/**
	 * Display text of one value.
	 *
	 * @param array $field Field definition.
	 * @param array $row   Account row.
	 * @return string Plain text ('' = no value).
	 */
	public static function value_text( array $field, array $row ) {
		$id   = $field['id'];
		$text = isset( $row[ $id ] ) && is_scalar( $row[ $id ] ) ? trim( (string) $row[ $id ] ) : '';
		switch ( $field['type'] ) {
			case 'number':
				if ( '' === $text || ! is_numeric( $text ) ) {
					return $text;
				}
				$num = (float) $text;
				$out = number_format_i18n( $num, floor( $num ) === $num ? 0 : 2 );
				return '' !== $field['unit'] ? $out . ' ' . $field['unit'] : $out;

			case 'spread':
				if ( '' !== $text ) {
					return $text;
				}
				$pips = isset( $row[ $id . '__pips' ] ) ? $row[ $id . '__pips' ] : null;
				/* translators: %s: spread in pips */
				return null === $pips || '' === $pips ? '' : sprintf( __( '%s pips', 'fxt-core' ), number_format_i18n( (float) $pips, 1 ) );

			case 'commission':
				if ( '' !== $text ) {
					return $text;
				}
				$model  = isset( $row[ $id . '__model' ] ) ? (string) $row[ $id . '__model' ] : '';
				$amount = isset( $row[ $id . '__amount' ] ) ? $row[ $id . '__amount' ] : null;
				if ( 'none' === $model ) {
					return __( 'None', 'fxt-core' );
				}
				if ( null === $amount || '' === $amount ) {
					return '';
				}
				$money = '$' . number_format_i18n( (float) $amount, 2 );
				/* translators: %s: amount */
				return 'round' === $model ? sprintf( __( '%s per lot round turn', 'fxt-core' ), $money ) : ( 'side' === $model ? sprintf( __( '%s per lot per side', 'fxt-core' ), $money ) : '' );
		}
		return $text;
	}

	/* ---------------------------------------------------------------------
	 * Sanitization (also used by register_meta and the demo importer)
	 * ------------------------------------------------------------------ */

	/**
	 * Whether an id may be used for a field.
	 *
	 * @param string $id Candidate id.
	 * @return bool
	 */
	public static function valid_id( $id ) {
		return (bool) preg_match( '/^[a-z][a-z0-9_]{1,40}$/', (string) $id ) && false === strpos( (string) $id, '__' ) && ! in_array( $id, self::SYSTEM_KEYS, true );
	}

	/**
	 * Field definitions.
	 *
	 * @param mixed $raw Raw list (or map id => def).
	 * @return array[]|null
	 */
	public static function sanitize_fields( $raw ) {
		if ( ! is_array( $raw ) ) {
			return null;
		}
		$types = self::types();
		$out   = array();
		foreach ( $raw as $key => $def ) {
			if ( ! is_array( $def ) ) {
				continue;
			}
			$id = isset( $def['id'] ) ? sanitize_key( (string) $def['id'] ) : sanitize_key( (string) $key );
			if ( ! self::valid_id( $id ) || isset( $out[ $id ] ) ) {
				continue;
			}
			$type  = isset( $def['type'] ) && isset( $types[ $def['type'] ] ) ? $def['type'] : 'text';
			$label = isset( $def['label'] ) ? sanitize_text_field( (string) $def['label'] ) : '';
			$out[ $id ] = array(
				'id'         => $id,
				'label'      => '' !== $label ? $label : $id,
				'type'       => $type,
				'instrument' => in_array( $type, array( 'spread', 'commission' ), true ) && isset( $def['instrument'] ) ? sanitize_key( (string) $def['instrument'] ) : '',
				'unit'       => 'number' === $type && isset( $def['unit'] ) ? sanitize_text_field( (string) $def['unit'] ) : '',
			);
		}
		// An empty list is a valid choice (all fields removed).
		return array_values( $out );
	}

	/**
	 * Account rows. With field definitions only their values are kept;
	 * without (register_meta, importer) every well-formed key is kept.
	 *
	 * @param mixed      $raw    Raw rows.
	 * @param array|null $fields Field definitions.
	 * @return array[]|null
	 */
	public static function sanitize_rows( $raw, $fields = null ) {
		if ( ! is_array( $raw ) ) {
			return null;
		}
		$known = null;
		if ( is_array( $fields ) ) {
			$known = array();
			foreach ( $fields as $field ) {
				$known[ $field['id'] ] = $field;
			}
		}
		$sources = Schema::sources();
		$models  = self::models();
		$out     = array();
		foreach ( $raw as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$clean = array(
				'name'           => isset( $row['name'] ) ? sanitize_text_field( (string) $row['name'] ) : '',
				'source'         => isset( $row['source'] ) && isset( $sources[ (string) $row['source'] ] ) ? (string) $row['source'] : '',
				'source_ref'     => isset( $row['source_ref'] ) ? sanitize_text_field( (string) $row['source_ref'] ) : '',
				'source_checked' => isset( $row['source_checked'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $row['source_checked'] ) ? (string) $row['source_checked'] : '',
				'lot_units'      => self::number( isset( $row['lot_units'] ) ? $row['lot_units'] : null, 0 ),
			);
			$has = '' !== $clean['name'];
			foreach ( $row as $key => $value ) {
				$key = (string) $key;
				if ( in_array( $key, self::SYSTEM_KEYS, true ) ) {
					continue;
				}
				$parts = explode( '__', $key, 2 );
				$base  = $parts[0];
				$part  = isset( $parts[1] ) ? $parts[1] : '';
				if ( ! self::valid_id( $base ) || ( null !== $known && ! isset( $known[ $base ] ) ) ) {
					continue;
				}
				if ( '' === $part ) {
					$value       = is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
					$clean[ $key ] = $value;
					$has         = $has || '' !== $value;
				} elseif ( 'pips' === $part || 'amount' === $part ) {
					$clean[ $key ] = self::number( $value, 0 );
				} elseif ( 'model' === $part ) {
					$clean[ $key ] = is_scalar( $value ) && isset( $models[ (string) $value ] ) ? (string) $value : '';
				}
			}
			if ( ! $has ) {
				continue;
			}
			if ( null !== $known ) {
				// Keep only the parts each field type uses.
				foreach ( array_keys( $clean ) as $key ) {
					$parts = explode( '__', $key, 2 );
					if ( isset( $parts[1] ) && isset( $known[ $parts[0] ] ) ) {
						$type = $known[ $parts[0] ]['type'];
						$ok   = ( 'spread' === $type && 'pips' === $parts[1] ) || ( 'commission' === $type && in_array( $parts[1], array( 'model', 'amount' ), true ) );
						if ( ! $ok ) {
							unset( $clean[ $key ] );
						}
					}
				}
			}
			if ( empty( $row['_v'] ) && null === $known ) {
				// Imported or legacy text: derive calculator values once.
				$clean = self::fill_from_text( $clean, self::guess_fields( $clean ) );
			}
			$clean['_v'] = self::VERSION;
			$out[]       = $clean;
		}
		return $out ? $out : null;
	}

	/**
	 * Field definitions implied by the keys of a legacy/imported row: the
	 * built-in spread and commission fields.
	 *
	 * @param array $row Row.
	 * @return array[]
	 */
	private static function guess_fields( array $row ) {
		$out = array();
		foreach ( self::default_fields() as $field ) {
			if ( in_array( $field['type'], array( 'spread', 'commission' ), true ) && isset( $row[ $field['id'] ] ) ) {
				$out[] = $field;
			}
		}
		return $out;
	}

	/**
	 * Display options.
	 *
	 * @param mixed      $raw    Raw { account_label, columns:[{id,show}] } or the
	 *                           admin form { account_label, order:[], show:[] }.
	 * @param array|null $fields Field definitions.
	 * @return array|null
	 */
	public static function sanitize_display( $raw, $fields = null ) {
		if ( ! is_array( $raw ) ) {
			return null;
		}
		$columns = array();
		if ( isset( $raw['order'] ) && is_array( $raw['order'] ) ) {
			$shown = array_flip( array_map( 'strval', isset( $raw['show'] ) && is_array( $raw['show'] ) ? $raw['show'] : array() ) );
			foreach ( $raw['order'] as $id ) {
				$id = sanitize_key( (string) $id );
				if ( self::valid_id( $id ) ) {
					$columns[ $id ] = array( 'id' => $id, 'show' => isset( $shown[ $id ] ) );
				}
			}
		} elseif ( isset( $raw['columns'] ) && is_array( $raw['columns'] ) ) {
			foreach ( $raw['columns'] as $column ) {
				$id = is_array( $column ) && isset( $column['id'] ) ? sanitize_key( (string) $column['id'] ) : '';
				if ( self::valid_id( $id ) ) {
					$columns[ $id ] = array( 'id' => $id, 'show' => ! empty( $column['show'] ) );
				}
			}
		}
		$out = array(
			'account_label' => isset( $raw['account_label'] ) ? sanitize_text_field( (string) $raw['account_label'] ) : '',
			'columns'       => array_values( $columns ),
		);
		return is_array( $fields ) ? self::reconcile_display( $out, $fields ) : $out;
	}

	/**
	 * Non-negative number or null.
	 *
	 * @param mixed $value Raw.
	 * @param float $min   Minimum.
	 * @return float|null
	 */
	private static function number( $value, $min ) {
		if ( ! is_scalar( $value ) || '' === trim( (string) $value ) ) {
			return null;
		}
		$value = str_replace( ',', '.', trim( (string) $value ) );
		return is_numeric( $value ) ? max( (float) $min, round( (float) $value, 6 ) ) : null;
	}

	/* ---------------------------------------------------------------------
	 * Admin
	 * ------------------------------------------------------------------ */

	/**
	 * Save both account boxes from the broker form.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $raw     Unslashed $_POST['fxt_acc'].
	 */
	public static function save( $post_id, array $raw ) {
		if ( empty( $raw['present'] ) ) {
			return;
		}
		$fields = self::sanitize_fields( isset( $raw['fields'] ) ? $raw['fields'] : array() );
		$fields = null === $fields ? array() : $fields;
		update_post_meta( $post_id, 'fxt_account_fields', wp_slash( $fields ) );

		$rows = self::sanitize_rows( isset( $raw['rows'] ) ? array_values( (array) $raw['rows'] ) : array(), $fields );
		if ( $rows ) {
			update_post_meta( $post_id, 'fxt_accounts', wp_slash( $rows ) );
		} else {
			delete_post_meta( $post_id, 'fxt_accounts' );
		}

		$display = self::sanitize_display( isset( $raw['display'] ) ? $raw['display'] : array(), $fields );
		update_post_meta( $post_id, 'fxt_account_display', wp_slash( $display ) );
	}

	/**
	 * Instruments for the spread / commission field selects.
	 *
	 * @return array key => name
	 */
	private static function instrument_options() {
		$out = array();
		foreach ( Costs::instruments() as $instrument ) {
			$out[ $instrument['key'] ] = $instrument['name'];
		}
		return $out;
	}

	/**
	 * Box "Broker data: account types": fields (add, remove, rename,
	 * reorder) and the account rows.
	 *
	 * @param \WP_Post $post Broker post.
	 */
	public static function render_fields_box( $post ) {
		$fields = self::fields( $post->ID );
		$rows   = self::rows( $post->ID, $fields );
		$types  = self::types();

		echo '<div class="fxt-fields fxt-acc" data-fxt-acc>';
		echo '<input type="hidden" name="fxt_acc[present]" value="1">';

		echo '<div class="fxt-field"><p class="fxt-field__label">' . esc_html__( 'Fields', 'fxt-core' ) . '</p>';
		echo '<p class="description">' . esc_html__( 'Rename a label at any time: values, display options and the cost calculator use the internal ID, which never changes. Removing a field deletes its values when the broker is saved. "Spread (pips)" and "Commission" fields feed the Trading cost calculator; choose the instrument their values refer to.', 'fxt-core' ) . '</p>';
		echo '<table class="widefat fxt-acc-defs"><thead><tr>';
		foreach ( array( __( 'Label', 'fxt-core' ), __( 'Type', 'fxt-core' ), __( 'Instrument / unit', 'fxt-core' ), __( 'Internal ID', 'fxt-core' ), '' ) as $head ) {
			echo '<th scope="col">' . esc_html( $head ) . '</th>';
		}
		echo '</tr></thead><tbody data-fxt-acc-defs>';
		foreach ( $fields as $field ) {
			self::render_def_row( $field, $types );
		}
		echo '</tbody></table>';
		echo '<template data-fxt-acc-def-template>';
		self::render_def_row( array( 'id' => '__FIELD__', 'label' => '__LABEL__', 'type' => '__TYPE__', 'instrument' => '', 'unit' => '' ), $types );
		echo '</template>';

		echo '<p class="fxt-acc-add">';
		echo '<label class="screen-reader-text" for="fxt-acc-newlabel">' . esc_html__( 'New field label', 'fxt-core' ) . '</label>';
		echo '<input type="text" id="fxt-acc-newlabel" class="regular-text" placeholder="' . esc_attr__( 'New field label, e.g. Swap-free', 'fxt-core' ) . '" data-fxt-acc-newlabel> ';
		echo '<label class="screen-reader-text" for="fxt-acc-newtype">' . esc_html__( 'Type', 'fxt-core' ) . '</label>';
		echo '<select id="fxt-acc-newtype" data-fxt-acc-newtype>';
		foreach ( $types as $type => $label ) {
			echo '<option value="' . esc_attr( $type ) . '">' . esc_html( $label ) . '</option>';
		}
		echo '</select> <button type="button" class="button" data-fxt-acc-addfield>' . esc_html__( 'Add field', 'fxt-core' ) . '</button></p>';
		echo '</div>';

		// Inputs for a new field in every row (cloned by admin.js).
		foreach ( array_keys( $types ) as $type ) {
			echo '<template data-fxt-acc-input="' . esc_attr( $type ) . '">';
			self::render_value( array( 'id' => '__FIELD__', 'label' => '__LABEL__', 'type' => $type, 'instrument' => '', 'unit' => '' ), array(), '__INDEX__' );
			echo '</template>';
		}

		echo '<div class="fxt-field"><p class="fxt-field__label">' . esc_html__( 'Account types', 'fxt-core' ) . '</p>';
		echo '<div class="fxt-repeater" data-fxt-repeater>';
		echo '<div class="fxt-repeater__rows" data-fxt-rows data-fxt-acc-rows>';
		foreach ( $rows as $index => $row ) {
			self::render_row( $fields, $row, (string) $index );
		}
		echo '</div><template data-fxt-template>';
		self::render_row( $fields, array(), '__INDEX__' );
		echo '</template>';
		printf( '<button type="button" class="button" data-fxt-add>%s</button>', esc_html__( 'Add account type', 'fxt-core' ) );
		echo '</div></div>';
		echo '</div>';
	}

	/**
	 * One field definition row.
	 *
	 * @param array $field Definition.
	 * @param array $types Type labels.
	 */
	private static function render_def_row( array $field, array $types ) {
		$id   = $field['id'];
		$name = 'fxt_acc[fields][' . $id . ']';
		$type = $field['type'];
		printf( '<tr data-fxt-acc-def="%s" data-type="%s">', esc_attr( $id ), esc_attr( $type ) );
		printf(
			'<td><label class="screen-reader-text" for="fxt-acc-def-%1$s">%2$s</label><input type="text" class="widefat" id="fxt-acc-def-%1$s" name="%3$s[label]" value="%4$s" data-fxt-acc-label><input type="hidden" name="%3$s[id]" value="%1$s"><input type="hidden" name="%3$s[type]" value="%5$s"></td>',
			esc_attr( $id ),
			esc_html__( 'Field label', 'fxt-core' ),
			esc_attr( $name ),
			esc_attr( $field['label'] ),
			esc_attr( $type )
		);
		echo '<td data-fxt-acc-typelabel>' . esc_html( isset( $types[ $type ] ) ? $types[ $type ] : '' ) . '</td><td>';
		// Both controls are rendered; admin.css shows the one that fits the type.
		echo '<span class="fxt-acc-def__instrument"><label class="screen-reader-text" for="fxt-acc-ins-' . esc_attr( $id ) . '">' . esc_html__( 'Instrument', 'fxt-core' ) . '</label><select id="fxt-acc-ins-' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '[instrument]">';
		echo '<option value="">' . esc_html__( 'Any instrument (commission)', 'fxt-core' ) . '</option>';
		foreach ( self::instrument_options() as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '"' . selected( $field['instrument'], $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></span>';
		echo '<span class="fxt-acc-def__unit"><label class="screen-reader-text" for="fxt-acc-unit-' . esc_attr( $id ) . '">' . esc_html__( 'Unit', 'fxt-core' ) . '</label><input type="text" class="small-text" id="fxt-acc-unit-' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '[unit]" value="' . esc_attr( $field['unit'] ) . '" placeholder="' . esc_attr__( 'Unit', 'fxt-core' ) . '"></span>';
		echo '<span class="fxt-acc-def__none" aria-hidden="true">&ndash;</span>';
		echo '</td><td><code>' . esc_html( $id ) . '</code></td><td class="fxt-acc-def__actions">';
		printf( '<button type="button" class="button-link" data-fxt-acc-up>%s</button> ', esc_html__( 'Move up', 'fxt-core' ) );
		printf( '<button type="button" class="button-link" data-fxt-acc-down>%s</button> ', esc_html__( 'Move down', 'fxt-core' ) );
		printf( '<button type="button" class="button-link button-link-delete" data-fxt-acc-remove>%s</button>', esc_html__( 'Remove', 'fxt-core' ) );
		echo '</td></tr>';
	}

	/**
	 * One account row (name, a value per field, row source, lot size).
	 *
	 * @param array  $fields Definitions.
	 * @param array  $row    Values.
	 * @param string $index  Row index or placeholder.
	 */
	private static function render_row( array $fields, array $row, $index ) {
		$base = 'fxt_acc[rows][' . $index . ']';
		$uid  = 'fxt-acc-' . $index;
		printf( '<div class="fxt-repeater__row fxt-acc-row" data-fxt-row data-fxt-acc-row="%s"><div class="fxt-group">', esc_attr( $index ) );
		printf(
			'<div class="fxt-field fxt-field--text"><label class="fxt-field__label" for="%1$s-name">%2$s</label><input type="text" class="widefat" id="%1$s-name" name="%3$s[name]" value="%4$s"></div>',
			esc_attr( $uid ),
			esc_html__( 'Account name', 'fxt-core' ),
			esc_attr( $base ),
			esc_attr( isset( $row['name'] ) ? $row['name'] : '' )
		);
		foreach ( $fields as $field ) {
			self::render_value( $field, $row, $index );
		}
		$open = ! empty( $row['source'] ) || ! empty( $row['lot_units'] );
		echo '<details class="fxt-acc-row__more"' . ( $open ? ' open' : '' ) . ' data-fxt-acc-more><summary>' . esc_html__( 'Row source label and calculator lot size', 'fxt-core' ) . '</summary><div class="fxt-group">';
		Fields::render(
			array( 'type' => 'select', 'label' => __( 'Source of this row', 'fxt-core' ), 'options' => Schema::sources(), 'help' => __( 'Optional. Shown in this row of the table; empty rows use the table source (Broker data: sources > Account types).', 'fxt-core' ) ),
			$base . '[source]',
			isset( $row['source'] ) ? $row['source'] : '',
			$uid . '-source'
		);
		Fields::render( array( 'type' => 'text', 'label' => __( 'Reference (URL or note)', 'fxt-core' ) ), $base . '[source_ref]', isset( $row['source_ref'] ) ? $row['source_ref'] : '', $uid . '-ref' );
		Fields::render( array( 'type' => 'date', 'label' => __( 'Checked on', 'fxt-core' ) ), $base . '[source_checked]', isset( $row['source_checked'] ) ? $row['source_checked'] : '', $uid . '-checked' );
		Fields::render(
			array( 'type' => 'number', 'label' => __( 'Units per lot for this account (calculator)', 'fxt-core' ), 'min' => 0, 'step' => 'any', 'help' => __( 'Only when one lot of this account is not the instrument\'s contract size, e.g. a cent account. Empty: the instrument setting is used.', 'fxt-core' ) ),
			$base . '[lot_units]',
			isset( $row['lot_units'] ) ? $row['lot_units'] : null,
			$uid . '-lot'
		);
		echo '</div></details>';
		echo '</div><div class="fxt-repeater__actions">';
		printf( '<button type="button" class="button-link" data-fxt-up>%s</button>', esc_html__( 'Move up', 'fxt-core' ) );
		printf( '<button type="button" class="button-link" data-fxt-down>%s</button>', esc_html__( 'Move down', 'fxt-core' ) );
		printf( '<button type="button" class="button-link button-link-delete" data-fxt-remove>%s</button>', esc_html__( 'Remove', 'fxt-core' ) );
		echo '</div></div>';
	}

	/**
	 * Inputs of one field in one row.
	 *
	 * @param array  $field Definition.
	 * @param array  $row   Values.
	 * @param string $index Row index or placeholder.
	 */
	private static function render_value( array $field, array $row, $index ) {
		$id    = $field['id'];
		$base  = 'fxt_acc[rows][' . $index . ']';
		$name  = $base . '[' . $id . ']';
		$uid   = 'fxt-acc-' . $index . '-' . $id;
		$text  = isset( $row[ $id ] ) && is_scalar( $row[ $id ] ) ? (string) $row[ $id ] : '';
		$label = '<span data-fxt-acc-fieldlabel>' . esc_html( $field['label'] ) . '</span>';
		printf( '<div class="fxt-field fxt-acc-value fxt-acc-value--%s" data-fxt-acc-field="%s">', esc_attr( $field['type'] ), esc_attr( $id ) );
		switch ( $field['type'] ) {
			case 'number':
				printf( '<label class="fxt-field__label" for="%1$s">%2$s</label><input type="number" step="any" class="small-text" id="%1$s" name="%3$s" value="%4$s">', esc_attr( $uid ), $label, esc_attr( $name ), esc_attr( $text ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				break;

			case 'spread':
				$pips = isset( $row[ $id . '__pips' ] ) && null !== $row[ $id . '__pips' ] ? (string) $row[ $id . '__pips' ] : '';
				echo '<p class="fxt-field__label">' . $label . '</p><div class="fxt-acc-value__parts">'; // phpcs:ignore WordPress.Security.EscapeOutput
				printf( '<label>%1$s <input type="number" step="any" min="0" class="small-text" id="%2$s-pips" name="%3$s" value="%4$s"></label>', esc_html__( 'Pips (calculator)', 'fxt-core' ), esc_attr( $uid ), esc_attr( $base . '[' . $id . '__pips]' ), esc_attr( $pips ) );
				printf( '<label>%1$s <input type="text" class="regular-text" id="%2$s" name="%3$s" value="%4$s" placeholder="%5$s"></label>', esc_html__( 'Display text', 'fxt-core' ), esc_attr( $uid ), esc_attr( $name ), esc_attr( $text ), esc_attr__( 'e.g. From 1.0 pips (empty: the pips)', 'fxt-core' ) );
				echo '</div>';
				break;

			case 'commission':
				$model  = isset( $row[ $id . '__model' ] ) ? (string) $row[ $id . '__model' ] : '';
				$amount = isset( $row[ $id . '__amount' ] ) && null !== $row[ $id . '__amount' ] ? (string) $row[ $id . '__amount' ] : '';
				echo '<p class="fxt-field__label">' . $label . '</p><div class="fxt-acc-value__parts">'; // phpcs:ignore WordPress.Security.EscapeOutput
				printf( '<label>%1$s <select id="%2$s-model" name="%3$s">', esc_html__( 'Model', 'fxt-core' ), esc_attr( $uid ), esc_attr( $base . '[' . $id . '__model]' ) );
				foreach ( self::models() as $key => $model_label ) {
					printf( '<option value="%s"%s>%s</option>', esc_attr( $key ), selected( $model, $key, false ), esc_html( $model_label ) );
				}
				echo '</select></label>';
				printf( '<label>%1$s <input type="number" step="any" min="0" class="small-text" id="%2$s-amount" name="%3$s" value="%4$s"></label>', esc_html__( 'Amount per lot (USD)', 'fxt-core' ), esc_attr( $uid ), esc_attr( $base . '[' . $id . '__amount]' ), esc_attr( $amount ) );
				printf( '<label>%1$s <input type="text" class="regular-text" id="%2$s" name="%3$s" value="%4$s" placeholder="%5$s"></label>', esc_html__( 'Display text', 'fxt-core' ), esc_attr( $uid ), esc_attr( $name ), esc_attr( $text ), esc_attr__( 'e.g. $7 per lot round turn (empty: from the model)', 'fxt-core' ) );
				echo '</div>';
				break;

			default:
				printf( '<label class="fxt-field__label" for="%1$s">%2$s</label><input type="text" class="widefat" id="%1$s" name="%3$s" value="%4$s">', esc_attr( $uid ), $label, esc_attr( $name ), esc_attr( $text ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</div>';
	}

	/**
	 * Box "Broker data: account types display": which fields the frontend
	 * table shows, in which order, and the first column heading.
	 *
	 * @param \WP_Post $post Broker post.
	 */
	public static function render_display_box( $post ) {
		$fields  = self::fields( $post->ID );
		$display = self::display( $post->ID, $fields );
		$labels  = array();
		foreach ( $fields as $field ) {
			$labels[ $field['id'] ] = $field['label'];
		}
		echo '<div class="fxt-fields fxt-acc-display" data-fxt-acc-display>';
		printf(
			'<div class="fxt-field fxt-field--text"><label class="fxt-field__label" for="fxt-acc-account-label">%s</label><input type="text" class="regular-text" id="fxt-acc-account-label" name="fxt_acc[display][account_label]" value="%s"></div>',
			esc_html__( 'First column heading (account name)', 'fxt-core' ),
			esc_attr( $display['account_label'] )
		);
		echo '<div class="fxt-field"><p class="fxt-field__label">' . esc_html__( 'Columns in the Account types table (frontend)', 'fxt-core' ) . '</p>';
		echo '<p class="description">' . esc_html__( 'Tick the fields to show and set their order with Move up / Move down. This order is only for the frontend table; the editing order is set in the box above.', 'fxt-core' ) . '</p>';
		echo '<input type="hidden" name="fxt_acc[display][show][]" value="">';
		echo '<ol class="fxt-acc-cols" data-fxt-acc-cols>';
		foreach ( $display['columns'] as $column ) {
			self::render_column( $column['id'], isset( $labels[ $column['id'] ] ) ? $labels[ $column['id'] ] : $column['id'], $column['show'] );
		}
		echo '</ol><template data-fxt-acc-col-template>';
		self::render_column( '__FIELD__', '__LABEL__', true );
		echo '</template></div></div>';
	}

	/**
	 * One column of the display options.
	 *
	 * @param string $id    Field id.
	 * @param string $label Label.
	 * @param bool   $show  Shown.
	 */
	private static function render_column( $id, $label, $show ) {
		printf(
			'<li data-fxt-acc-col="%1$s"><label><input type="checkbox" name="fxt_acc[display][show][]" value="%1$s"%2$s> <span data-fxt-acc-collabel>%3$s</span></label><input type="hidden" name="fxt_acc[display][order][]" value="%1$s"> <span class="fxt-acc-cols__actions"><button type="button" class="button-link" data-fxt-acc-colup>%4$s</button> <button type="button" class="button-link" data-fxt-acc-coldown>%5$s</button></span></li>',
			esc_attr( $id ),
			$show ? ' checked' : '',
			esc_html( $label ),
			esc_html__( 'Move up', 'fxt-core' ),
			esc_html__( 'Move down', 'fxt-core' )
		);
	}
}
