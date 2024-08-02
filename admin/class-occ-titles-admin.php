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
 */
class Occ_Titles_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string $plugin_name The name of this plugin.
	 * @param    string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name   = $plugin_name;
		$this->version       = $version;
		$this->openai_helper = new Occ_Titles_OpenAI_Helper();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		global $pagenow;

		$selected_post_types = get_option( 'occ_titles_post_types', array() );

		// Enqueue styles on the settings page and selected post type edit pages.
		if ( ( $pagenow === 'post-new.php' || $pagenow === 'post.php' ) && isset( $_GET['post'] ) ) {
			$post_type = get_post_type( intval( $_GET['post'] ) );
			if ( in_array( $post_type, $selected_post_types, true ) ) {
				wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/occ-titles-admin.css', array(), $this->version, 'all' );
			}
		} elseif ( $pagenow === 'options-general.php' && isset( $_GET['page'] ) && $_GET['page'] === 'occ_titles-settings' ) {
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/occ-titles-admin.css', array(), $this->version, 'all' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		global $pagenow;

		$selected_post_types = get_option( 'occ_titles_post_types', array() );

		// Enqueue scripts on the settings page and selected post type edit pages.
		if ( ( $pagenow === 'post-new.php' || $pagenow === 'post.php' ) && isset( $_GET['post'] ) ) {
			$post_type = get_post_type( intval( $_GET['post'] ) );
			if ( in_array( $post_type, $selected_post_types, true ) ) {
				wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/occ-titles-admin.js', array( 'jquery' ), $this->version, true );
			}
		} elseif ( $pagenow === 'options-general.php' && isset( $_GET['page'] ) && $_GET['page'] === 'occ_titles-settings' ) {
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/occ-titles-admin.js', array( 'jquery' ), $this->version, true );
		}

		wp_localize_script(
			$this->plugin_name,
			'occ_titles_admin_vars',
			array(
				'ajax_url'              => admin_url( 'admin-ajax.php' ),
				'occ_titles_ajax_nonce' => wp_create_nonce( 'occ_titles_ajax_nonce' ),
				'selected_post_types'   => $selected_post_types,
				'current_post_type'     => get_post_type(),
			)
		);
	}

	/**
	 * Add the Generate Titles button.
	 *
	 * @since 1.0.0
	 */
	public function occ_titles_add_button() {
		global $pagenow, $post;

		$selected_post_types = get_option( 'occ_titles_post_types', array() );

		// Check if the current page is the post editor and if the post type is selected.
		if ( ( $pagenow === 'post-new.php' || $pagenow === 'post.php' ) && isset( $post ) ) {
			if ( in_array( $post->post_type, $selected_post_types, true ) ) {
				echo '<div id="occ_titles--button-wrapper">
					<button id="occ_titles_button" class="button button-primary">Generate Titles</button>
				</div>';
			}
		}
	}

	/**
	 * Generate titles using OpenAI.
	 *
	 * @since 1.0.0
	 */
	/**
	 * Generate titles using OpenAI.
	 *
	 * @since 1.0.0
	 */
	public function generate_titles() {
	    // Log that the AJAX request was received
	    error_log('AJAX request received.');

	    // Check nonce for security
	    if (!check_ajax_referer('occ_titles_ajax_nonce', 'nonce', false)) {
	        error_log('Nonce verification failed.');
	        wp_send_json_error(array('message' => 'Nonce verification failed.'));
	    }
	    error_log('Nonce verification passed.');

	    // Verify the user has the appropriate capability
	    if (!current_user_can('edit_posts')) {
	        error_log('Permission denied.');
	        wp_send_json_error(array('message' => 'Permission denied.'));
	    }
	    error_log('User capability verified.');

	    // Sanitize and get incoming data
	    $content = isset($_POST['content']) ? sanitize_text_field(wp_unslash($_POST['content'])) : '';
	    $style = isset($_POST['style']) ? sanitize_text_field(wp_unslash($_POST['style'])) : 'default';
	    $api_key = get_option('occ_titles_openai_api_key');
	    $assistant_id = get_option('occ_titles_assistant_id');

	    // Log the data being sent to the assistant
	    error_log('Content: ' . $content);
	    error_log('Style: ' . $style);
	    error_log('API Key: ' . ($api_key ? 'Exists' : 'Missing'));
	    error_log('Assistant ID: ' . ($assistant_id ? 'Exists' : 'Missing'));

	    // Check for missing data
	    if (empty($content) || empty($api_key) || empty($assistant_id)) {
	        error_log('Missing data.');
	        wp_send_json_error(array('message' => 'Missing data.'));
	    }

	    // Step 1: Create a new thread
	    $thread_id = $this->openai_helper->create_thread($api_key);
	    if (!$thread_id) {
	        error_log('Failed to create thread.');
	        wp_send_json_error(array('message' => 'Failed to create thread.'));
	    }
	    error_log('Thread ID: ' . $thread_id);

	    // Modify the query with the selected style
	    $query = $content . "\n\nStyle: " . ucfirst($style);

	    // Step 2: Add message and run thread
	    $result = $this->openai_helper->add_message_and_run_thread($api_key, $thread_id, $assistant_id, $query);
	    if (is_string($result)) {
	        error_log('Add message and run thread error: ' . $result);
	        wp_send_json_error(array('message' => $result));
	    } else {
	        // Log the response to inspect it
	        error_log('Response from OpenAI: ' . print_r($result, true));

	        // Ensure 'titles' is in the response
	        if (isset($result['titles'])) {
	            wp_send_json_success($result);
	        } else {
	            error_log('Unexpected response format.');
	            wp_send_json_error(array('message' => 'Unexpected response format.'));
	        }
	    }
	}




}
