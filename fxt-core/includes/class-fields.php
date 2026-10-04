<?php
/**
 * Schema-driven field engine: admin rendering, sanitization, REST schema and masking.
 *
 * @package FXT\Core
 */

namespace FXT\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Field engine. Stateless; every method takes a field definition from Schema.
 */
final class Fields {

	/* ---------------------------------------------------------------------
	 * Options
	 * ------------------------------------------------------------------ */

	/**
	 * Resolve select options. The string "markets" maps to the market taxonomy.
	 *
	 * @param array $field Field definition.
	 * @return array value => label
	 */
	public static function options( array $field ) {
		if ( isset( $field['options'] ) && 'markets' === $field['options'] ) {
			$options = array();
			foreach ( Repository::markets() as $market ) {
				$options[ $market['code'] ] = $market['name'];
			}
			return $options;
		}
		return isset( $field['options'] ) ? (array) $field['options'] : array();
	}

	/* ---------------------------------------------------------------------
	 * Sanitization
	 * ------------------------------------------------------------------ */

	/**
	 * Sanitize a raw (unslashed) value according to its field definition.
	 *
	 * @param array $field Field definition.
	 * @param mixed $raw   Raw value from the request.
	 * @return mixed Clean value, or null when empty.
	 */
	public static function sanitize( array $field, $raw ) {
		switch ( $field['type'] ) {
			case 'text':
				$value = is_scalar( $raw ) ? sanitize_text_field( (string) $raw ) : '';
				if ( '' !== $value && ! empty( $field['mask'] ) ) {
					$value = self::mask( $value, $field['mask'] );
				}
				return '' === $value ? null : $value;

			case 'textarea':
				$value = is_scalar( $raw ) ? sanitize_textarea_field( (string) $raw ) : '';
				return '' === $value ? null : $value;

			case 'url':
				$value = is_scalar( $raw ) ? esc_url_raw( trim( (string) $raw ) ) : '';
				return '' === $value ? null : $value;

			case 'number':
				if ( ! is_scalar( $raw ) || '' === trim( (string) $raw ) || ! is_numeric( $raw ) ) {
					return null;
				}
				$value = (float) $raw;
				if ( isset( $field['min'] ) ) {
					$value = max( (float) $field['min'], $value );
				}
				if ( isset( $field['max'] ) ) {
					$value = min( (float) $field['max'], $value );
				}
				return ( isset( $field['step'] ) && 1.0 === (float) $field['step'] ) ? (int) round( $value ) : round( $value, 2 );

			case 'date':
				$value = is_scalar( $raw ) ? trim( (string) $raw ) : '';
				return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : null;

			case 'datetime':
				$value = is_scalar( $raw ) ? str_replace( ' ', 'T', trim( (string) $raw ) ) : '';
				return preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value ) ? $value : null;

			case 'select':
				$options = self::options( $field );
				$value   = is_scalar( $raw ) ? (string) $raw : '';
				if ( array_key_exists( $value, $options ) ) {
					return '' === $value ? null : $value;
				}
				return isset( $field['default'] ) ? $field['default'] : null;

			case 'checkbox':
				return ! empty( $raw );

			case 'list':
				if ( is_array( $raw ) ) {
					$items = $raw;
				} else {
					$pattern = empty( $field['lines'] ) ? '/[,\r\n]+/' : '/[\r\n]+/';
					$items   = preg_split( $pattern, (string) $raw );
				}
				$items = array_values( array_filter( array_map( 'sanitize_text_field', array_map( 'trim', (array) $items ) ), 'strlen' ) );
				return $items ? $items : null;

			case 'post':
				$id   = absint( $raw );
				$type = isset( $field['post_type'] ) ? $field['post_type'] : 'post';
				return ( $id && get_post_type( $id ) === $type ) ? $id : null;

			case 'media':
				$id = absint( $raw );
				return ( $id && 'attachment' === get_post_type( $id ) ) ? $id : null;

			case 'group':
				return self::sanitize_group( $field['fields'], is_array( $raw ) ? $raw : array() );

			case 'repeater':
				$rows = array();
				foreach ( is_array( $raw ) ? $raw : array() as $row ) {
					$clean = self::sanitize_group( $field['fields'], is_array( $row ) ? $row : array() );
					if ( null !== $clean ) {
						$rows[] = $clean;
					}
				}
				return $rows ? $rows : null;

			case 'market_map':
				$map = array();
				$raw = is_array( $raw ) ? $raw : array();
				foreach ( Repository::markets() as $market ) {
					$code = $market['code'];
					if ( isset( $raw[ $code ] ) && is_array( $raw[ $code ] ) ) {
						$clean = self::sanitize_group( $field['fields'], $raw[ $code ] );
						if ( null !== $clean ) {
							$map[ $code ] = $clean;
						}
					}
				}
				return $map ? $map : null;
		}
		return null;
	}

	/**
	 * Sanitize a set of sub-fields. Returns null when every value is empty.
	 *
	 * @param array $fields Sub-field definitions.
	 * @param array $raw    Raw values.
	 * @return array|null
	 */
	public static function sanitize_group( array $fields, array $raw ) {
		$clean     = array();
		$has_value = false;
		foreach ( $fields as $key => $field ) {
			$value         = self::sanitize( $field, isset( $raw[ $key ] ) ? $raw[ $key ] : null );
			$clean[ $key ] = $value;
			if ( null !== $value && false !== $value ) {
				$has_value = true;
			}
		}
		return $has_value ? $clean : null;
	}

	/**
	 * Mask sensitive identifiers. Already-masked values are left as they are.
	 *
	 *   digits: "MT5 12344821" -> "MT5 ****4821"  (tokens of 5+ characters keep the last 4)
	 *   name:   "Tran Nguyen"  -> "T*** N***"
	 *
	 * @param string $value Value to mask.
	 * @param string $mode  'digits' or 'name'.
	 * @return string
	 */
	public static function mask( $value, $mode ) {
		$tokens = preg_split( '/(\s+|-)/', $value, -1, PREG_SPLIT_DELIM_CAPTURE );
		foreach ( $tokens as $i => $token ) {
			if ( '' === trim( $token ) || '-' === $token || false !== strpos( $token, '*' ) ) {
				continue;
			}
			if ( 'name' === $mode ) {
				$tokens[ $i ] = mb_substr( $token, 0, 1 ) . '***';
			} elseif ( mb_strlen( $token ) >= 5 ) {
				$tokens[ $i ] = '****' . mb_substr( $token, -4 );
			}
		}
		return implode( '', $tokens );
	}

	/* ---------------------------------------------------------------------
	 * REST schema
	 * ------------------------------------------------------------------ */

	/**
	 * JSON schema for a field, used by register_*_meta( show_in_rest ).
	 *
	 * @param array $field Field definition.
	 * @return array
	 */
	public static function rest_schema( array $field ) {
		switch ( $field['type'] ) {
			case 'number':
				return array( 'type' => array( 'number', 'null' ) );
			case 'checkbox':
				return array( 'type' => 'boolean' );
			case 'post':
			case 'media':
				return array( 'type' => array( 'integer', 'null' ) );
			case 'list':
				return array( 'type' => array( 'array', 'null' ), 'items' => array( 'type' => 'string' ) );
			case 'group':
				return array( 'type' => array( 'object', 'null' ), 'properties' => self::rest_properties( $field['fields'] ) );
			case 'repeater':
				return array(
					'type'  => array( 'array', 'null' ),
					'items' => array( 'type' => 'object', 'properties' => self::rest_properties( $field['fields'] ) ),
				);
			case 'market_map':
				return array(
					'type'                 => array( 'object', 'null' ),
					'additionalProperties' => array( 'type' => 'object', 'properties' => self::rest_properties( $field['fields'] ) ),
				);
			default:
				return array( 'type' => array( 'string', 'null' ) );
		}
	}

	/**
	 * Schema properties for a set of sub-fields.
	 *
	 * @param array $fields Sub-fields.
	 * @return array
	 */
	private static function rest_properties( array $fields ) {
		$props = array();
		foreach ( $fields as $key => $field ) {
			$props[ $key ] = self::rest_schema( $field );
		}
		return $props;
	}

	/**
	 * The meta "type" WordPress expects for a field.
	 *
	 * @param array $field Field definition.
	 * @return string
	 */
	public static function meta_type( array $field ) {
		$map = array(
			'number'     => 'number',
			'checkbox'   => 'boolean',
			'post'       => 'integer',
			'media'      => 'integer',
			'list'       => 'array',
			'repeater'   => 'array',
			'group'      => 'object',
			'market_map' => 'object',
		);
		return isset( $map[ $field['type'] ] ) ? $map[ $field['type'] ] : 'string';
	}

	/* ---------------------------------------------------------------------
	 * Admin rendering
	 * ------------------------------------------------------------------ */

	/**
	 * Render a labelled field row.
	 *
	 * @param array  $field Field definition.
	 * @param string $name  Input name (e.g. fxt_meta[fxt_score]).
	 * @param mixed  $value Current value.
	 * @param string $id    Unique DOM id.
	 */
	public static function render( array $field, $name, $value, $id ) {
		$type = $field['type'];
		echo '<div class="fxt-field fxt-field--' . esc_attr( $type ) . '">';

		if ( in_array( $type, array( 'group', 'repeater', 'market_map' ), true ) ) {
			echo '<p class="fxt-field__label">' . esc_html( $field['label'] ) . '</p>';
		} elseif ( 'checkbox' !== $type ) {
			echo '<label class="fxt-field__label" for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label>';
		}

		self::render_control( $field, $name, $value, $id );

		if ( ! empty( $field['help'] ) ) {
			echo '<p class="description">' . esc_html( $field['help'] ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Render the input itself.
	 *
	 * @param array  $field Field definition.
	 * @param string $name  Input name.
	 * @param mixed  $value Current value.
	 * @param string $id    DOM id.
	 */
	private static function render_control( array $field, $name, $value, $id ) {
		switch ( $field['type'] ) {
			case 'textarea':
				printf( '<textarea class="widefat" rows="3" id="%s" name="%s">%s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( (string) $value ) );
				break;

			case 'number':
				printf(
					'<input type="number" class="small-text" id="%s" name="%s" value="%s"%s%s step="%s">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( null === $value ? '' : (string) $value ),
					isset( $field['min'] ) ? ' min="' . esc_attr( $field['min'] ) . '"' : '',
					isset( $field['max'] ) ? ' max="' . esc_attr( $field['max'] ) . '"' : '',
					esc_attr( isset( $field['step'] ) ? $field['step'] : 'any' )
				);
				break;

			case 'checkbox':
				printf(
					'<label><input type="checkbox" id="%s" name="%s" value="1"%s> %s</label>',
					esc_attr( $id ),
					esc_attr( $name ),
					checked( ! empty( $value ), true, false ),
					esc_html( $field['label'] )
				);
				break;

			case 'select':
				printf( '<select id="%s" name="%s">', esc_attr( $id ), esc_attr( $name ) );
				foreach ( self::options( $field ) as $option => $label ) {
					printf( '<option value="%s"%s>%s</option>', esc_attr( $option ), selected( (string) $value, (string) $option, false ), esc_html( $label ) );
				}
				echo '</select>';
				break;

			case 'list':
				$items = is_array( $value ) ? $value : array();
				if ( ! empty( $field['lines'] ) ) {
					printf( '<textarea class="widefat" rows="3" id="%s" name="%s">%s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( implode( "\n", $items ) ) );
				} else {
					printf( '<input type="text" class="widefat" id="%s" name="%s" value="%s">', esc_attr( $id ), esc_attr( $name ), esc_attr( implode( ', ', $items ) ) );
				}
				break;

			case 'post':
				$posts = get_posts(
					array(
						'post_type'      => $field['post_type'],
						'posts_per_page' => 200,
						'orderby'        => 'title',
						'order'          => 'ASC',
						'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
					)
				);
				printf( '<select id="%s" name="%s"><option value="">%s</option>', esc_attr( $id ), esc_attr( $name ), esc_html__( 'Select', 'fxt-core' ) );
				foreach ( $posts as $post ) {
					printf( '<option value="%d"%s>%s</option>', (int) $post->ID, selected( (int) $value, (int) $post->ID, false ), esc_html( get_the_title( $post ) ) );
				}
				echo '</select>';
				break;

			case 'media':
				$src = $value ? wp_get_attachment_image_url( (int) $value, 'thumbnail' ) : '';
				printf(
					'<div class="fxt-media" data-fxt-media><img src="%s" alt="" class="fxt-media__preview"%s><input type="hidden" id="%s" name="%s" value="%s"><button type="button" class="button" data-fxt-media-select>%s</button> <button type="button" class="button-link" data-fxt-media-clear>%s</button></div>',
					esc_url( $src ? $src : '' ),
					$src ? '' : ' hidden',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ? (string) (int) $value : '' ),
					esc_html__( 'Choose image', 'fxt-core' ),
					esc_html__( 'Remove', 'fxt-core' )
				);
				break;

			case 'group':
				echo '<div class="fxt-group">';
				foreach ( $field['fields'] as $key => $sub ) {
					self::render( $sub, $name . '[' . $key . ']', isset( $value[ $key ] ) ? $value[ $key ] : null, $id . '-' . $key );
				}
				echo '</div>';
				break;

			case 'repeater':
				self::render_repeater( $field, $name, is_array( $value ) ? $value : array(), $id );
				break;

			case 'market_map':
				$value = is_array( $value ) ? $value : array();
				$markets = Repository::markets();
				if ( ! $markets ) {
					echo '<p class="description">' . esc_html__( 'Add countries under Broker Reviews > Markets first.', 'fxt-core' ) . '</p>';
					break;
				}
				echo '<div class="fxt-market-map">';
				foreach ( $markets as $market ) {
					$code  = $market['code'];
					$row   = isset( $value[ $code ] ) ? $value[ $code ] : array();
					$avail = isset( $row['availability'] ) ? $row['availability'] : 'pending';
					$label = Schema::availability();
					printf(
						'<details class="fxt-market"><summary><strong>%s</strong> %s <span class="fxt-market__state">%s</span></summary><div class="fxt-group">',
						esc_html( $code ),
						esc_html( $market['name'] ),
						esc_html( isset( $label[ $avail ] ) ? $label[ $avail ] : '' )
					);
					foreach ( $field['fields'] as $key => $sub ) {
						self::render( $sub, $name . '[' . $code . '][' . $key . ']', isset( $row[ $key ] ) ? $row[ $key ] : null, $id . '-' . strtolower( $code ) . '-' . $key );
					}
					echo '</div></details>';
				}
				echo '</div>';
				break;

			case 'url':
			case 'date':
			case 'datetime':
			case 'text':
			default:
				$input = array(
					'url'      => 'url',
					'date'     => 'date',
					'datetime' => 'datetime-local',
				);
				printf(
					'<input type="%s" class="widefat" id="%s" name="%s" value="%s">',
					esc_attr( isset( $input[ $field['type'] ] ) ? $input[ $field['type'] ] : 'text' ),
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value )
				);
		}
	}

	/**
	 * Repeater: existing rows + a <template> row; admin.js adds/removes/reorders.
	 *
	 * @param array  $field Field definition.
	 * @param string $name  Input name.
	 * @param array  $rows  Existing rows.
	 * @param string $id    DOM id.
	 */
	private static function render_repeater( array $field, $name, array $rows, $id ) {
		echo '<div class="fxt-repeater" data-fxt-repeater>';
		echo '<div class="fxt-repeater__rows" data-fxt-rows>';
		foreach ( array_values( $rows ) as $index => $row ) {
			self::render_repeater_row( $field, $name, $row, $id, (string) $index );
		}
		echo '</div><template data-fxt-template>';
		self::render_repeater_row( $field, $name, array(), $id, '__INDEX__' );
		echo '</template>';
		printf( '<button type="button" class="button" data-fxt-add>%s</button>', esc_html__( 'Add row', 'fxt-core' ) );
		echo '</div>';
	}

	/**
	 * One repeater row.
	 *
	 * @param array  $field Field definition.
	 * @param string $name  Input name.
	 * @param array  $row   Row values.
	 * @param string $id    DOM id.
	 * @param string $index Row index or placeholder.
	 */
	private static function render_repeater_row( array $field, $name, array $row, $id, $index ) {
		echo '<div class="fxt-repeater__row" data-fxt-row><div class="fxt-group">';
		foreach ( $field['fields'] as $key => $sub ) {
			self::render( $sub, $name . '[' . $index . '][' . $key . ']', isset( $row[ $key ] ) ? $row[ $key ] : null, $id . '-' . $index . '-' . $key );
		}
		echo '</div><div class="fxt-repeater__actions">';
		printf( '<button type="button" class="button-link" data-fxt-up>%s</button>', esc_html__( 'Move up', 'fxt-core' ) );
		printf( '<button type="button" class="button-link" data-fxt-down>%s</button>', esc_html__( 'Move down', 'fxt-core' ) );
		printf( '<button type="button" class="button-link button-link-delete" data-fxt-remove>%s</button>', esc_html__( 'Remove', 'fxt-core' ) );
		echo '</div></div>';
	}
}
