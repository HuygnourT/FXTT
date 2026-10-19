<?php
/**
 * Trading cost calculator: settings and calculation.
 *
 * Estimated cost of one trade per account type and volume:
 *
 *   pip value per lot (USD) = units per lot x pip size x USD per 1 unit of
 *                             the quote currency
 *   spread cost             = spread (pips) x pip value per lot x lots
 *   commission              = per side:   amount x lots x 2 (round trip) or x 1 (opening)
 *                             round turn: amount x lots x 1 (round trip) or x 0.5 (opening)
 *                             none:       0
 *   total                   = spread cost + commission
 *
 * The spread is paid once per trade (crossing the bid/ask), so it is the
 * same for "opening a position" and "round trip". Units per lot come from
 * the instrument unless an account sets its own (e.g. cent accounts).
 * Values that are not known are N/A: nothing is guessed. Swaps, slippage,
 * deposit fees and currency conversion are not included.
 *
 * @package FXT\Core
 */

namespace FXT\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Cost calculator.
 */
final class Costs {

	/**
	 * Default volume presets (lots).
	 *
	 * @return array[]
	 */
	public static function default_volumes() {
		return array(
			array( 'lots' => 0.01, 'label' => '' ),
			array( 'lots' => 1, 'label' => '' ),
			array( 'lots' => 100, 'label' => '' ),
		);
	}

	/**
	 * Default instruments. Contract sizes and pip sizes are the usual
	 * conventions; brokers can differ, so every value is editable.
	 *
	 * @return array[]
	 */
	public static function default_instruments() {
		return array(
			array( 'key' => 'eurusd', 'name' => 'EUR/USD', 'units' => 100000, 'pip_size' => 0.0001, 'quote' => 'USD', 'usd_rate' => 1, 'note' => '' ),
			array( 'key' => 'gbpusd', 'name' => 'GBP/USD', 'units' => 100000, 'pip_size' => 0.0001, 'quote' => 'USD', 'usd_rate' => 1, 'note' => '' ),
			array( 'key' => 'xauusd', 'name' => 'XAU/USD (gold)', 'units' => 100, 'pip_size' => 0.01, 'quote' => 'USD', 'usd_rate' => 1, 'note' => __( '1 lot = 100 troy ounces; pip = 0.01. Some brokers quote gold spreads with another pip size: check before entering spreads for gold.', 'fxt-core' ) ),
		);
	}

	/**
	 * Default options.
	 *
	 * @return array
	 */
	public static function default_options() {
		return array(
			'instrument' => 'eurusd',
			'basis'      => 'round_trip',
			'note'       => __( 'Estimates in USD for one trade, from the account data on this page. Not included: swaps, slippage, deposit and withdrawal fees, currency conversion. N/A means the data needed for that value is not available.', 'fxt-core' ),
		);
	}

	/**
	 * Trade bases.
	 *
	 * @return array
	 */
	public static function bases() {
		return array(
			'round_trip' => __( 'Round trip (open and close)', 'fxt-core' ),
			'open'       => __( 'Opening a position only', 'fxt-core' ),
		);
	}

	/**
	 * Volume presets in admin order.
	 *
	 * @return array[] { lots: float, label: string }
	 */
	public static function volumes() {
		$rows = Repository::setting( 'calc_volumes' );
		$out  = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$lots = isset( $row['lots'] ) ? (float) $row['lots'] : 0;
			if ( $lots > 0 ) {
				$out[] = array( 'lots' => $lots, 'label' => isset( $row['label'] ) ? (string) $row['label'] : '' );
			}
		}
		return $out ? $out : self::default_volumes();
	}

	/**
	 * Instruments keyed by their stable key.
	 *
	 * @return array<string, array>
	 */
	public static function instruments() {
		$rows = Repository::setting( 'calc_instruments' );
		$out  = array();
		foreach ( is_array( $rows ) && $rows ? $rows : self::default_instruments() as $row ) {
			$key = isset( $row['key'] ) ? sanitize_key( (string) $row['key'] ) : '';
			if ( '' === $key || isset( $out[ $key ] ) ) {
				continue;
			}
			$quote       = strtoupper( isset( $row['quote'] ) ? trim( (string) $row['quote'] ) : '' );
			$rate        = isset( $row['usd_rate'] ) && '' !== $row['usd_rate'] && null !== $row['usd_rate'] ? (float) $row['usd_rate'] : ( 'USD' === $quote ? 1.0 : null );
			$out[ $key ] = array(
				'key'      => $key,
				'name'     => isset( $row['name'] ) && '' !== $row['name'] ? (string) $row['name'] : strtoupper( $key ),
				'units'    => isset( $row['units'] ) ? (float) $row['units'] : 0.0,
				'pip_size' => isset( $row['pip_size'] ) ? (float) $row['pip_size'] : 0.0,
				'quote'    => $quote,
				'usd_rate' => $rate,
				'note'     => isset( $row['note'] ) ? (string) $row['note'] : '',
			);
		}
		return $out;
	}

	/**
	 * Calculator options merged with defaults.
	 *
	 * @return array
	 */
	public static function options() {
		$saved = Repository::setting( 'calc_options' );
		$saved = is_array( $saved ) ? array_filter( $saved, static function ( $v ) {
			return null !== $v && '' !== $v;
		} ) : array();
		return array_merge( self::default_options(), $saved );
	}

	/**
	 * USD value of one pip for a number of units, or null when the
	 * instrument lacks the data (e.g. a non-USD quote without a rate).
	 *
	 * @param array      $instrument Instrument.
	 * @param float|null $units      Units per lot (null = the instrument's).
	 * @return float|null
	 */
	public static function pip_value( array $instrument, $units = null ) {
		$units = null === $units ? $instrument['units'] : (float) $units;
		if ( $units <= 0 || $instrument['pip_size'] <= 0 || null === $instrument['usd_rate'] || $instrument['usd_rate'] <= 0 ) {
			return null;
		}
		return $units * $instrument['pip_size'] * $instrument['usd_rate'];
	}

	/**
	 * The spread and commission fields a broker uses for an instrument.
	 *
	 * @param array  $fields Account field definitions.
	 * @param string $key    Instrument key.
	 * @return array { spread: array|null, commission: array|null }
	 */
	public static function fields_for( array $fields, $key ) {
		$spread     = null;
		$commission = null;
		$any        = null;
		foreach ( $fields as $field ) {
			if ( 'spread' === $field['type'] && null === $spread && $field['instrument'] === $key ) {
				$spread = $field;
			}
			if ( 'commission' === $field['type'] ) {
				if ( null === $commission && $field['instrument'] === $key ) {
					$commission = $field;
				}
				if ( null === $any && '' === $field['instrument'] ) {
					$any = $field;
				}
			}
		}
		return array( 'spread' => $spread, 'commission' => $commission ? $commission : $any );
	}

	/**
	 * Cost table of one broker.
	 *
	 * @param array  $broker         Normalized broker.
	 * @param string $instrument_key Instrument key ('' = default).
	 * @param string $basis          round_trip|open ('' = default).
	 * @return array {
	 *     instrument: array|null, basis: string, volumes: array[], fields: array,
	 *     pip_value: float|null,
	 *     rows: [ { name, lot_units, cells: [ { spread, commission, total } ] } ]
	 * }
	 */
	public static function table( array $broker, $instrument_key = '', $basis = '' ) {
		$options     = self::options();
		$instruments = self::instruments();
		$key         = '' !== $instrument_key && isset( $instruments[ $instrument_key ] ) ? $instrument_key : $options['instrument'];
		$instrument  = isset( $instruments[ $key ] ) ? $instruments[ $key ] : ( $instruments ? reset( $instruments ) : null );
		$basis       = isset( self::bases()[ $basis ] ) ? $basis : ( isset( self::bases()[ $options['basis'] ] ) ? $options['basis'] : 'round_trip' );
		$volumes     = self::volumes();
		$fields      = $instrument ? self::fields_for( $broker['account_fields'], $instrument['key'] ) : array( 'spread' => null, 'commission' => null );

		$rows = array();
		foreach ( $broker['accounts'] as $account ) {
			$units     = ! empty( $account['lot_units'] ) ? (float) $account['lot_units'] : null;
			$pip_value = $instrument ? self::pip_value( $instrument, $units ) : null;
			$pips      = null;
			if ( $fields['spread'] ) {
				$value = isset( $account[ $fields['spread']['id'] . '__pips' ] ) ? $account[ $fields['spread']['id'] . '__pips' ] : null;
				$pips  = null === $value || '' === $value ? null : (float) $value;
			}
			$model  = '';
			$amount = null;
			if ( $fields['commission'] ) {
				$id     = $fields['commission']['id'];
				$model  = isset( $account[ $id . '__model' ] ) ? (string) $account[ $id . '__model' ] : '';
				$amount = isset( $account[ $id . '__amount' ] ) && '' !== $account[ $id . '__amount' ] && null !== $account[ $id . '__amount' ] ? (float) $account[ $id . '__amount' ] : null;
			}
			$cells = array();
			foreach ( $volumes as $volume ) {
				$lots       = $volume['lots'];
				$spread     = null !== $pips && null !== $pip_value ? $pips * $pip_value * $lots : null;
				$commission = self::commission( $model, $amount, $lots, $basis );
				$cells[]    = array(
					'spread'     => $spread,
					'commission' => $commission,
					'total'      => null !== $spread && null !== $commission ? $spread + $commission : null,
				);
			}
			$rows[] = array(
				'name'      => isset( $account['name'] ) ? (string) $account['name'] : '',
				'lot_units' => $units,
				'cells'     => $cells,
			);
		}

		return array(
			'instrument' => $instrument,
			'basis'      => $basis,
			'volumes'    => $volumes,
			'fields'     => $fields,
			'pip_value'  => $instrument ? self::pip_value( $instrument ) : null,
			'rows'       => $rows,
		);
	}

	/**
	 * Commission of one trade.
	 *
	 * @param string     $model  none|side|round|''.
	 * @param float|null $amount USD per lot.
	 * @param float      $lots   Volume.
	 * @param string     $basis  round_trip|open.
	 * @return float|null
	 */
	public static function commission( $model, $amount, $lots, $basis ) {
		if ( 'none' === $model ) {
			return 0.0;
		}
		if ( null === $amount || ! in_array( $model, array( 'side', 'round' ), true ) ) {
			return null;
		}
		$round_trip = 'round_trip' === $basis;
		$factor     = 'side' === $model ? ( $round_trip ? 2 : 1 ) : ( $round_trip ? 1 : 0.5 );
		return $amount * $lots * $factor;
	}

	/**
	 * Money for display: "$1,234.50"; null -> "N/A".
	 *
	 * @param float|null $value Value.
	 * @return string
	 */
	public static function money( $value ) {
		if ( null === $value ) {
			return __( 'N/A', 'fxt-core' );
		}
		if ( $value > 0 && $value < 0.005 ) {
			// Would round to $0.00 although the cost is not zero.
			return '< $0.01';
		}
		return '$' . number_format_i18n( round( $value, 2 ), 2 );
	}

	/**
	 * Column label of a volume preset: the admin label or "0.01 lot" / "100 lots".
	 *
	 * @param array $volume Preset.
	 * @return string
	 */
	public static function volume_label( array $volume ) {
		if ( '' !== trim( $volume['label'] ) ) {
			return $volume['label'];
		}
		$lots   = $volume['lots'];
		$plain  = rtrim( rtrim( sprintf( '%.6F', $lots ), '0' ), '.' );
		$dot    = strpos( $plain, '.' );
		$text   = number_format_i18n( $lots, false === $dot ? 0 : strlen( $plain ) - $dot - 1 );
		/* translators: %s: number of lots */
		return sprintf( 1.0 >= $lots ? __( '%s lot', 'fxt-core' ) : __( '%s lots', 'fxt-core' ), $text );
	}

	/**
	 * Validate the calculator settings (Settings > Cost calculator).
	 *
	 * @param array $clean Sanitized settings (calc_* keys).
	 * @return array
	 */
	public static function validate_settings( array $clean ) {
		$volumes = array();
		foreach ( is_array( $clean['calc_volumes'] ) ? $clean['calc_volumes'] : array() as $row ) {
			if ( isset( $row['lots'] ) && (float) $row['lots'] > 0 ) {
				$volumes[] = array( 'lots' => (float) $row['lots'], 'label' => isset( $row['label'] ) ? (string) $row['label'] : '' );
			}
		}
		$clean['calc_volumes'] = $volumes ? $volumes : null;

		$instruments = array();
		$skipped     = array();
		foreach ( is_array( $clean['calc_instruments'] ) ? $clean['calc_instruments'] : array() as $row ) {
			$name = isset( $row['name'] ) ? (string) $row['name'] : '';
			$key  = sanitize_key( isset( $row['key'] ) && '' !== (string) $row['key'] ? (string) $row['key'] : str_replace( '/', '', $name ) );
			if ( '' === $key || isset( $instruments[ $key ] ) || empty( $row['units'] ) || empty( $row['pip_size'] ) ) {
				$skipped[] = '' !== $name ? $name : $key;
				continue;
			}
			$row['key']           = $key;
			$row['quote']         = strtoupper( isset( $row['quote'] ) ? (string) $row['quote'] : '' );
			$instruments[ $key ] = $row;
		}
		if ( $skipped ) {
			/* translators: %s: instrument names */
			add_settings_error( Repository::SETTINGS, 'fxt-instruments', sprintf( __( 'Instruments need a unique key, units per lot and a pip size. Not saved: %s.', 'fxt-core' ), implode( ', ', array_filter( $skipped ) ) ), 'warning' );
		}
		$clean['calc_instruments'] = $instruments ? array_values( $instruments ) : null;
		return $clean;
	}
}
