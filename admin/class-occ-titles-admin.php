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
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_styles() {
		global $pagenow;

		$selected_post_types = get_option( 'occ_titles_post_types', array() );

		// Enqueue styles on the selected post type edit pages.
		if ( ( 'post-new.php' === $pagenow || 'post.php' === $pagenow ) && isset( $_GET['post'] ) ) {
			$post_type = get_post_type( intval( $_GET['post'] ) );
			if ( in_array( $post_type, $selected_post_types, true ) ) {
				wp_enqueue_style(
					$this->plugin_name,
					plugin_dir_url( __FILE__ ) . 'css/occ-titles-admin.css',
					array(),
					$this->version,
					'all'
				);
			}
		} elseif ( 'options-general.php' === $pagenow && isset( $_GET['page'] ) && 'occ_titles-settings' === $_GET['page'] ) {
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
		global $pagenow;

		$selected_post_types = get_option( 'occ_titles_post_types', array() );

		// Enqueue scripts on the selected post type edit pages.
		if ( ( 'post-new.php' === $pagenow || 'post.php' === $pagenow ) && isset( $_GET['post'] ) ) {
			$post_type = get_post_type( intval( $_GET['post'] ) );
			if ( in_array( $post_type, $selected_post_types, true ) ) {
				wp_enqueue_script(
				    'occ_titles_utils',
				    plugin_dir_url( __FILE__ ) . 'js/occ-titles-utils.js',
				    array( 'jquery' ),
				    '1.0.0',
				    true
				);

				wp_enqueue_script(
					$this->plugin_name,
					plugin_dir_url( __FILE__ ) . 'js/occ-titles-admin.js',
					array( 'jquery' ),
					$this->version,
					true
				);
			}
		} elseif ( 'options-general.php' === $pagenow && isset( $_GET['page'] ) && 'occ_titles-settings' === $_GET['page'] ) {
			wp_enqueue_script(
				$this->plugin_name,
				plugin_dir_url( __FILE__ ) . 'js/occ-titles-admin.js',
				array( 'jquery' ),
				$this->version,
				true
			);
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
	 * Add the "Generate Titles" meta box to the post editor.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function occ_titles_add_meta_box() {
	    $selected_post_types = get_option( 'occ_titles_post_types', array() );

	    foreach ( $selected_post_types as $post_type ) {
	        add_meta_box(
	            'occ_titles_meta_box',
	            esc_html__( 'Generate SEO Titles', 'occ_titles' ),
	            array( $this, 'render_meta_box' ),
	            $post_type,
	            'normal',
	            'high'
	        );
	    }
	}


	/**
	 * Render the content of the meta box.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_meta_box() {
		echo '<div id="occ_titles--controls-wrapper" style="margin-bottom: 20px;">';

		// Hidden dropdown for Style selection initially.
		echo '<label for="occ_titles_style" style="margin-right: 10px; display: none;" class="occ_titles_style_label">' . esc_html__( 'Select Style:', 'occ_titles' ) . '</label>';
		echo '<select id="occ_titles_style" name="occ_titles_style" style="display: none;" class="occ_titles_style_dropdown">';
		echo '<option value="" disabled selected>' . esc_html__( 'Choose a Style...', 'occ_titles' ) . '</option>'; // Placeholder option.

		$styles = array(
			'How-To',
			'Listicle',
			'Question',
			'Command',
			'Intriguing Statement',
			'News Headline',
			'Comparison',
			'Benefit-Oriented',
			'Storytelling',
			'Problem-Solution',
		);

		foreach ( $styles as $style ) {
			echo '<option value="' . esc_attr( strtolower( $style ) ) . '">' . esc_html( $style ) . '</option>';
		}

		echo '</select>';

		// Generate Titles Button.
		echo '<button id="occ_titles_button" class="button button-primary">' . esc_html__( 'Generate Titles', 'occ_titles' ) . '</button>';
		echo '</div>';
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
			wp_send_json_error( array( 'message' => __( 'Nonce verification failed.', 'occ_titles' ) ) );
		}

		// Verify the user has the appropriate capability.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'occ_titles' ) ) );
		}

		// Sanitize and get incoming data.
		$content      = isset( $_POST['content'] ) ? sanitize_text_field( wp_unslash( $_POST['content'] ) ) : '';
		$style        = isset( $_POST['style'] ) ? sanitize_text_field( wp_unslash( $_POST['style'] ) ) : '';
		$api_key      = get_option( 'occ_titles_openai_api_key' );
		$assistant_id = get_option( 'occ_titles_assistant_id' );

		// Check for missing data.
		if ( empty( $content ) || empty( $api_key ) || empty( $assistant_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing data.', 'occ_titles' ) ) );
		}

		// Modify the query with the selected style, if provided.
		$query = $content;
		if ( ! empty( $style ) ) {
			$query .= "\n\nStyle: " . ucfirst( $style ); // Append the user-provided style to the query.
		} else {
			$query .= "\n\nStyle: Choose the most suitable style"; // Instruct the Assistant to choose the style.
		}

		// Step 1: Create a new thread.
		$thread_id = $this->openai_helper->create_thread( $api_key );
		if ( ! $thread_id ) {
			wp_send_json_error( array( 'message' => __( 'Failed to create thread.', 'occ_titles' ) ) );
		}

		// Step 2: Add message and run thread.
		$result = $this->openai_helper->add_message_and_run_thread( $api_key, $thread_id, $assistant_id, $query );

		// Check if the result contains the expected data structure.
		if ( isset( $result['titles'] ) && is_array( $result['titles'] ) ) {
			wp_send_json_success( array( 'titles' => $result['titles'] ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Unexpected response format.', 'occ_titles' ) ) );
		}
	}
}
