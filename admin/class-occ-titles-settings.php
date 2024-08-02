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
 * Class Occ_Titles_Settings
 *
 * Handles the settings and assistant creation for OneClickContent - Titles.
 */
class Occ_Titles_Settings {

	/**
	 * Registers the settings page under the options menu.
	 *
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
	 * @return void
	 */
	public function occ_titles_options_page() {
		?>
		<div id="occ_titles" class="wrap">
			<form class="occ_titles-settings-form" method="post" action="options.php">
				<?php
				settings_fields( 'occ_titles_settings' );
				do_settings_sections( 'occ_titles_settings' );
				submit_button();
				?>
			</form>

			<!-- Manual Assistant Creation Button -->
			<form method="post" action="">
				<?php wp_nonce_field( 'occ_titles_create_assistant_action', 'occ_titles_create_assistant_nonce' ); ?>
				<input type="submit" name="occ_titles_create_assistant" id="occ_titles_create_assistant" class="button button-primary" value="<?php esc_attr_e( 'Create Assistant', 'occ_titles' ); ?>">
			</form>
		</div>
		<?php
	}

	/**
	 * Registers the settings and their fields.
	 *
	 * @return void
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
		} else {
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
	 * @return void
	 */
	public function occ_titles_assistant_id_callback() {
		$value = get_option( 'occ_titles_assistant_id', '' );

		if ( empty( $value ) ) {
			$value = esc_html__( 'Assistant not created yet', 'occ_titles' );
		}

		echo '<input type="text" id="occ_titles_assistant_id" name="occ_titles_assistant_id" value="' . esc_attr( $value ) . '" />';
		echo '<p class="description">' . esc_html__( 'Enter the Assistant ID provided by OpenAI or leave as is to use the auto-generated one.', 'occ_titles' ) . '</p>';
	}

	/**
	 * Callback function for the Post Types setting field.
	 *
	 * @return void
	 */
	public function occ_titles_post_types_callback() {
		$selected_post_types = get_option( 'occ_titles_post_types', array( 'post' ) );
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
	 * @return void
	 */
	public function occ_titles_settings_section_callback() {
		echo '<p>' . esc_html__( 'Configure the settings for the OneClickContent - Titles plugin.', 'occ_titles' ) . '</p>';
	}

	/**
	 * Callback function for the OpenAI API Key setting field.
	 *
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
	 * Creates an assistant using OpenAI's API.
	 *
	 * @return string|bool The Assistant ID if successful, false otherwise.
	 */
	private function occ_titles_create_assistant() {
		$api_key = get_option( 'occ_titles_openai_api_key' );

		if ( empty( $api_key ) ) {
			return false;
		}

		$initial_prompt = array(
			'description' => "You are an SEO expert and content writer. Your task is to generate five SEO-optimized titles for a given text. Each title should be engaging, include relevant keywords, and be between 50-60 characters long. Additionally, analyze the sentiment of each title and include it in the response. The sentiment can be 'Positive', 'Negative', or 'Neutral'. Generate the titles based on the text provided, using different styles. The styles you can use are: How-To, Listicle, Question, Command, Intriguing Statement, News Headline, Comparison, Benefit-Oriented, Storytelling, and Problem-Solution. Also, identify and include relevant keywords used in the titles. Always use the `generate_5_titles_with_styles_and_keywords` function to create and return the titles. The response must be in a JSON format.",
			'behavior'    => array(
				array(
					'trigger'     => 'message',
					'instruction' => "When provided with a message containing the content of an article, you must call the `generate_5_titles_with_styles_and_keywords` function. This function will generate five SEO-optimized titles. Each title must include relevant keywords, sentiment analysis ('Positive', 'Negative', or 'Neutral'), and a different style from the following: How-To, Listicle, Question, Command, Intriguing Statement, News Headline, Comparison, Benefit-Oriented, Storytelling, and Problem-Solution. The expected JSON format is:\n[\n  { \"index\": 1, \"text\": \"Title 1 content\", \"style\": \"Style\", \"sentiment\": \"Sentiment\", \"keywords\": [\"keyword1\", \"keyword2\"] },\n  { \"index\": 2, \"text\": \"Title 2 content\", \"style\": \"Style\", \"sentiment\": \"Sentiment\", \"keywords\": [\"keyword1\", \"keyword2\"] },\n  { \"index\": 3, \"text\": \"Title 3 content\", \"style\": \"Style\", \"sentiment\": \"Sentiment\", \"keywords\": [\"keyword1\", \"keyword2\"] },\n  { \"index\": 4, \"text\": \"Title 4 content\", \"style\": \"Style\", \"sentiment\": \"Sentiment\", \"keywords\": [\"keyword1\", \"keyword2\"] },\n  { \"index\": 5, \"text\": \"Title 5 content\", \"style\": \"Style\", \"sentiment\": \"Sentiment\", \"keywords\": [\"keyword1\", \"keyword2\"] }\n]. Ensure the response is in this exact format.",
				),
			),
		);

		$function_definition = array(
			'name'        => 'generate_5_titles_with_styles_and_keywords',
			'description' => esc_html__( 'Generate five titles that are search engine optimized for length and copy from the provided article content, including sentiment analysis, style, and relevant keywords, and return them in a specific JSON format.', 'occ_titles' ),
			'parameters'  => array(
				'type'       => 'object',
				'properties' => array(
					'titles' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'index'     => array(
									'type'        => 'integer',
									'description' => esc_html__( 'The index of the title.', 'occ_titles' ),
								),
								'text'      => array(
									'type'        => 'string',
									'description' => esc_html__( 'The content of the title.', 'occ_titles' ),
								),
								'style'     => array(
									'type'        => 'string',
									'description' => esc_html__( 'The style of the title.', 'occ_titles' ),
								),
								'sentiment' => array(
									'type'        => 'string',
									'description' => esc_html__( 'The sentiment of the title.', 'occ_titles' ),
								),
								'keywords'  => array(
									'type'        => 'array',
									'items'       => array(
										'type' => 'string',
									),
									'description' => esc_html__( 'A list of relevant keywords used in the title.', 'occ_titles' ),
								),
							),
							'required'   => array( 'index', 'text', 'style', 'sentiment', 'keywords' ),
						),
					),
				),
				'required'   => array( 'titles' ),
			),
		);

		$payload = array(
			'description'     => esc_html__( 'Assistant for generating SEO-optimized titles.', 'occ_titles' ),
			'instructions'    => wp_json_encode( $initial_prompt ),
			'name'            => esc_html__( 'OneClickContent - Titles Assistant', 'occ_titles' ),
			'tools'           => array(
				array(
					'type'     => 'function',
					'function' => $function_definition,
				),
			),
			'model'           => 'gpt-4o',
			'response_format' => array( 'type' => 'json_object' ),
		);

		$response = wp_remote_post(
			'https://api.openai.com/v1/assistants',
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
					'OpenAI-Beta'   => 'assistants=v2',
				),
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
		    error_log( 'Assistant creation error: ' . $response->get_error_message() ); // Debugging line
		    return false;
		}

		$response_body  = wp_remote_retrieve_body( $response );
		$assistant_data = json_decode( $response_body, true );

		if ( isset( $assistant_data['id'] ) ) {
		    return $assistant_data['id'];
		} else {
		    error_log( 'Failed to create assistant, response: ' . print_r( $assistant_data, true ) ); // Debugging line
		    return false;
		}
	}

	/**
	 * Handles the manual assistant creation process.
	 *
	 * @return void
	 */
	public function occ_titles_handle_assistant_creation() {
		if ( isset( $_POST['occ_titles_create_assistant'] ) && check_admin_referer( 'occ_titles_create_assistant_action', 'occ_titles_create_assistant_nonce' ) ) {
			$assistant_id = $this->occ_titles_create_assistant();

			if ( $assistant_id ) {
				update_option( 'occ_titles_assistant_id', $assistant_id );
				add_settings_error( 'occ_titles_assistant_id', 'assistant-created', __( 'Assistant successfully created.', 'occ_titles' ), 'updated' );
			} else {
				add_settings_error( 'occ_titles_assistant_id', 'assistant-creation-failed', __( 'Failed to create assistant.', 'occ_titles' ), 'error' );
			}
		}
	}
}
