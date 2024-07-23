<?php

/**
 * The admin-specific functionality of the plugin.
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
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Occ_Titles
 * @subpackage Occ_Titles/admin
 * @author     James Wilson <info@oneclickcontent.com>
 */
class Occ_Titles_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name   = $plugin_name;
		$this->version       = $version;
		$this->openai_helper = new OpenAI_Helper();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/occ-titles-admin.css', array(), $this->version, 'all' );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/occ-titles-admin.js', array( 'jquery', 'wp-data', 'wp-editor' ), $this->version, true );

		// Localize the script to pass data from PHP to JavaScript
		wp_localize_script(
			$this->plugin_name,
			'occ_titles_admin_vars',
			array(
				'ajax_url'              => admin_url( 'admin-ajax.php' ),
				'occ_titles_ajax_nonce' => wp_create_nonce( 'occ_titles_ajax_nonce' ),
			)
		);
	}

	public function occ_titles_add_button() {
		global $pagenow, $post;

		if ( ( $pagenow === 'post-new.php' || $pagenow === 'post.php' ) && isset( $post ) ) {
			echo '<div id="occ_titles--button-wrapper">
	                <button id="occ_titles_button" class="button button-primary">Generate Titles</button>
	              </div>';
		}
	}

	/**
	 * Generate titles using OpenAI.
	 *
	 * This function handles the AJAX request to generate SEO-optimized titles
	 * using the OpenAI API.
	 *
	 * @since    1.0.0
	 */
	public function generate_titles() {
		// Check nonce for security
		check_ajax_referer( 'occ_titles_ajax_nonce', 'nonce' );

		// Verify the user has the appropriate capability
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ) );
		}

		// Sanitize incoming data
		$content      = isset( $_POST['content'] ) ? sanitize_text_field( wp_unslash( $_POST['content'] ) ) : '';
		$api_key      = get_option( 'occ_titles_openai_api_key' );
		$assistant_id = get_option( 'occ_titles_assistant_id' );

		// Check for missing data
		if ( empty( $content ) || empty( $api_key ) || empty( $assistant_id ) ) {
			wp_send_json_error( array( 'message' => 'Missing data.' ) );
		}

		// Step 1: Create a new thread
		$thread_id = $this->openai_helper->create_thread( $api_key );
		if ( ! $thread_id ) {
			wp_send_json_error( array( 'message' => 'Failed to create thread.' ) );
		}

		// Step 2: Add message and run thread
		$result = $this->openai_helper->add_message_and_run_thread( $api_key, $thread_id, $assistant_id, $content );
		if ( is_string( $result ) ) {
			wp_send_json_error( array( 'message' => $result ) );
		} else {
			wp_send_json_success( $result );
		}
	}
}
