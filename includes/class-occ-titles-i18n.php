<?php
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://oneclickcontent.com
 * @since      1.0.0
 *
 * @package    Occ_Titles
 * @subpackage Occ_Titles/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Occ_Titles
 * @subpackage Occ_Titles/includes
 * @author     James Wilson <info@oneclickcontent.com>
 */
class Occ_Titles_I18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'oneclickcontent-titles',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}
