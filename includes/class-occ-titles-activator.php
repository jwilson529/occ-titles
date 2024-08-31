<?php
/**
 * Fired during plugin activation
 *
 * @link       https://oneclickcontent.com
 * @since      1.0.0
 *
 * @package    Occ_Titles
 * @subpackage Occ_Titles/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Occ_Titles
 * @subpackage Occ_Titles/includes
 * @author     James Wilson <info@oneclickcontent.com>
 */
class Occ_Titles_Activator {

	/**
	 * Runs on plugin activation.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		// Check if the 'occ_titles_assistant_id' option exists.
		if ( false === get_option( 'occ_titles_assistant_id' ) ) {
			// Set the option with a placeholder value.
			update_option( 'occ_titles_assistant_id', '1' );
		}
	}
}
