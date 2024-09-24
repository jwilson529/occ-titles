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
			__( 'OneClickContent - Titles Settings', 'oneclickcontent_titles' ),
			__( 'OCC - Titles', 'oneclickcontent_titles' ),
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
			__( 'OneClickContent - Titles Settings', 'oneclickcontent-titles' ),
			array( $this, 'occ_titles_settings_section_callback' ),
			'occ_titles_settings'
		);

		add_settings_field(
			'occ_titles_openai_api_key',
			__( 'OpenAI API Key', 'oneclickcontent-titles' ),
			array( $this, 'occ_titles_openai_api_key_callback' ),
			'occ_titles_settings',
			'occ_titles_settings_section',
			array( 'label_for' => 'occ_titles_openai_api_key' )
		);

		$api_key = get_option( 'occ_titles_openai_api_key' );

		// Only register additional settings if the API key is valid.
		if ( ! empty( $api_key ) && self::validate_openai_api_key( $api_key ) ) {
			register_setting( 'occ_titles_settings', 'occ_titles_post_types', array( 'sanitize_callback' => 'sanitize_text_field' ) );
			register_setting( 'occ_titles_settings', 'occ_titles_assistant_id', array( 'sanitize_callback' => 'sanitize_text_field' ) );
			register_setting( 'occ_titles_settings', 'occ_titles_openai_model', array( 'sanitize_callback' => 'sanitize_text_field' ) );

			add_settings_field(
				'occ_titles_post_types',
				__( 'Post Types', 'oneclickcontent-titles' ),
				array( $this, 'occ_titles_post_types_callback' ),
				'occ_titles_settings',
				'occ_titles_settings_section'
			);

			add_settings_field(
				'occ_titles_assistant_id',
				__( 'Assistant ID', 'oneclickcontent-titles' ),
				array( $this, 'occ_titles_assistant_id_callback' ),
				'occ_titles_settings',
				'occ_titles_settings_section',
				array( 'label_for' => 'occ_titles_assistant_id' )
			);

			add_settings_field(
				'occ_titles_openai_model',
				__( 'OpenAI Model', 'oneclickcontent-titles' ),
				array( $this, 'occ_titles_openai_model_callback' ),
				'occ_titles_settings',
				'occ_titles_settings_section',
				array( 'label_for' => 'occ_titles_openai_model' )
			);

		} elseif ( ! get_settings_errors( 'invalid-api-key' ) ) {
			add_settings_error(
				'occ_titles_openai_api_key',
				'invalid-api-key',
				__( 'The OpenAI API key is invalid. Please enter a valid API key in the OneClickContent - Titles settings to use OneClickContent - Titles.', 'oneclickcontent-titles' ) .
				' <a href="' . esc_url( admin_url( 'options-general.php?page=occ_titles-settings' ) ) . '">' . __( 'Settings', 'oneclickcontent-titles' ) . '</a>',
				'error'
			);
		}
	}


	/**
	 * Callback function for the OpenAI Model setting field.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function occ_titles_openai_model_callback() {
		$selected_model = get_option( 'occ_titles_openai_model', '' );
		$api_key        = get_option( 'occ_titles_openai_api_key' );

		if ( ! empty( $api_key ) ) {
			$models = self::validate_openai_api_key( $api_key );

			if ( $models && is_array( $models ) ) {
				echo '<select id="occ_titles_openai_model" name="occ_titles_openai_model">';
				foreach ( $models as $model ) {
					echo '<option value="' . esc_attr( $model ) . '"' . selected( $selected_model, $model, false ) . '>' . esc_html( $model ) . '</option>';
				}
				echo '</select>';
				echo '<p class="description">' . esc_html__( 'Select the OpenAI model to use for the assistant.', 'oneclickcontent_titles' ) . '</p>';
			} else {
				echo '<p class="error">' . esc_html__( 'Unable to retrieve models. Please check your API key.', 'oneclickcontent_titles' ) . '</p>';
			}
		} else {
			echo '<p class="error">' . esc_html__( 'Please enter a valid OpenAI API key first.', 'oneclickcontent_titles' ) . '</p>';
		}
	}

	/**
	 * Callback function for the Assistant ID setting field.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function occ_titles_assistant_id_callback() {
		$assistant_id = get_option( 'occ_titles_assistant_id', '' );

		// Validate existing Assistant ID.
		if ( ! empty( $assistant_id ) && ! $this->validate_assistant_id( $assistant_id ) ) {
			// Invalid Assistant ID, try to create a new one.
			$assistant_id = $this->occ_titles_create_assistant();
			if ( $assistant_id ) {
				update_option( 'occ_titles_assistant_id', $assistant_id );
				add_settings_error(
					'occ_titles_assistant_id',
					'assistant-created',
					__( 'A new assistant was created because the existing one was invalid.', 'oneclickcontent_titles' ),
					'updated'
				);
			} else {
				add_settings_error(
					'occ_titles_assistant_id',
					'assistant-creation-failed',
					__( 'Failed to create a new assistant.', 'oneclickcontent_titles' ),
					'error'
				);
			}
		}

		echo '<input type="text" id="occ_titles_assistant_id" name="occ_titles_assistant_id" value="' . esc_attr( $assistant_id ) . '" />';
		echo '<p class="description">' . esc_html__( 'Enter the Assistant ID provided by OpenAI or leave as is to use the auto-generated one.', 'oneclickcontent_titles' ) . '</p>';
	}

	/**
	 * Validates the Assistant ID by checking its existence in OpenAI.
	 *
	 * @since 1.0.0
	 * @param string $assistant_id The Assistant ID to validate.
	 * @return bool True if the Assistant ID is valid, false otherwise.
	 */
	private function validate_assistant_id( $assistant_id ) {
		$api_key = get_option( 'occ_titles_openai_api_key' );

		if ( empty( $api_key ) || empty( $assistant_id ) ) {
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

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// If the assistant exists, it should return data; otherwise, return false.
		return isset( $data['id'] ) && $data['id'] === $assistant_id;
	}

	/**
	 * Callback function for the Post Types setting field.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function occ_titles_post_types_callback() {
		$selected_post_types = (array) get_option( 'occ_titles_post_types', array( 'post' ) );
		$post_types          = get_post_types( array( 'public' => true ), 'names', 'and' );
		unset( $post_types['attachment'] );

		echo '<p>' . esc_html__( 'Select which post types OneClickContent - Titles should be enabled on:', 'oneclickcontent_titles' ) . '</p>';
		echo '<p><em>' . esc_html__( 'Custom post types must have titles enabled.', 'oneclickcontent_titles' ) . '</em></p>';

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
		echo '<p>' . esc_html__( 'Configure the settings for the OneClickContent - Titles plugin.', 'oneclickcontent_titles' ) . '</p>';
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
		echo '<p class="description">' . wp_kses_post( __( 'Get your OpenAI API Key <a href="https://beta.openai.com/signup/">here</a>.', 'oneclickcontent_titles' ) ) . '</p>';
	}


	/**
	 * Validates the OpenAI API key and fetches models that support function calling.
	 *
	 * @since 1.0.0
	 * @param string $api_key The API key to validate.
	 * @return array|bool List of models if successful, false otherwise.
	 */
	public static function validate_openai_api_key( $api_key ) {
		// Make sure the API key is not empty.
		if ( empty( $api_key ) ) {
			return false;
		}

		// Send a request to the OpenAI API to fetch the models.
		$response = wp_remote_get(
			'https://api.openai.com/v1/models',
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
				),
			)
		);

		// Check if the request resulted in an error.
		if ( is_wp_error( $response ) ) {
			return false;
		}

		// Retrieve and decode the response body.
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Define the timestamps for June 13, 2023, and November 6, 2023.
		$function_calling_cutoff          = 1686614400;
		$parallel_function_calling_cutoff = 1699228800;

		// Check if the data contains an array of models.
		if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
			// Filter models that support function calling based on creation date.
			$models = array_filter(
				$data['data'],
				function ( $model ) use ( $function_calling_cutoff, $parallel_function_calling_cutoff ) {
					// Check for models that support function calling or parallel function calling.
					return isset( $model['created'] ) && (
						$model['created'] >= $function_calling_cutoff ||
						$model['created'] >= $parallel_function_calling_cutoff
					);
				}
			);

			// Return an array of model IDs.
			return array_map(
				function ( $model ) {
					return $model['id'];
				},
				$models
			);
		}

		// Return false if validation fails or no models are found.
		return false;
	}


	/**
	 * AJAX handler for validating the OpenAI API key.
	 *
	 * @since 1.0.0
	 */
	public function occ_titles_ajax_validate_openai_api_key() {
		check_ajax_referer( 'occ_titles_ajax_nonce', 'nonce' );

		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';

		$models = self::validate_openai_api_key( $api_key );

		if ( $models ) {
			wp_send_json_success(
				array(
					'message' => __( 'API key is valid.', 'oneclickcontent_titles' ),
					'models'  => $models,
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Invalid API key.', 'oneclickcontent_titles' ) ) );
		}
	}

	/**
	 * Creates an assistant using OpenAI's API with Function Calling.
	 *
	 * @since 1.0.0
	 * @return string|bool The Assistant ID if successful, false otherwise.
	 */
	public function occ_titles_create_assistant() {
		$api_key = get_option( 'occ_titles_openai_api_key' );

		if ( empty( $api_key ) ) {
			return false;
		}

		// Initial prompt with dynamic style determination or user-selected style.
		$initial_prompt = array(
			'description' => esc_html__( 'You are an SEO expert and content writer. Your task is to generate five SEO-optimized titles for a given text. Each title should be engaging, include relevant keywords, and be between 50-60 characters long. Additionally, analyze the sentiment of each title and include it in the response. If a style is provided, use it for all titles; otherwise, choose the most suitable style from the following options: How-To, Listicle, Question, Command, Intriguing Statement, News Headline, Comparison, Benefit-Oriented, Storytelling, and Problem-Solution. The response must adhere to the provided JSON Schema.', 'oneclickcontent_titles' ),
			'behavior'    => array(
				array(
					'trigger'     => 'message',
					'instruction' => esc_html__( "When provided with a message containing the content of an article, and an optional style parameter, you must call the `generate_5_titles_with_styles_and_keywords` function. This function will generate exactly five SEO-optimized titles. Each title must include relevant keywords, sentiment analysis ('Positive', 'Negative', or 'Neutral'), and either the provided style or a chosen style. The expected JSON format is:\n[\n  { \"index\": 1, \"text\": \"Title 1 content\", \"style\": \"Style\", \"sentiment\": \"Sentiment\", \"keywords\": [\"keyword1\", \"keyword2\"] },\n  { \"index\": 2, \"text\": \"Title 2 content\", \"style\": \"Style\", \"sentiment\": \"Sentiment\", \"keywords\": [\"keyword1\", \"keyword2\"] },\n  { \"index\": 3, \"text\": \"Title 3 content\", \"style\": \"Style\", \"sentiment\": \"Sentiment\", \"keywords\": [\"keyword1\", \"keyword2\"] },\n  { \"index\": 4, \"text\": \"Title 4 content\", \"style\": \"Style\", \"sentiment\": \"Sentiment\", \"keywords\": [\"keyword1\", \"keyword2\"] },\n  { \"index\": 5, \"text\": \"Title 5 content\", \"style\": \"Style\", \"sentiment\": \"Sentiment\", \"keywords\": [\"keyword1\", \"keyword2\"] }\n]. Ensure the response is in this exact format.", 'oneclickcontent_titles' ),
				),
			),
		);

		$function_definition = array(
			'name'        => 'generate_5_titles_with_styles_and_keywords',
			'description' => esc_html__( 'Generate five titles that are search engine optimized for length and copy from the provided article content, including sentiment analysis, style, and relevant keywords, and return them in a specific JSON format.', 'oneclickcontent_titles' ),
			'parameters'  => array(
				'type'       => 'object',
				'properties' => array(
					'content' => array(
						'type'        => 'string',
						'description' => esc_html__( 'The content of the article for which titles are being generated.', 'oneclickcontent_titles' ),
					),
					'style'   => array(
						'type'        => 'string',
						'description' => esc_html__( 'The style of the titles to be generated. If not provided, the assistant will choose the most suitable style.', 'oneclickcontent_titles' ),
						'enum'        => array(
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
				'required'   => array( 'content' ), // 'Style' is optional.
			),
		);

		$model = get_option( 'occ_titles_openai_model', 'gpt-4o-mini' ); // Default to gpt-4o-mini if not set.

		$payload = array(
			'description'     => esc_html__( 'Assistant for generating SEO-optimized titles.', 'oneclickcontent_titles' ),
			'instructions'    => wp_json_encode( $initial_prompt ),
			'name'            => esc_html__( 'OneClickContent - Titles Assistant', 'oneclickcontent_titles' ),
			'tools'           => array(
				array(
					'type'     => 'function',
					'function' => $function_definition,
				),
			),
			'model'           => $model, // Use the selected model here.
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
			return false;
		}

		$response_body  = wp_remote_retrieve_body( $response );
		$assistant_data = json_decode( $response_body, true );

		if ( isset( $assistant_data['id'] ) ) {
			return $assistant_data['id'];
		} else {
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
			$existing_assistant_id = get_option( 'occ_titles_assistant_id', '' );

			if ( empty( $existing_assistant_id ) ) {
				$assistant_id = $this->occ_titles_create_assistant();

				if ( $assistant_id ) {
					update_option( 'occ_titles_assistant_id', $assistant_id );
					add_settings_error( 'occ_titles_assistant_id', 'assistant-created', __( 'Assistant successfully created.', 'oneclickcontent_titles' ), 'updated' );
				} else {
					add_settings_error( 'occ_titles_assistant_id', 'assistant-creation-failed', __( 'Failed to create assistant.', 'oneclickcontent_titles' ), 'error' );
				}
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
			wp_send_json_error( array( 'message' => __( 'Invalid nonce', 'oneclickcontent_titles' ) ) );
		}

		// Define the allowed fields that can be saved via AJAX.
		$allowed_fields = array(
			'occ_titles_openai_api_key',
			'occ_titles_post_types',
			'occ_titles_assistant_id',
			'occ_titles_openai_model',
		);

		// Check if the necessary $_POST variables are set.
		if ( isset( $_POST['field_name'], $_POST['field_value'] ) ) {
			// Sanitize the field name immediately.
			$field_name = sanitize_text_field( wp_unslash( $_POST['field_name'] ) );

			// Ensure that the field being saved is in the list of allowed fields.
			if ( ! in_array( $field_name, $allowed_fields, true ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid field name.', 'oneclickcontent_titles' ) ) );
			}

			// Sanitize and assign field_value depending on whether it's an array or not.
			$field_value = is_array( $_POST['field_value'] )
				? array_map( 'sanitize_text_field', wp_unslash( $_POST['field_value'] ) )
				: sanitize_text_field( wp_unslash( $_POST['field_value'] ) );

			// Update the option.
			update_option( $field_name, $field_value );

			// Additional logic for specific fields.
			if ( 'occ_titles_assistant_id' === $field_name ) {
				$instance = new self(); // Create an instance to use non-static methods.
				if ( ! $instance->validate_assistant_id( $field_value ) ) {
					$new_assistant_id = $instance->occ_titles_create_assistant();
					if ( $new_assistant_id ) {
						$field_value = $new_assistant_id;
						update_option( $field_name, $field_value );
						wp_send_json_success(
							array(
								'message'          => __( 'Invalid Assistant ID. A new one has been created.', 'oneclickcontent_titles' ),
								'new_assistant_id' => $new_assistant_id,
							)
						);
					} else {
						wp_send_json_error( array( 'message' => __( 'Failed to create a new Assistant ID.', 'oneclickcontent_titles' ) ) );
					}
				} else {
					update_option( $field_name, $field_value );
					wp_send_json_success( array( 'message' => __( 'Assistant ID is valid and settings saved successfully.', 'oneclickcontent_titles' ) ) );
				}
			} else {
				// Return a success response for other fields.
				wp_send_json_success( array( 'message' => __( 'Settings saved successfully.', 'oneclickcontent_titles' ) ) );
			}
		} else {
			wp_send_json_error( array( 'message' => __( 'Missing field_name or field_value.', 'oneclickcontent_titles' ) ) );
		}
	}

	/**
	 * Display admin notices for settings.
	 */
	public function display_admin_notices() {
		settings_errors();
	}
}
