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
 * Defines the plugin name, version, and two example hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Occ_Titles
 * @subpackage Occ_Titles/admin
 * @author     James Wilson <info@oneclickcontent.com>
 */
class Occ_Titles_Settings {

	/**
	 * Register the plugin settings page.
	 *
	 * @since    1.0.0
	 */
	public function occ_titles_register_options_page() {
		add_options_page(
			__( 'OneClickContent - Titles Settings', 'occ_titles' ),
			__( 'OCC - Titles', 'occ_titles' ),
			'manage_options',
			'occ_titles-settings',
			array( $this, 'occ_titles_options_page' )
		);
	}

	/**
	 * Display the options page.
	 *
	 * @since    1.0.0
	 */
	public function occ_titles_options_page() {
		?>
		<div id="occ_titles" class="wrap">
			<form class="occ_titles-settings-form" method="post" action="options.php">
				<?php settings_fields( 'occ_titles_settings' ); ?>
				<?php do_settings_sections( 'occ_titles_settings' ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register the plugin settings.
	 *
	 * @since    1.0.0
	 */
	public function occ_titles_register_settings() {
		register_setting( 'occ_titles_settings', 'occ_titles_openai_api_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );

		add_settings_section(
			'occ_titles_settings_section',
			__( 'OneClickContent - Titles Settings', 'occ_titles' ),
			array( $this, 'occ_titles_settings_section_callback' ),
			'occ_titles_settings'
		);

		add_settings_field(
			'occ_titles_openai_api_key',
			__( 'OpenAI API Key', 'occ_titles' ),
			array( $this, 'occ_titles_openai_api_key_callback' ),
			'occ_titles_settings',
			'occ_titles_settings_section',
			array( 'label_for' => 'occ_titles_openai_api_key' )
		);

		$api_key = get_option( 'occ_titles_openai_api_key' );

		if ( ! empty( $api_key ) && self::validate_openai_api_key( $api_key ) ) {
			register_setting( 'occ_titles_settings', 'occ_titles_post_types' );
			register_setting( 'occ_titles_settings', 'occ_titles_assistant_id' );

			add_settings_field(
				'occ_titles_post_types',
				__( 'Post Types', 'occ_titles' ),
				array( $this, 'occ_titles_post_types_callback' ),
				'occ_titles_settings',
				'occ_titles_settings_section'
			);

			add_settings_field(
				'occ_titles_assistant_id',
				__( 'Assistant ID', 'occ_titles' ),
				array( $this, 'occ_titles_assistant_id_callback' ),
				'occ_titles_settings',
				'occ_titles_settings_section',
				array( 'label_for' => 'occ_titles_assistant_id' )
			);
		} else {
			add_settings_error(
				'occ_titles_openai_api_key',
				'invalid-api-key',
				sprintf(
					/* translators: %s: URL to OneClickContent - Titles settings page */
					__( 'The OpenAI API key is invalid. Please enter a valid API key in the <a href="%s">OneClickContent - Titles settings</a> to use OneClickContent - Titles.', 'occ_titles' ),
					esc_url( admin_url( 'options-general.php?page=occ_titles-settings' ) )
				),
				'error'
			);
		}
	}

	/**
	 * Callback for the Assistant ID field.
	 *
	 * @since    1.0.0
	 */
	public function occ_titles_assistant_id_callback() {
		$default_assistant_id = 'asst_8zagpq55wZRRpeKpUMeOEWvq';
		$value                = get_option( 'occ_titles_assistant_id', $default_assistant_id );

		if ( $value === $default_assistant_id && get_option( 'occ_titles_assistant_id' ) === false ) {
			update_option( 'occ_titles_assistant_id', $default_assistant_id );
		}

		echo '<input type="text" id="occ_titles_assistant_id" name="occ_titles_assistant_id" value="' . esc_attr( $value ) . '" />';
		echo '<p class="description">' . esc_html__( 'Enter the Assistant ID provided by OpenAI. The default ID is asst_8zagpq55wZRRpeKpUMeOEWvq.', 'occ_titles' ) . '</p>';
	}

	/**
	 * Callback for the post types field.
	 *
	 * @since 1.0.0
	 */
	public function occ_titles_post_types_callback() {
	    $selected_post_types = get_option('occ_titles_post_types', array());

	    if (empty($selected_post_types)) {
	        $selected_post_types = array('post');
	    }

	    // Fetch public post types and exclude the 'attachment' (media) post type
	    $post_types = get_post_types(array('public' => true), 'names', 'and');
	    unset($post_types['attachment']); // Remove the 'attachment' post type

	    echo '<p>' . esc_html__('Select which post types OneClickContent - Titles should be enabled on:', 'occ_titles') . '</p>';
	    echo '<p><em>' . esc_html__('Custom post types must have titles enabled.', 'occ_titles') . '</em></p>';

	    foreach ($post_types as $post_type) {
	        $checked = in_array($post_type, $selected_post_types, true) ? 'checked' : '';
	        $post_type_label = str_replace('_', ' ', ucwords($post_type));
	        echo '<label class="toggle-switch">';
	        echo '<input type="checkbox" name="occ_titles_post_types[]" value="' . esc_attr($post_type) . '" class="occ_titles-settings-checkbox" ' . esc_attr($checked) . '>';
	        echo '<span class="slider"></span>';
	        echo '</label>';
	        echo '<span class="post-type-label">' . esc_html($post_type_label) . '</span><br>';
	    }

	}


	/**
	 * Callback for the settings section.
	 *
	 * @since    1.0.0
	 */
	public function occ_titles_settings_section_callback() {
		echo '<p>' . esc_html__( 'Configure the settings for the OneClickContent - Titles plugin.', 'occ_titles' ) . '</p>';
	}

	/**
	 * Callback for the OpenAI API key field.
	 *
	 * @since    1.0.0
	 */
	public function occ_titles_openai_api_key_callback() {
		$value = get_option( 'occ_titles_openai_api_key', '' );
		echo '<input type="password" name="occ_titles_openai_api_key" value="' . esc_attr( $value ) . '" />';
		echo '<p class="description">' . wp_kses_post( __( 'Get your OpenAI API Key <a href="https://beta.openai.com/signup/">here</a>.', 'occ_titles' ) ) . '</p>';
	}

	/**
	 * Validate the OpenAI API key.
	 *
	 * @since    1.0.0
	 * @param string $api_key The OpenAI API key to validate.
	 * @return bool True if the API key is valid, false otherwise.
	 */
	public static function validate_openai_api_key( $api_key ) {
		$response = wp_remote_get(
			'https://api.openai.com/v1/models',
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		return isset( $data['data'] ) && is_array( $data['data'] );
	}
}
