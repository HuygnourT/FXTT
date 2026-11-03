<?php
/**
 * Markdown to Gutenberg core blocks (1.4.7). No dependency.
 *
 * Converts standard Markdown (CommonMark subset + GitHub tables) into a tree
 * of core blocks: { name, attributes, innerBlocks }. The block editor builds
 * the blocks from this tree (wp.blocks.createBlock), so the saved markup is
 * always the editor's own and every block stays editable.
 *
 *   # .. ######   core/heading (H1 becomes H2: the post title is the H1)
 *   paragraph     core/paragraph
 *   - * + / 1.    core/list + core/list-item (nested lists kept)
 *   >             core/quote (paragraphs, lists... inside)
 *   | a | b |     core/table (header row, column alignment), theme style "Data table"
 *   --- *** ___   core/separator
 *   ![alt](url)   core/image (alone on its line; only valid, reachable images)
 *   ```           core/code
 *   **b** *i* ~~s~~ `code` [link](url) <https://...>   inline formatting
 *
 * Raw HTML in the Markdown is shown as text, never as markup. Links only
 * keep http(s), mailto, tel, relative and #anchor targets.
 *
 * @package FXT\Core
 */

namespace FXT\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Markdown converter.
 */
class Markdown {

	/** Largest Markdown accepted (bytes). */
	const MAX_BYTES = 1048576;

	/** @var string[] Notes for the person importing (images skipped...). */
	private $warnings = array();

	/** @var callable|null Image check: fn( string $url ) => array|null { url, id }. */
	private $image_check;

	/** @var array Placeholders of the inline formatter. */
	private $slots = array();

	/** @var bool H1 already reported. */
	private $h1_noted = false;

	/**
	 * @param callable|null $image_check Returns { url, id } for a usable image, null otherwise.
	 */
	public function __construct( $image_check = null ) {
		$this->image_check = $image_check;
	}

	/**
	 * Convert Markdown to a block tree.
	 *
	 * @param string $markdown Markdown text.
	 * @return array { blocks: array[], warnings: string[] }
	 */
	public function convert( $markdown ) {
		$this->warnings = array();
		$text           = str_replace( array( "\r\n", "\r" ), "\n", (string) $markdown );
		$text           = preg_replace( '/^\xEF\xBB\xBF/', '', $text ); // BOM.
		$text           = str_replace( "\t", '    ', $text );
		$lines          = explode( "\n", $text );
		$lines          = $this->strip_front_matter( $lines );
		$blocks         = $this->blocks( $lines );
		return array(
			'blocks'   => $blocks,
			'warnings' => array_values( array_unique( $this->warnings ) ),
		);
	}

	/* ---------------------------------------------------------------------
	 * Block level
	 * ------------------------------------------------------------------ */

	/**
	 * YAML front matter at the very top ("---" ... "---") is metadata, not content.
	 *
	 * @param string[] $lines Lines.
	 * @return string[]
	 */
	private function strip_front_matter( array $lines ) {
		if ( ! isset( $lines[0] ) || '---' !== trim( $lines[0] ) ) {
			return $lines;
		}
		$count = count( $lines );
		for ( $i = 1; $i < min( $count, 60 ); $i++ ) {
			if ( '---' === trim( $lines[ $i ] ) || '...' === trim( $lines[ $i ] ) ) {
				if ( $i > 1 && preg_match( '/^[A-Za-z0-9_-]+\s*:/', $lines[1] ) ) {
					$this->warnings[] = __( 'Front matter at the top of the file (between --- lines) was not imported: it holds metadata, not content.', 'fxt-core' );
					return array_slice( $lines, $i + 1 );
				}
				return $lines;
			}
		}
		return $lines;
	}

	/**
	 * Parse lines into blocks.
	 *
	 * @param string[] $lines Lines.
	 * @return array[]
	 */
	private function blocks( array $lines ) {
		$out   = array();
		$count = count( $lines );
		$i     = 0;
		while ( $i < $count ) {
			$line = $lines[ $i ];
			if ( '' === trim( $line ) ) {
				++$i;
				continue;
			}

			// Fenced code.
			if ( preg_match( '/^ {0,3}(`{3,}|~{3,})\s*([\w+-]*)/', $line, $m ) ) {
				$fence = $m[1];
				$code  = array();
				++$i;
				while ( $i < $count && ! preg_match( '/^ {0,3}' . preg_quote( $fence[0], '/' ) . '{' . strlen( $fence ) . ',}\s*$/', $lines[ $i ] ) ) {
					$code[] = $lines[ $i ];
					++$i;
				}
				++$i; // Closing fence.
				$out[] = $this->block( 'core/code', array( 'content' => esc_html( implode( "\n", $code ) ) ) );
				continue;
			}

			// ATX heading.
			if ( preg_match( '/^ {0,3}(#{1,6})\s+(.*?)\s*#*\s*$/', $line, $m ) || preg_match( '/^ {0,3}(#{1,6})\s*$/', $line, $m ) ) {
				$out[] = $this->heading( strlen( $m[1] ), isset( $m[2] ) ? $m[2] : '' );
				++$i;
				continue;
			}

			// Horizontal rule (before lists: "* * *").
			if ( $this->is_rule( $line ) ) {
				$out[] = $this->block( 'core/separator', array() );
				++$i;
				continue;
			}

			// Table: a row with pipes followed by a separator row.
			if ( false !== strpos( $line, '|' ) && isset( $lines[ $i + 1 ] ) && $this->is_table_separator( $lines[ $i + 1 ] ) ) {
				$rows = array( $line, $lines[ $i + 1 ] );
				$i   += 2;
				while ( $i < $count && '' !== trim( $lines[ $i ] ) && false !== strpos( $lines[ $i ], '|' ) ) {
					$rows[] = $lines[ $i ];
					++$i;
				}
				$out[] = $this->table( $rows );
				continue;
			}

			// Blockquote.
			if ( preg_match( '/^ {0,3}>/', $line ) ) {
				$inner = array();
				while ( $i < $count && ( preg_match( '/^ {0,3}>/', $lines[ $i ] ) || ( '' !== trim( $lines[ $i ] ) && ! empty( $inner ) && '' !== trim( end( $inner ) ) && ! $this->starts_block( $lines[ $i ] ) ) ) ) {
					$inner[] = preg_replace( '/^ {0,3}> ?/', '', $lines[ $i ] );
					++$i;
				}
				$children = $this->blocks( $inner );
				if ( ! $children ) {
					continue;
				}
				$out[] = $this->block( 'core/quote', array(), $children );
				continue;
			}

			// List.
			if ( $this->list_marker( $line ) ) {
				list( $block, $i ) = $this->parse_list( $lines, $i );
				$out[]             = $block;
				continue;
			}

			// Image alone on its line.
			if ( preg_match( '/^\s*!\[([^\]]*)\]\(\s*<?([^\s)>]+)>?(?:\s+["\']([^"\']*)["\'])?\s*\)\s*$/', $line, $m ) ) {
				$image = $this->image( $m[2], $m[1], isset( $m[3] ) ? $m[3] : '' );
				if ( $image ) {
					$out[] = $image;
				}
				++$i;
				continue;
			}

			// Paragraph (or setext heading).
			$para = array();
			while ( $i < $count && '' !== trim( $lines[ $i ] ) ) {
				if ( $para && preg_match( '/^ {0,3}(=+|-+)\s*$/', $lines[ $i ], $s ) ) {
					// Setext heading: text underlined with === (H1 -> H2) or --- (H2).
					$out[] = $this->heading( '=' === $s[1][0] ? 1 : 2, implode( ' ', array_map( 'trim', $para ) ) );
					$para  = array();
					++$i;
					continue 2;
				}
				if ( $para && $this->starts_block( $lines[ $i ] ) ) {
					break;
				}
				$para[] = $lines[ $i ];
				++$i;
			}
			if ( $para ) {
				$out[] = $this->block( 'core/paragraph', array( 'content' => $this->inline_lines( $para ) ) );
			}
		}
		return $out;
	}

	/**
	 * Does this line start a block other than a paragraph?
	 *
	 * @param string $line Line.
	 * @return bool
	 */
	private function starts_block( $line ) {
		return (bool) ( preg_match( '/^ {0,3}(#{1,6}(\s|$)|>|`{3,}|~{3,})/', $line ) || $this->is_rule( $line ) || $this->list_marker( $line ) );
	}

	/**
	 * Horizontal rule: three or more -, * or _ (spaces allowed).
	 *
	 * @param string $line Line.
	 * @return bool
	 */
	private function is_rule( $line ) {
		return (bool) preg_match( '/^ {0,3}([-*_])(\s*\1){2,}\s*$/', $line );
	}

	/**
	 * Table separator row, e.g. "| :--- | ---: |".
	 *
	 * @param string $line Line.
	 * @return bool
	 */
	private function is_table_separator( $line ) {
		return false !== strpos( $line, '-' ) && (bool) preg_match( '/^\s*\|?\s*:?-+:?\s*(\|\s*:?-+:?\s*)*\|?\s*$/', $line );
	}

	/**
	 * List marker of a line.
	 *
	 * @param string $line Line.
	 * @return array|null { indent, ordered, number, width, text }
	 */
	private function list_marker( $line ) {
		if ( ! preg_match( '/^( *)([-*+]|(\d{1,9})[.)])( +|$)(.*)$/', $line, $m ) ) {
			return null;
		}
		return array(
			'indent'  => strlen( $m[1] ),
			'ordered' => '' !== $m[3],
			'number'  => '' !== $m[3] ? (int) $m[3] : 1,
			'width'   => strlen( $m[1] . $m[2] . $m[4] ),
			'text'    => $m[5],
		);
	}

	/**
	 * Parse one list (and the lists nested in it) starting at line $i.
	 *
	 * @param string[] $lines Lines.
	 * @param int      $i     First line (a list marker).
	 * @return array { 0: block, 1: next line index }
	 */
	private function parse_list( array $lines, $i ) {
		$count = count( $lines );
		$first = $this->list_marker( $lines[ $i ] );
		$base  = $first['indent'];
		$items = array();
		while ( $i < $count ) {
			$line = $lines[ $i ];
			if ( '' === trim( $line ) ) {
				// A blank line ends the list unless the list goes on after it.
				$next = $i + 1;
				while ( $next < $count && '' === trim( $lines[ $next ] ) ) {
					++$next;
				}
				if ( $next >= $count ) {
					$i = $next;
					break;
				}
				$marker = $this->list_marker( $lines[ $next ] );
				$indent = strlen( $lines[ $next ] ) - strlen( ltrim( $lines[ $next ], ' ' ) );
				if ( ( $marker && $marker['indent'] <= $base + 1 && $marker['ordered'] === $first['ordered'] ) || ( $items && $indent > $base ) ) {
					if ( $items ) {
						$items[ count( $items ) - 1 ]['sub'][] = '';
					}
					$i = $next;
					continue;
				}
				$i = $next;
				break;
			}
			$marker = $this->list_marker( $line );
			$indent = strlen( $line ) - strlen( ltrim( $line, ' ' ) );
			if ( $marker && $marker['indent'] <= $base + 1 ) {
				if ( $marker['ordered'] !== $first['ordered'] ) {
					break; // Another kind of list starts.
				}
				$items[] = array( 'text' => array( $marker['text'] ), 'sub' => array(), 'width' => $marker['width'] );
				++$i;
				continue;
			}
			if ( ! $items ) {
				break;
			}
			$last = count( $items ) - 1;
			if ( $indent > $base ) {
				// Nested content (another list, or text of this item).
				if ( $items[ $last ]['sub'] || $marker ) {
					$items[ $last ]['sub'][] = $line;
				} else {
					$items[ $last ]['text'][] = $line;
				}
				++$i;
				continue;
			}
			if ( $this->starts_block( $line ) || $this->is_table_separator( isset( $lines[ $i + 1 ] ) ? $lines[ $i + 1 ] : '' ) ) {
				break;
			}
			// Lazy continuation of the item's text.
			$items[ $last ]['text'][] = $line;
			++$i;
		}

		$children = array();
		foreach ( $items as $item ) {
			$content = $this->inline_lines( $item['text'] );
			$inner   = array();
			if ( $item['sub'] ) {
				$sub = $this->dedent( $item['sub'] );
				foreach ( $this->blocks( $sub ) as $child ) {
					if ( 'core/list' === $child['name'] ) {
						$inner[] = $child;
					} elseif ( isset( $child['attributes']['content'] ) && '' !== $child['attributes']['content'] ) {
						// A list item holds text and nested lists only: other text joins the item.
						$content .= ( '' !== $content ? '<br>' : '' ) . $child['attributes']['content'];
					} else {
						$this->warnings[] = __( 'Content nested in a list item other than text or a list (quote, table, image...) was not imported. Move it out of the list.', 'fxt-core' );
					}
				}
			}
			$children[] = $this->block( 'core/list-item', array( 'content' => $content ), $inner );
		}
		$attrs = array();
		if ( $first['ordered'] ) {
			$attrs['ordered'] = true;
			if ( 1 !== $first['number'] ) {
				$attrs['start'] = $first['number'];
			}
		}
		return array( $this->block( 'core/list', $attrs, $children ), $i );
	}

	/**
	 * Remove the common indentation of nested lines.
	 *
	 * @param string[] $lines Lines.
	 * @return string[]
	 */
	private function dedent( array $lines ) {
		$min = null;
		foreach ( $lines as $line ) {
			if ( '' !== trim( $line ) ) {
				$indent = strlen( $line ) - strlen( ltrim( $line, ' ' ) );
				$min    = null === $min ? $indent : min( $min, $indent );
			}
		}
		return array_map(
			static function ( $line ) use ( $min ) {
				return (string) substr( $line, min( (int) $min, strlen( $line ) - strlen( ltrim( $line, ' ' ) ) ) );
			},
			$lines
		);
	}

	/**
	 * Heading block. H1 becomes H2 (the post title is the page's H1).
	 *
	 * @param int    $level Markdown level.
	 * @param string $text  Text.
	 * @return array
	 */
	private function heading( $level, $text ) {
		if ( 1 === $level ) {
			$level = 2;
			if ( ! $this->h1_noted ) {
				$this->h1_noted   = true;
				$this->warnings[] = __( 'H1 headings (#) were imported as H2: the post title is already the H1 of the page.', 'fxt-core' );
			}
		}
		return $this->block( 'core/heading', array( 'level' => $level, 'content' => $this->inline( trim( $text ) ) ) );
	}

	/**
	 * Table block (header row, body rows, column alignment).
	 *
	 * @param string[] $rows Raw rows (row 2 = separator).
	 * @return array
	 */
	private function table( array $rows ) {
		$head   = $this->cells( $rows[0] );
		$aligns = array();
		foreach ( $this->cells( $rows[1] ) as $index => $spec ) {
			$spec             = trim( $spec );
			$left             = ':' === substr( $spec, 0, 1 );
			$right            = ':' === substr( $spec, -1 );
			$aligns[ $index ] = $left && $right ? 'center' : ( $right ? 'right' : ( $left ? 'left' : '' ) );
		}
		$columns = count( $head );
		$build   = function ( array $cells, $tag ) use ( $columns, $aligns ) {
			$out = array();
			for ( $c = 0; $c < $columns; $c++ ) {
				$cell = array(
					'content' => $this->inline( isset( $cells[ $c ] ) ? trim( $cells[ $c ] ) : '' ),
					'tag'     => $tag,
				);
				if ( ! empty( $aligns[ $c ] ) ) {
					$cell['align'] = $aligns[ $c ];
				}
				$out[] = $cell;
			}
			return array( 'cells' => $out );
		};
		$body = array();
		foreach ( array_slice( $rows, 2 ) as $row ) {
			$cells = $this->cells( $row );
			if ( count( $cells ) > $columns ) {
				$this->warnings[] = __( 'A table row had more cells than the header row; the extra cells were not imported.', 'fxt-core' );
			}
			$body[] = $build( $cells, 'td' );
		}
		return $this->block(
			'core/table',
			array(
				'className' => 'is-style-data',
				'head'      => array( $build( $head, 'th' ) ),
				'body'      => $body,
			)
		);
	}

	/**
	 * Cells of a table row (pipes inside `code` or escaped as \| are kept).
	 *
	 * @param string $row Row.
	 * @return string[]
	 */
	private function cells( $row ) {
		$row   = trim( $row );
		$row   = preg_replace( '/^\|/', '', $row );
		$row   = preg_replace( '/(?<!\\\\)\|$/', '', $row );
		$cells = array();
		$cell  = '';
		$code  = false;
		$len   = strlen( $row );
		for ( $k = 0; $k < $len; $k++ ) {
			$ch = $row[ $k ];
			if ( '\\' === $ch && $k + 1 < $len && '|' === $row[ $k + 1 ] ) {
				$cell .= '|';
				++$k;
				continue;
			}
			if ( '`' === $ch ) {
				$code = ! $code;
			}
			if ( '|' === $ch && ! $code ) {
				$cells[] = $cell;
				$cell    = '';
				continue;
			}
			$cell .= $ch;
		}
		$cells[] = $cell;
		return $cells;
	}

	/**
	 * Image block, or null (with a note) when the image cannot be used.
	 *
	 * @param string $url   URL.
	 * @param string $alt   Alternative text.
	 * @param string $title Title (used as caption).
	 * @return array|null
	 */
	private function image( $url, $alt, $title ) {
		$url   = html_entity_decode( trim( $url ), ENT_QUOTES, 'UTF-8' );
		$found = $this->image_check ? call_user_func( $this->image_check, $url ) : null;
		if ( ! $found ) {
			/* translators: %s: image URL */
			$this->warnings[] = sprintf( __( 'Image not imported (not a valid, reachable image address): %s', 'fxt-core' ), $url );
			return null;
		}
		$attrs = array(
			'url' => $found['url'],
			'alt' => trim( wp_strip_all_tags( $alt ) ),
		);
		if ( ! empty( $found['id'] ) ) {
			$attrs['id']       = (int) $found['id'];
			$attrs['sizeSlug'] = 'large';
		}
		if ( '' !== trim( $title ) ) {
			$attrs['caption'] = esc_html( trim( $title ) );
		}
		return $this->block( 'core/image', $attrs );
	}

	/**
	 * Block node.
	 *
	 * @param string $name  Block name.
	 * @param array  $attrs Attributes.
	 * @param array  $inner Inner blocks.
	 * @return array
	 */
	private function block( $name, array $attrs, array $inner = array() ) {
		return array(
			'name'        => $name,
			'attributes'  => (object) $attrs,
			'innerBlocks' => $inner,
		);
	}

	/* ---------------------------------------------------------------------
	 * Inline level
	 * ------------------------------------------------------------------ */

	/**
	 * Lines of one paragraph: joined with spaces; two trailing spaces or a
	 * trailing backslash make a line break.
	 *
	 * @param string[] $lines Lines.
	 * @return string HTML.
	 */
	private function inline_lines( array $lines ) {
		$parts = array();
		$last  = count( $lines ) - 1;
		foreach ( $lines as $n => $line ) {
			$break = $n < $last && ( preg_match( '/ {2,}$/', $line ) || preg_match( '/(?<!\\\\)\\\\$/', $line ) );
			$line  = trim( preg_replace( '/(?<!\\\\)\\\\$/', '', $line ) );
			$parts[] = $line . ( $break ? "\x01BR\x01" : '' );
		}
		$text = implode( ' ', $parts );
		$text = str_replace( "\x01BR\x01 ", "\x01BR\x01", $text );
		return str_replace( "\x01BR\x01", '<br>', $this->inline( $text ) );
	}

	/**
	 * Inline Markdown to safe HTML.
	 *
	 * @param string $text Text.
	 * @return string HTML (a, strong, em, s, code, br only).
	 */
	public function inline( $text ) {
		$this->slots = array();
		$text        = (string) $text;
		$keep        = "\x01BR\x01";

		// Code spans first: their content is literal.
		$text = preg_replace_callback(
			'/(`+)(.+?)(?<!`)\1(?!`)/s',
			function ( $m ) {
				return $this->slot( '<code>' . esc_html( trim( $m[2] ) ) . '</code>' );
			},
			$text
		);
		// Backslash escapes.
		$text = preg_replace_callback(
			'/\\\\([\\\\`*_{}\[\]()#+\-.!|>~<"])/',
			function ( $m ) {
				return $this->slot( esc_html( $m[1] ) );
			},
			$text
		);
		// Autolinks <https://...> and <name@example.com>.
		$text = preg_replace_callback(
			'/<((?:https?:\/\/|mailto:)[^\s<>]+|[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,})>/',
			function ( $m ) {
				$href = false === strpos( $m[1], ':' ) ? 'mailto:' . $m[1] : $m[1];
				$url  = $this->safe_url( $href );
				return '' === $url ? $this->slot( esc_html( $m[1] ) ) : $this->slot( '<a href="' . esc_attr( $url ) . '">' . esc_html( $m[1] ) . '</a>' );
			},
			$text
		);
		$text = str_replace( $keep, "\x02", $text );
		// Anything else that looks like HTML is text.
		$text = esc_html( $text );
		$text = str_replace( "\x02", $keep, $text );

		// Inline images inside text: a link to the image (images need their own line).
		$text = preg_replace_callback(
			'/!\[([^\]]*)\]\(\s*([^\s)]+)(?:\s+&quot;[^&]*&quot;)?\s*\)/',
			function ( $m ) {
				$this->warnings[] = __( 'Images inside a sentence were imported as links. Put an image on its own line to import it as an image block.', 'fxt-core' );
				$url              = $this->safe_url( html_entity_decode( $m[2], ENT_QUOTES, 'UTF-8' ) );
				$label            = '' !== trim( $m[1] ) ? $m[1] : $m[2];
				return '' === $url ? $label : '<a href="' . esc_attr( $url ) . '">' . $label . '</a>';
			},
			$text
		);
		// Links [text](url "title").
		$text = preg_replace_callback(
			'/\[((?:[^\[\]]|\[[^\]]*\])*)\]\(\s*<?((?:[^\s()<>]|\([^\s()]*\))*)>?(?:\s+(?:&quot;(.*?)&quot;|&#039;(.*?)&#039;))?\s*\)/',
			function ( $m ) {
				$url   = $this->safe_url( html_entity_decode( $m[2], ENT_QUOTES, 'UTF-8' ) );
				$title = isset( $m[3] ) && '' !== $m[3] ? $m[3] : ( isset( $m[4] ) ? $m[4] : '' );
				if ( '' === $url ) {
					return $m[1];
				}
				return $this->slot( '<a href="' . esc_attr( $url ) . '"' . ( '' !== $title ? ' title="' . $title . '"' : '' ) . '>' ) . $m[1] . $this->slot( '</a>' );
			},
			$text
		);
		// Emphasis: ***both***, **bold**, __bold__, *italic*, _italic_, ~~strike~~.
		$text = preg_replace( '/\*\*\*(?=\S)(.+?)(?<=\S)\*\*\*/s', '<strong><em>$1</em></strong>', $text );
		$text = preg_replace( '/\*\*(?=\S)(.+?)(?<=\S)\*\*/s', '<strong>$1</strong>', $text );
		$text = preg_replace( '/(?<![A-Za-z0-9_])__(?=\S)(.+?)(?<=\S)__(?![A-Za-z0-9_])/s', '<strong>$1</strong>', $text );
		$text = preg_replace( '/(?<![*\w])\*(?=[^\s*])(.+?)(?<=[^\s*])\*(?![*\w])/s', '<em>$1</em>', $text );
		$text = preg_replace( '/(?<![A-Za-z0-9_])_(?=[^\s_])(.+?)(?<=[^\s_])_(?![A-Za-z0-9_])/s', '<em>$1</em>', $text );
		$text = preg_replace( '/~~(?=\S)(.+?)(?<=\S)~~/s', '<s>$1</s>', $text );

		// Put the placeholders back (several passes: links hold code spans).
		for ( $pass = 0; $pass < 3 && false !== strpos( $text, "\x03" ); $pass++ ) {
			$text = preg_replace_callback(
				'/\x03(\d+)\x03/',
				function ( $m ) {
					return isset( $this->slots[ (int) $m[1] ] ) ? $this->slots[ (int) $m[1] ] : '';
				},
				$text
			);
		}
		$text = str_replace( $keep, '<br>', $text );
		// Final guard: only the tags this converter writes.
		return trim(
			wp_kses(
				$text,
				array(
					'a'      => array( 'href' => true, 'title' => true ),
					'strong' => array(),
					'em'     => array(),
					's'      => array(),
					'code'   => array(),
					'br'     => array(),
				)
			)
		);
	}

	/**
	 * Store finished HTML out of reach of the next steps.
	 *
	 * @param string $html HTML.
	 * @return string Placeholder.
	 */
	private function slot( $html ) {
		$this->slots[] = $html;
		return "\x03" . ( count( $this->slots ) - 1 ) . "\x03";
	}

	/**
	 * Allowed link targets: http(s), mailto, tel, relative paths and #anchors.
	 *
	 * @param string $url URL.
	 * @return string '' when not allowed.
	 */
	public function safe_url( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return '';
		}
		if ( '#' === $url[0] || '/' === $url[0] || '.' === $url[0] || '?' === $url[0] ) {
			return esc_url_raw( $url, array( 'http', 'https' ) ) ? $url : '';
		}
		if ( ! preg_match( '/^(https?:\/\/|mailto:|tel:)/i', $url ) ) {
			// "example.com/page" without a scheme is not a usable link target.
			return preg_match( '/^[\w.-]+\.[a-z]{2,}(\/|$)/i', $url ) ? esc_url_raw( 'https://' . $url ) : '';
		}
		return (string) esc_url_raw( $url, array( 'http', 'https', 'mailto', 'tel' ) );
	}

	/**
	 * Notes collected during the last conversion.
	 *
	 * @return string[]
	 */
	public function warnings() {
		return $this->warnings;
	}
}
