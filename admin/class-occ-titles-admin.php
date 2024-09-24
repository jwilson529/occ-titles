<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Handles the admin-specific hooks for enqueuing stylesheets and JavaScript,
 * and provides the functionality for generating SEO-optimized titles using OpenAI.
 *
 * @link       https://oneclickcontent.com
 * @since      1.0.0
 *
 * @package    Occ_Titles
 * @subpackage Occ_Titles/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and handles the admin-specific hooks.
 *
 * @package    Occ_Titles
 * @subpackage Occ_Titles/admin
 */
class Occ_Titles_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Helper class instance for handling OpenAI API requests.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var Occ_Titles_OpenAI_Helper $openai_helper Instance of the helper class.
	 */
	private $openai_helper;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name   = $plugin_name;
		$this->version       = $version;
		$this->openai_helper = new Occ_Titles_OpenAI_Helper();
	}


	/**
	 * Add a custom meta box to the Block Editor.
	 *
	 * @since 1.0.0
	 */
	public function add_meta_box() {
		$screen = get_current_screen();
		if ( 'post' === $screen->base && $this->is_block_editor_active() ) {
			add_meta_box(
				'occ_titles_meta_box',
				__( 'OCC Titles Meta Box', 'oneclickcontent_titles' ),
				array( $this, 'render_meta_box_content' ),
				$screen->post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render the content of the custom meta box.
	 *
	 * @since 1.0.0
	 */
	public function render_meta_box_content() {
		echo '';
	}

	/**
	 * Check if the Block Editor (Gutenberg) is active.
	 *
	 * @since 1.0.0
	 * @return bool True if Block Editor is active, false otherwise.
	 */
	private function is_block_editor_active() {
		if ( function_exists( 'use_block_editor_for_post_type' ) && use_block_editor_for_post_type( get_post_type() ) ) {
			return true;
		}
		return false;
	}


	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_styles() {
		$screen              = get_current_screen();
		$selected_post_types = get_option( 'occ_titles_post_types', array() );

		if ( 'post' === $screen->base && in_array( $screen->post_type, $selected_post_types, true ) && ! wp_should_load_block_editor_scripts_and_styles() ) {
			wp_enqueue_style(
				$this->plugin_name,
				plugin_dir_url( __FILE__ ) . 'css/occ-titles-admin.css',
				array(),
				$this->version,
				'all'
			);
		} elseif ( 'settings_page_occ_titles-settings' === $screen->base ) {
			wp_enqueue_style(
				$this->plugin_name,
				plugin_dir_url( __FILE__ ) . 'css/occ-titles-admin.css',
				array(),
				$this->version,
				'all'
			);
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts() {
		$screen              = get_current_screen(); // Get current screen object.
		$selected_post_types = get_option( 'occ_titles_post_types', array() );

		// Enqueue the settings script on all admin pages.
		wp_enqueue_script(
			'occ-titles-settings',
			plugin_dir_url( __FILE__ ) . 'js/occ-titles-settings.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		// Enqueue scripts on the selected post type edit pages.
		if ( 'post' === $screen->base && in_array( $screen->post_type, $selected_post_types, true ) ) {
			wp_enqueue_script(
				'occ-titles-utils',
				plugin_dir_url( __FILE__ ) . 'js/occ-titles-utils.js',
				array( 'jquery' ),
				$this->version,
				true
			);

			wp_enqueue_script(
				'occ-titles-admin',
				plugin_dir_url( __FILE__ ) . 'js/occ-titles-admin.js',
				array( 'jquery', 'occ-titles-utils', 'occ-titles-settings' ),
				$this->version,
				true
			);

			wp_localize_script(
				'occ-titles-admin',
				'occ_titles_admin_vars',
				array(
					'ajax_url'              => admin_url( 'admin-ajax.php' ),
					'occ_titles_ajax_nonce' => wp_create_nonce( 'occ_titles_ajax_nonce' ),
					'selected_post_types'   => $selected_post_types,
					'current_post_type'     => $screen->post_type,
					'svg_url'               => plugin_dir_url( __DIR__ ) . 'img/ai-sparkle.svg',
				)
			);
		} elseif ( 'settings_page_occ_titles-settings' === $screen->base ) {
			wp_enqueue_script(
				'occ-titles-admin-post',
				plugin_dir_url( __FILE__ ) . 'js/occ-titles-admin.js',
				array( 'jquery', 'occ-titles-settings' ),
				$this->version,
				true
			);

			wp_localize_script(
				'occ-titles-admin-post',
				'occ_titles_admin_vars',
				array(
					'ajax_url'              => admin_url( 'admin-ajax.php' ),
					'occ_titles_ajax_nonce' => wp_create_nonce( 'occ_titles_ajax_nonce' ),
					'selected_post_types'   => $selected_post_types,
					'current_post_type'     => $screen->post_type,
					'svg_url'               => plugin_dir_url( __DIR__ ) . 'img/ai-sparkle.svg',
				)
			);
		}
	}

	/**
	 * Enqueue block editor styles.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function occ_titles_enqueue_block_editor_assets() {
		wp_enqueue_style(
			'occ-titles-editor-css',
			plugin_dir_url( __FILE__ ) . 'css/occ-titles-admin.css',
			array(),
			$this->version
		);
	}



	/**
	 * Handle the AJAX request to generate titles using OpenAI.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function generate_titles() {
		// Check nonce for security.
		if ( ! check_ajax_referer( 'occ_titles_ajax_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Nonce verification failed.', 'oneclickcontent_titles' ) ) );
		}

		// Verify the user has the appropriate capability.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'oneclickcontent_titles' ) ) );
		}

		// Sanitize and get incoming data.
		$content      = isset( $_POST['content'] ) ? sanitize_text_field( wp_unslash( $_POST['content'] ) ) : '';
		$style        = isset( $_POST['style'] ) ? sanitize_text_field( wp_unslash( $_POST['style'] ) ) : '';
		$api_key      = get_option( 'occ_titles_openai_api_key' );
		$assistant_id = get_option( 'occ_titles_assistant_id' );

		// Check for missing data.
		if ( empty( $content ) || empty( $api_key ) || empty( $assistant_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing data.', 'oneclickcontent_titles' ) ) );
		}

		// Modify the query with the selected style, if provided.
		$query = $content;
		if ( ! empty( $style ) ) {
			$query .= "\n\nStyle: " . ucfirst( $style );
		} else {
			$query .= "\n\nStyle: Choose the most suitable style";
		}

		// Step 1: Create a new thread.
		$thread_id = $this->openai_helper->create_thread( $api_key );
		if ( ! $thread_id ) {
			wp_send_json_error( array( 'message' => __( 'Failed to create thread.', 'oneclickcontent_titles' ) ) );
		}

		// Step 2: Add message and run thread.
		$result = $this->openai_helper->add_message_and_run_thread( $api_key, $thread_id, $assistant_id, $query );

		// Check if the result contains the expected data structure.
		if ( isset( $result['titles'] ) && is_array( $result['titles'] ) ) {
			wp_send_json_success( array( 'titles' => $result['titles'] ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Unexpected response format.', 'oneclickcontent_titles' ) ) );
		}
	}
}
