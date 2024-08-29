<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Handles the settings and assistant creation for OneClickContent - Titles.
 *
 * @link       https://oneclickcontent.com
 * @since      1.0.0
 *
 * @package    Occ_Titles
 * @subpackage Occ_Titles/admin
 */

/**
 * Class Occ_Titles_Settings
 *
 * Manages the settings page and assistant creation for the OneClickContent - Titles plugin.
 */
class Occ_Titles_Settings {

	/**
	 * Registers the settings page under the options menu.
	 *
	 * @since 1.0.0
	 * @return void
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
	 * Outputs the settings page content.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function occ_titles_options_page() {
		?>
		<div id="occ_titles" class="wrap">
			<form class="occ_titles-settings-form" method="post" action="options.php">
				<?php
				settings_fields( 'occ_titles_settings' );
				do_settings_sections( 'occ_titles_settings' );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Registers the settings and their fields.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function occ_titles_register_settings() {
		register_setting(
			'occ_titles_settings',
			'occ_titles_openai_api_key',
			array( 'sanitize_callback' => 'sanitize_text_field' )
		);

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

		// Only register additional settings if API key is valid.
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
		} elseif ( ! get_settings_errors( 'invalid-api-key' ) ) {
				add_settings_error(
					'occ_titles_openai_api_key',
					'invalid-api-key',
					__( 'The OpenAI API key is invalid. Please enter a valid API key in the OneClickContent - Titles settings to use OneClickContent - Titles.', 'occ_titles' ) . ' ' .
					'<a href="' . esc_url( admin_url( 'options-general.php?page=occ_titles-settings' ) ) . '">' . __( 'Settings', 'occ_titles' ) . '</a>',
					'error'
				);
		}
	}

	/**
	 * Callback function for the Assistant ID setting field.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function occ_titles_assistant_id_callback() {
	    $assistant_id = get_option('occ_titles_assistant_id', '');

	    // Validate existing Assistant ID
	    if (!empty($assistant_id) && !$this->validate_assistant_id($assistant_id)) {
	        // Invalid Assistant ID, try to create a new one
	        $assistant_id = $this->occ_titles_create_assistant();
	        if ($assistant_id) {
	            update_option('occ_titles_assistant_id', $assistant_id);
	            add_settings_error(
	                'occ_titles_assistant_id',
	                'assistant-created',
	                __('A new assistant was created because the existing one was invalid.', 'occ_titles'),
	                'updated'
	            );
	        } else {
	            add_settings_error(
	                'occ_titles_assistant_id',
	                'assistant-creation-failed',
	                __('Failed to create a new assistant.', 'occ_titles'),
	                'error'
	            );
	        }
	    }

	    echo '<input type="text" id="occ_titles_assistant_id" name="occ_titles_assistant_id" value="' . esc_attr($assistant_id) . '" />';
	    echo '<p class="description">' . esc_html__('Enter the Assistant ID provided by OpenAI or leave as is to use the auto-generated one.', 'occ_titles') . '</p>';
	}


	/**
	 * Validates the Assistant ID by checking its existence in OpenAI.
	 *
	 * @since 1.0.0
	 * @param string $assistant_id The Assistant ID to validate.
	 * @return bool True if the Assistant ID is valid, false otherwise.
	 */
	private function validate_assistant_id($assistant_id) {
	    $api_key = get_option('occ_titles_openai_api_key');

	    if (empty($api_key) || empty($assistant_id)) {
	        return false;
	    }

	    $response = wp_remote_get(
	        'https://api.openai.com/v1/assistants/' . $assistant_id,
	        array(
	            'headers' => array(
	                'Content-Type'  => 'application/json',
	                'Authorization' => 'Bearer ' . $api_key,
	                'OpenAI-Beta'   => 'assistants=v2',
	            ),
	        )
	    );

	    if (is_wp_error($response)) {
	        return false;
	    }

	    $body = wp_remote_retrieve_body($response);
	    $data = json_decode($body, true);

	    // If the assistant exists, it should return data; otherwise, return false
	    return isset($data['id']) && $data['id'] === $assistant_id;
	}


	/**
	 * Callback function for the Post Types setting field.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function occ_titles_post_types_callback() {
		// Ensure $selected_post_types is always an array
		$selected_post_types = (array) get_option( 'occ_titles_post_types', array( 'post' ) );
		$post_types          = get_post_types( array( 'public' => true ), 'names', 'and' );
		unset( $post_types['attachment'] );

		echo '<p>' . esc_html__( 'Select which post types OneClickContent - Titles should be enabled on:', 'occ_titles' ) . '</p>';
		echo '<p><em>' . esc_html__( 'Custom post types must have titles enabled.', 'occ_titles' ) . '</em></p>';

		foreach ( $post_types as $post_type ) {
			$checked         = in_array( $post_type, $selected_post_types, true ) ? 'checked' : '';
			$post_type_label = str_replace( '_', ' ', ucwords( $post_type ) );
			echo '<label class="toggle-switch">';
			echo '<input type="checkbox" name="occ_titles_post_types[]" value="' . esc_attr( $post_type ) . '" class="occ_titles-settings-checkbox" ' . esc_attr( $checked ) . '>';
			echo '<span class="slider"></span>';
			echo '</label>';
			echo '<span class="post-type-label">' . esc_html( $post_type_label ) . '</span><br>';
		}
	}

	/**
	 * Callback function for the settings section description.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function occ_titles_settings_section_callback() {
		echo '<p>' . esc_html__( 'Configure the settings for the OneClickContent - Titles plugin.', 'occ_titles' ) . '</p>';
	}

	/**
	 * Callback function for the OpenAI API Key setting field.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function occ_titles_openai_api_key_callback() {
		$value = get_option( 'occ_titles_openai_api_key', '' );
		echo '<input type="password" name="occ_titles_openai_api_key" value="' . esc_attr( $value ) . '" />';
		echo '<p class="description">' . wp_kses_post( __( 'Get your OpenAI API Key <a href="https://beta.openai.com/signup/">here</a>.', 'occ_titles' ) ) . '</p>';
	}

	/**
	 * Validates the OpenAI API key.
	 *
	 * @since 1.0.0
	 * @param string $api_key The API key to validate.
	 * @return bool True if valid, false otherwise.
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

	/**
	 * Creates an assistant using OpenAI's API with Function Calling.
	 *
	 * @since 1.0.0
	 * @return string|bool The Assistant ID if successful, false otherwise.
	 */
	public function occ_titles_create_assistant() {
	    $api_key = get_option('occ_titles_openai_api_key');

	    if (empty($api_key)) {
	        return false;
	    }

	    // Initial prompt with dynamic style determination or user-selected style
	    $initial_prompt = array(
	        'description' => esc_html__('You are an SEO expert and content writer. Your task is to generate five SEO-optimized titles for a given text. Each title should be engaging, include relevant keywords, and be between 50-60 characters long. Additionally, analyze the sentiment of each title and include it in the response. If a style is provided, use it for all titles; otherwise, choose the most suitable style from the following options: How-To, Listicle, Question, Command, Intriguing Statement, News Headline, Comparison, Benefit-Oriented, Storytelling, and Problem-Solution. The response must adhere to the provided JSON Schema.', 'occ_titles'),
	        'behavior' => array(
	            array(
	                'trigger' => 'message',
	                'instruction' => esc_html__("When provided with a message containing the content of an article, and an optional style parameter, you must call the `generate_5_titles_with_styles_and_keywords` function. This function will generate exactly five SEO-optimized titles. Each title must include relevant keywords, sentiment analysis ('Positive', 'Negative', or 'Neutral'), and either the provided style or a chosen style. The expected JSON format is:\n[\n  { \"index\": 1, \"text\": \"Title 1 content\", \"style\": \"Style\", \"sentiment\": \"Sentiment\", \"keywords\": [\"keyword1\", \"keyword2\"] },\n  { \"index\": 2, \"text\": \"Title 2 content\", \"style\": \"Style\", \"sentiment\": \"Sentiment\", \"keywords\": [\"keyword1\", \"keyword2\"] },\n  { \"index\": 3, \"text\": \"Title 3 content\", \"style\": \"Style\", \"sentiment\": \"Sentiment\", \"keywords\": [\"keyword1\", \"keyword2\"] },\n  { \"index\": 4, \"text\": \"Title 4 content\", \"style\": \"Style\", \"sentiment\": \"Sentiment\", \"keywords\": [\"keyword1\", \"keyword2\"] },\n  { \"index\": 5, \"text\": \"Title 5 content\", \"style\": \"Style\", \"sentiment\": \"Sentiment\", \"keywords\": [\"keyword1\", \"keyword2\"] }\n]. Ensure the response is in this exact format.", 'occ_titles'),
	            ),
	        ),
	    );

	    $function_definition = array(
	        'name' => 'generate_5_titles_with_styles_and_keywords',
	        'description' => esc_html__('Generate five titles that are search engine optimized for length and copy from the provided article content, including sentiment analysis, style, and relevant keywords, and return them in a specific JSON format.', 'occ_titles'),
	        'parameters' => array(
	            'type' => 'object',
	            'properties' => array(
	                'content' => array(
	                    'type' => 'string',
	                    'description' => esc_html__('The content of the article for which titles are being generated.', 'occ_titles'),
	                ),
	                'style' => array(
	                    'type' => 'string',
	                    'description' => esc_html__('The style of the titles to be generated. If not provided, the assistant will choose the most suitable style.', 'occ_titles'),
	                    'enum' => array(
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
	                    ),
	                ),
	            ),
	            'required' => array('content'), // 'style' is optional
	        ),
	    );

	    $payload = array(
	        'description' => esc_html__('Assistant for generating SEO-optimized titles.', 'occ_titles'),
	        'instructions' => wp_json_encode($initial_prompt),
	        'name' => esc_html__('OneClickContent - Titles Assistant', 'occ_titles'),
	        'tools' => array(
	            array(
	                'type' => 'function',
	                'function' => $function_definition,
	            ),
	        ),
	        'model' => 'gpt-4o',
	        'response_format' => array('type' => 'json_object'),
	    );

	    $response = wp_remote_post(
	        'https://api.openai.com/v1/assistants',
	        array(
	            'headers' => array(
	                'Content-Type' => 'application/json',
	                'Authorization' => 'Bearer ' . $api_key,
	                'OpenAI-Beta' => 'assistants=v2',
	            ),
	            'body' => wp_json_encode($payload),
	        )
	    );

	    if (is_wp_error($response)) {
	        error_log('Assistant creation error: ' . $response->get_error_message()); // Debugging line.
	        return false;
	    }

	    $response_body = wp_remote_retrieve_body($response);
	    $assistant_data = json_decode($response_body, true);

	    if (isset($assistant_data['id'])) {
	        return $assistant_data['id'];
	    } else {
	        error_log('Failed to create assistant, response: ' . print_r($assistant_data, true)); // Debugging line.
	        return false;
	    }
	}






	/**
	 * Handles the manual assistant creation process.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function occ_titles_handle_assistant_creation() {
		if ( isset( $_POST['occ_titles_create_assistant'] ) && check_admin_referer( 'occ_titles_create_assistant_action', 'occ_titles_create_assistant_nonce' ) ) {

			// Debugging: Log that the assistant creation process started.
			error_log( 'occ_titles_handle_assistant_creation triggered.' );

			// Check if an assistant ID already exists.
			$existing_assistant_id = get_option( 'occ_titles_assistant_id', '' );

			if ( empty( $existing_assistant_id ) ) {
				$assistant_id = $this->occ_titles_create_assistant();

				if ( $assistant_id ) {
					update_option( 'occ_titles_assistant_id', $assistant_id );
					add_settings_error( 'occ_titles_assistant_id', 'assistant-created', __( 'Assistant successfully created.', 'occ_titles' ), 'updated' );
				} else {
					add_settings_error( 'occ_titles_assistant_id', 'assistant-creation-failed', __( 'Failed to create assistant.', 'occ_titles' ), 'error' );
				}
			} else {
				// Assistant already exists, log a message or handle accordingly.
				error_log( 'Assistant ID already exists: ' . $existing_assistant_id );
			}
		}
	}

	/**
	 * Auto-saves the settings via AJAX.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function occ_titles_auto_save() {
		// Verify the nonce for security.
		if ( ! check_ajax_referer( 'occ_titles_ajax_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce', 'occ_titles' ) ) );
		}

		// Sanitize and update the option.
		$field_name  = sanitize_text_field( $_POST['field_name'] );
		$field_value = $_POST['field_value'];

		if ( is_array( $field_value ) ) {
			$field_value = array_map( 'sanitize_text_field', $field_value );
		} else {
			$field_value = sanitize_text_field( $field_value );
		}

		update_option( $field_name, $field_value );

		// Send success response.
		wp_send_json_success();
	}
}
