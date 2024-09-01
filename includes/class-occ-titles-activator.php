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
		// Set default Assistant ID if it doesn't exist.
		if ( false === get_option( 'occ_titles_assistant_id' ) ) {
			update_option( 'occ_titles_assistant_id', '1' );
		}

		// Set default post types if they don't exist.
		if ( false === get_option( 'occ_titles_post_types' ) ) {
			update_option( 'occ_titles_post_types', array( 'post' ) ); // Set 'post' as the default post type.
		}

		// Set default model if it doesn't exist.
		if ( false === get_option( 'occ_titles_openai_model' ) ) {
			update_option( 'occ_titles_openai_model', 'gpt-4o-mini' ); // Set 'gpt-4o-mini' as the default model.
		}
	}
}
