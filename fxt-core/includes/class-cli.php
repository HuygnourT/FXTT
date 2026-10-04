<?php
/**
 * WP-CLI: wp fxt demo import [--force] | wp fxt demo remove [--yes]
 *
 * @package FXT\Core
 */

namespace FXT\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Manage FX Trading Today demo content.
 */
final class CLI {

	/**
	 * Import (or update) the demo content.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Also replace the front page, menus and reading settings.
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Flags.
	 */
	public function import( $args, $assoc_args ) {
		$this->print( ( new Importer() )->import( ! empty( $assoc_args['force'] ) ) );
	}

	/**
	 * Remove every demo item.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Skip the confirmation.
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Flags.
	 */
	public function remove( $args, $assoc_args ) {
		\WP_CLI::confirm( 'Delete all FX Trading Today demo content?', $assoc_args );
		$this->print( ( new Importer() )->remove() );
	}

	/**
	 * Output log lines.
	 *
	 * @param string[] $lines Log.
	 */
	private function print( array $lines ) {
		foreach ( $lines as $line ) {
			\WP_CLI::log( $line );
		}
		\WP_CLI::success( 'Done.' );
	}
}
