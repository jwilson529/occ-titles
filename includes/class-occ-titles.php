<?php
/**
 * The file that defines the core plugin class.
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://oneclickcontent.com
 * @since      1.0.0
 *
 * @package    Occ_Titles
 * @subpackage Occ_Titles/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks. Also maintains the unique identifier of this plugin
 * as well as the current version of the plugin.
 *
 * @since      1.0.0
 * @package    Occ_Titles
 * @subpackage Occ_Titles/includes
 * @author     James Wilson <info@oneclickcontent.com>
 */
class Occ_Titles {

	/**
	 * Maintains and registers all hooks for the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Occ_Titles_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The string used to uniquely identify this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( defined( 'OCC_TITLES_VERSION' ) ) {
			$this->version = OCC_TITLES_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'oneclickcontent-titles';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Occ_Titles_Loader: Orchestrates the hooks of the plugin.
	 * - Occ_Titles_I18n: Defines internationalization functionality.
	 * - Occ_Titles_Admin: Defines all hooks for the admin area.
	 * - Occ_Titles_Public: Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function load_dependencies() {

		// The class responsible for orchestrating the actions and filters of the core plugin.
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-occ-titles-loader.php';

		// The class responsible for defining internationalization functionality of the plugin.
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-occ-titles-i18n.php';

		// The class responsible for defining all actions that occur in the admin area.
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-occ-titles-admin.php';

		// The class responsible for defining AI helper actions that occur in the admin area.
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-occ-titles-openai-helper.php';

		// The class responsible for defining settings actions that occur in the admin area.
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-occ-titles-settings.php';

		$this->loader = new Occ_Titles_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Occ_Titles_I18n class to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function set_locale() {

		$plugin_i18n = new Occ_Titles_I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality of the plugin.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function define_admin_hooks() {
		$plugin_admin    = new Occ_Titles_Admin( $this->get_plugin_name(), $this->get_version() );
		$plugin_settings = new Occ_Titles_Settings();

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles', 5 );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts', 5 );
		$this->loader->add_action( 'enqueue_block_editor_assets', $plugin_admin, 'occ_titles_enqueue_block_editor_assets', 5 );
		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'add_meta_box', 5 );

		$this->loader->add_action( 'admin_menu', $plugin_settings, 'occ_titles_register_options_page' );
		$this->loader->add_action( 'admin_init', $plugin_settings, 'occ_titles_register_settings' );
		$this->loader->add_action( 'wp_ajax_occ_titles_generate_titles', $plugin_admin, 'generate_titles' );

		$this->loader->add_action( 'admin_init', $plugin_settings, 'occ_titles_handle_assistant_creation' );
		$this->loader->add_action( 'wp_ajax_occ_titles_auto_save', $plugin_settings, 'occ_titles_auto_save' );
		$this->loader->add_action( 'wp_ajax_occ_titles_ajax_validate_openai_api_key', $plugin_settings, 'occ_titles_ajax_validate_openai_api_key' );
		$this->loader->add_action( 'admin_notices', $plugin_settings, 'display_admin_notices' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since 1.0.0
	 * @return string The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since 1.0.0
	 * @return Occ_Titles_Loader Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since 1.0.0
	 * @return string The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
