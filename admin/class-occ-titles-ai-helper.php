<?php
/**
 * Class Occ_Titles_OpenAI_Helper
 *
 * Handles interactions with the OpenAI API.
 *
 * @since 1.0.0
 * @package Occ_Titles_OpenAI_Helper
 */

class Occ_Titles_OpenAI_Helper {

	/**
	 * Create a new thread in the OpenAI API.
	 *
	 * @since 1.0.0
	 * @param string $api_key The OpenAI API key.
	 * @return string|null The thread ID or null if failed.
	 */
	public function create_thread( $api_key ) {
		$response = wp_remote_post(
			'https://api.openai.com/v1/threads',
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
					'OpenAI-Beta'   => 'assistants=v2',
				),
				'body'    => '{}',
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! isset( $response_body['id'] ) ) {
			return null;
		}

		return $response_body['id'];
	}

	/**
	 * Add a message and run the thread in the OpenAI API.
	 *
	 * @since 1.0.0
	 * @param string $api_key      The OpenAI API key.
	 * @param string $thread_id    The thread ID.
	 * @param string $assistant_id The assistant ID.
	 * @param string $query        The query to add as a message.
	 * @return mixed The result of the run or an error message.
	 */
	public function add_message_and_run_thread( $api_key, $thread_id, $assistant_id, $query ) {
		// Step 3: Add a message to the thread.
		$message_api_url = "https://api.openai.com/v1/threads/{$thread_id}/messages";
		$body            = wp_json_encode(
			array(
				'role'    => 'user',
				'content' => $query,
			)
		);
		$response        = wp_remote_post(
			$message_api_url,
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
					'OpenAI-Beta'   => 'assistants=v2',
				),
				'body'    => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			return 'Failed to add message.';
		}

		// Step 4: Run the thread.
		$run_api_url = "https://api.openai.com/v1/threads/{$thread_id}/runs";
		$body        = wp_json_encode(
			array(
				'assistant_id' => $assistant_id,
			)
		);
		$response    = wp_remote_post(
			$run_api_url,
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
					'OpenAI-Beta'   => 'assistants=v2',
				),
				'body'    => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			return 'Failed to run thread.';
		}

		$response_body    = wp_remote_retrieve_body( $response );
		$decoded_response = json_decode( $response_body, true );

		if ( 'queued' === $decoded_response['status'] || 'running' === $decoded_response['status'] ) {
			return $this->wait_for_run_completion( $api_key, $decoded_response['id'], $thread_id );
		} elseif ( 'completed' === $decoded_response['status'] ) {
			return $this->fetch_messages_from_thread( $api_key, $thread_id );
		} else {
			return 'Run failed or was cancelled.';
		}
	}

	/**
	 * Wait for the run to complete in the OpenAI API.
	 *
	 * @since 1.0.0
	 * @param string $api_key   The OpenAI API key.
	 * @param string $run_id    The run ID.
	 * @param string $thread_id The thread ID.
	 * @return mixed The run result or an error message.
	 */
	private function wait_for_run_completion( $api_key, $run_id, $thread_id ) {
		$status_check_url = "https://api.openai.com/v1/threads/{$thread_id}/runs/{$run_id}";

		$attempts     = 0;
		$max_attempts = 20;

		while ( $attempts < $max_attempts ) {
			sleep( 5 );
			$response = wp_remote_get(
				$status_check_url,
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $api_key,
						'OpenAI-Beta'   => 'assistants=v2',
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				return 'Failed to check run status.';
			}

			$response_body    = wp_remote_retrieve_body( $response );
			$decoded_response = json_decode( $response_body, true );

			if ( isset( $decoded_response['error'] ) ) {
				return 'Error retrieving run status: ' . $decoded_response['error']['message'];
			}

			if ( isset( $decoded_response['status'] ) && 'completed' === $decoded_response['status'] ) {
				$this->cancel_run( $api_key, $thread_id, $decoded_response['id'] );
				return $this->fetch_messages_from_thread( $api_key, $thread_id );
			} elseif ( isset( $decoded_response['status'] ) && ( 'failed' === $decoded_response['status'] || 'cancelled' === $decoded_response['status'] ) ) {
				return 'Run failed or was cancelled.';
			} elseif ( isset( $decoded_response['status'] ) && 'requires_action' === $decoded_response['status'] ) {
				return $this->handle_requires_action( $api_key, $run_id, $thread_id, $decoded_response['required_action'] );
			}

			++$attempts;
		}

		return 'Run did not complete in expected time.';
	}

	/**
	 * Handle required actions for the run.
	 *
	 * @since 1.0.0
	 * @param string $api_key         The OpenAI API key.
	 * @param string $run_id          The run ID.
	 * @param string $thread_id       The thread ID.
	 * @param array  $required_action The required action details.
	 * @return mixed The run result or an error message.
	 */
	private function handle_requires_action( $api_key, $run_id, $thread_id, $required_action ) {
		if ( 'submit_tool_outputs' === $required_action['type'] ) {
			$tool_calls   = $required_action['submit_tool_outputs']['tool_calls'];
			$tool_outputs = array();

			foreach ( $tool_calls as $tool_call ) {
				$output = '';
				if ( 'function' === $tool_call['type'] ) {
					switch ( $tool_call['function']['name'] ) {
						case 'generate_word_list':
							$output = ''; // Need to determine from the response
							break;
						default:
							$output = wp_json_encode( array( 'success' => 'true' ) );
							break;
					}

					$tool_outputs[] = array(
						'tool_call_id' => $tool_call['id'],
						'output'       => $output,
					);
				}
			}

			$submit_tool_outputs_url = "https://api.openai.com/v1/threads/{$thread_id}/runs/{$run_id}/submit_tool_outputs";
			$response                = wp_remote_post(
				$submit_tool_outputs_url,
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $api_key,
						'OpenAI-Beta'   => 'assistants=v2',
						'Content-Type'  => 'application/json',
					),
					'body'    => wp_json_encode( array( 'tool_outputs' => $tool_outputs ) ),
				)
			);

			if ( is_wp_error( $response ) ) {
				return 'Failed to submit tool outputs.';
			}

			return $this->wait_for_run_completion( $api_key, $run_id, $thread_id );
		}

		return 'Unhandled requires_action.';
	}

	/**
	 * Fetch messages from the thread.
	 *
	 * @since 1.0.0
	 * @param string $api_key   The OpenAI API key.
	 * @param string $thread_id The thread ID.
	 * @return mixed The messages from the thread or an error message.
	 */
	public function fetch_messages_from_thread( $api_key, $thread_id ) {
		$messages_url = "https://api.openai.com/v1/threads/{$thread_id}/messages";

		$response = wp_remote_get(
			$messages_url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
					'OpenAI-Beta'   => 'assistants=v2',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return 'Failed to fetch messages.';
		}

		$response_body    = wp_remote_retrieve_body( $response );
		$decoded_response = json_decode( $response_body, true );

		if ( ! isset( $decoded_response['data'] ) ) {
			return 'No messages found.';
		}

		$messages = array_map(
			function ( $message ) {
				foreach ( $message['content'] as $content ) {
					if ( 'text' === $content['type'] ) {
						return json_decode( $content['text']['value'], true );
					}
				}
				return 'No text content.';
			},
			$decoded_response['data']
		);

		return $messages[0];
	}

	/**
	 * Cancel the run when complete.
	 *
	 * @since 1.0.0
	 * @param string $api_key   The OpenAI API key.
	 * @param string $thread_id The thread ID.
	 * @param string $run_id The run ID.
	 * @return mixed The messages from the thread or an error message.
	 */
	private function cancel_run( $api_key, $thread_id, $run_id ) {
		$cancel_url = "https://api.openai.com/v1/threads/{$thread_id}/runs/{$run_id}/cancel";
		$response   = wp_remote_post(
			$cancel_url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'OpenAI-Beta'   => 'assistants=v2',
					'Content-Type'  => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return 'Failed to cancel run.';
		}

		return 'Run cancelled successfully.';
	}
}
