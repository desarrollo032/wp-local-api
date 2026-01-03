<?php
/**
 * Main class for the AI API Proxy REST endpoints.
 *
 * @package WordPress\Feature_API_Agent
 */

namespace A8C\WpFeatureApiAgent;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Registers and handles REST API endpoints for proxying AI service requests.
 */
class WP_AI_API_Proxy {

	/**
	 * Supported AI API service providers.
	 */
	private const SUPPORTED_AI_API_SERVICES = [ 'openai', 'openrouter', 'gemini' ];

	/**
	 * Base URL for the OpenAI API.
	 */
	private const OPENAI_API_ROOT = 'https://api.openai.com/v1/';

	/**
	 * Default base URL for OpenRouter API (can be overridden in options).
	 */
	private const OPENROUTER_API_ROOT = 'https://openrouter.ai/api/v1/';
	
	/**
	 * Base URL for the Google Gemini API (OpenAI Compatibility).
	 */
	private const GEMINI_API_ROOT = 'https://generativelanguage.googleapis.com/v1beta/openai/';

	/**
	 * Cache namespace for AI proxy data.
	 */
	private const AI_API_PROXY_CACHE_NAMESPACE = 'ai_api_proxy';

	/**
	 * Cache key prefix for provider models.
	 */
	private const AI_API_PROXY_MODELS_CACHE_KEY_PREFIX = 'models';

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'ai-api-proxy/v1';

	/**
	 * REST API base route for the proxy.
	 *
	 * @var string
	 */
	protected $rest_base = '';

	/**
	 * Registers WordPress hooks.
	 */
	public function register_hooks() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Registers the REST API routes.
	 */
	public function register_rest_routes() {
		register_rest_route(
			$this->namespace,
			'/healthcheck',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'ai_api_healthcheck' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/models',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'list_available_models' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		// MCP status endpoint - check if wordpress-mcp is active
		register_rest_route(
			$this->namespace,
			'/mcp/status',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'mcp_status_check' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		// MCP tools endpoint - get available tools from wordpress-mcp
		register_rest_route(
			$this->namespace,
			'/mcp/tools',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'mcp_tools_list' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		// MCP call endpoint - execute MCP tools
		register_rest_route(
			$this->namespace,
			'/mcp/call',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'mcp_call_tool' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'tool'      => array(
						'description'       => __( 'The MCP tool name to execute.', 'wp-feature-api-agent' ),
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
					),
					'arguments' => array(
						'description'       => __( 'Arguments for the MCP tool.', 'wp-feature-api-agent' ),
						'type'              => 'object',
						'required'          => false,
						'default'           => array(),
						'sanitize_callback' => array( $this, 'sanitize_mcp_arguments' ),
						'validate_callback' => 'rest_validate_request_arg',
					),
				),
			)
		);

		// Specific route for chat completions (most commonly used endpoint)
		// This must be registered before the catch-all route
		register_rest_route(
			$this->namespace,
			'/chat/completions',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'ai_api_chat_completions' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		// Catch-all route for other AI API endpoints (must be registered last)
		register_rest_route(
			$this->namespace,
			'/(?P<api_path>.*)',
			array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => array( $this, 'ai_api_proxy' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'api_path' => array(
						'description'       => __( 'The path to proxy to the AI service API.', 'wp-feature-api-agent' ),
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => array( $this, 'sanitize_api_path' ),
						'validate_callback' => array( $this, 'validate_api_path' ),
					),
				),
			)
		);
	}

	/**
	 * Sanitizes MCP arguments to prevent injection attacks.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return array Sanitized arguments array.
	 */
	public function sanitize_mcp_arguments( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $value as $key => $arg_value ) {
			$clean_key = sanitize_key( $key );
			if ( is_string( $arg_value ) ) {
				// Use sanitize_textarea_field to preserve newlines and multiline content
				$sanitized[ $clean_key ] = sanitize_textarea_field( $arg_value );
			} elseif ( is_array( $arg_value ) ) {
				$sanitized[ $clean_key ] = $this->sanitize_mcp_arguments( $arg_value );
			} elseif ( is_numeric( $arg_value ) ) {
				$sanitized[ $clean_key ] = $arg_value;
			} elseif ( is_bool( $arg_value ) ) {
				$sanitized[ $clean_key ] = $arg_value;
			} elseif ( is_null( $arg_value ) ) {
				$sanitized[ $clean_key ] = null;
			}
			// Skip other types for security
		}

		return $sanitized;
	}

	/**
	 * Sanitizes API path to prevent path traversal attacks.
	 *
	 * @param string $path The API path to sanitize.
	 * @return string Sanitized path.
	 */
	public function sanitize_api_path( $path ) {
		// Remove any path traversal attempts
		$path = str_replace( array( '../', '..\\', './', '.\\' ), '', $path );
		
		// Remove null bytes
		$path = str_replace( "\0", '', $path );
		
		// Sanitize as text field
		return sanitize_text_field( $path );
	}

	/**
	 * Validates API path to ensure it's safe.
	 *
	 * @param string $path The API path to validate.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	public function validate_api_path( $path ) {
		// Check for path traversal attempts
		if ( strpos( $path, '..' ) !== false ) {
			return new WP_Error(
				'invalid_api_path',
				__( 'Invalid API path: path traversal not allowed.', 'wp-feature-api-agent' ),
				array( 'status' => 400 )
			);
		}

		// Check for null bytes
		if ( strpos( $path, "\0" ) !== false ) {
			return new WP_Error(
				'invalid_api_path',
				__( 'Invalid API path: null bytes not allowed.', 'wp-feature-api-agent' ),
				array( 'status' => 400 )
			);
		}

		// Validate length
		if ( strlen( $path ) > 500 ) {
			return new WP_Error(
				'invalid_api_path',
				__( 'Invalid API path: path too long.', 'wp-feature-api-agent' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Checks if the current user has permissions to access protected endpoints.
	 *
	 * @return bool|WP_Error True if the user has permission, WP_Error otherwise.
	 */
	public function check_permissions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to access this endpoint.', 'wp-feature-api-agent' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Healthcheck endpoint callback.
	 * Checks if required API key constants are defined.
	 *
	 * @param WP_REST_Request $request Incoming request data.
	 * @return WP_REST_Response Response object.
	 */
	public function ai_api_healthcheck( WP_REST_Request $request ) {
		$provider = WP_AI_API_Options::get_provider();

		$all_defined = false;
		switch ( $provider ) {
			case 'openrouter':
				$all_defined = ! empty( WP_AI_API_Options::get_openrouter_api_key() );
				break;
			case 'gemini':
				$all_defined = ! empty( WP_AI_API_Options::get_gemini_api_key() );
				break;
			case 'openai':
			default:
				$all_defined = ! empty( WP_AI_API_Options::get_openai_api_key() );
				break;
		}

		$status = $all_defined ? 'OK' : 'Configuration Error';
		$code   = $all_defined ? 200 : 500;

		return new WP_REST_Response( array( 'status' => $status ), $code );
	}

	/**
	 * Chat completions endpoint callback.
	 * Proxies chat completion requests to the configured AI service.
	 *
	 * @param WP_REST_Request $request Incoming request data.
	 * @return WP_Error|WP_REST_Response Proxied response or error.
	 */
	public function ai_api_chat_completions( WP_REST_Request $request ) {
		// Create a new request with the api_path parameter set to 'chat/completions'
		$request->set_param( 'api_path', 'chat/completions' );
		
		// Delegate to the generic proxy method
		return $this->ai_api_proxy( $request );
	}

	/**
	 * Lists all the models available from the configured providers (OpenAI).
	 *
	 * @param WP_REST_Request $request Incoming request data.
	 * @return WP_Error|WP_REST_Response Model list data or error.
	 */
	public function list_available_models( WP_REST_Request $request ) {
		$all_models = array();

		foreach ( self::SUPPORTED_AI_API_SERVICES as $provider ) {
			$models = $this->get_provider_model_list( $provider );
			if ( is_array( $models ) ) {
				foreach ( $models as $model ) {
					if ( is_object( $model ) ) {
						$model->owned_by = sanitize_text_field( $provider );
						
						// For OpenRouter, prioritize free models
						if ( $provider === 'openrouter' ) {
							$model->is_free = $this->is_free_openrouter_model( $model );
							$model->provider_name = $provider;
						}
						
						$all_models[] = $model;
					}
				}
			}
		}

		if ( empty( $all_models ) ) {
			return new WP_Error(
				'model_list_failed',
				__( 'Unable to retrieve model lists from any provider.', 'wp-feature-api-agent' ),
				array( 'status' => 500 )
			);
		}

		// Sort models to put free ones first for OpenRouter
		usort( $all_models, function( $a, $b ) {
			// If both are from OpenRouter, prioritize free models
			if ( isset( $a->provider_name ) && isset( $b->provider_name ) && 
				 $a->provider_name === 'openrouter' && $b->provider_name === 'openrouter' ) {
				if ( isset( $a->is_free ) && isset( $b->is_free ) ) {
					if ( $a->is_free && ! $b->is_free ) return -1;
					if ( ! $a->is_free && $b->is_free ) return 1;
				}
			}
			// Prioritize OpenRouter models over OpenAI when OpenRouter is selected
			$provider = WP_AI_API_Options::get_provider();
			if ( $provider === 'openrouter' ) {
				if ( isset( $a->provider_name ) && isset( $b->provider_name ) ) {
					if ( $a->provider_name === 'openrouter' && $b->provider_name !== 'openrouter' ) return -1;
					if ( $a->provider_name !== 'openrouter' && $b->provider_name === 'openrouter' ) return 1;
				}
			}

			return 0;
		});

		$response_data = (object) array(
			'object' => 'list',
			'data'   => $all_models,
		);

		return new WP_REST_Response( $response_data );
	}

	/**
	 * Determines if an OpenRouter model is free.
	 *
	 * @param object $model The model object from OpenRouter API.
	 * @return bool True if the model is free.
	 */
	private function is_free_openrouter_model( $model ) {
		// Check if pricing information indicates free model
		if ( isset( $model->pricing ) ) {
			if ( isset( $model->pricing->prompt ) && isset( $model->pricing->completion ) ) {
				$prompt_cost = floatval( $model->pricing->prompt );
				$completion_cost = floatval( $model->pricing->completion );
				return $prompt_cost === 0.0 && $completion_cost === 0.0;
			}
		}

		// List of known free models on OpenRouter (updated list)
		$free_models = array(
			'microsoft/phi-3-mini-128k-instruct:free',
			'microsoft/phi-3-medium-128k-instruct:free',
			'huggingfaceh4/zephyr-7b-beta:free',
			'openchat/openchat-7b:free',
			'gryphe/mythomist-7b:free',
			'undi95/toppy-m-7b:free',
			'openrouter/auto',
			'nousresearch/nous-capybara-7b:free',
			'mistralai/mistral-7b-instruct:free',
			'google/gemma-7b-it:free',
			'meta-llama/llama-3-8b-instruct:free',
			'qwen/qwen-2-7b-instruct:free',
		);

		$model_id = isset( $model->id ) ? $model->id : '';
		return in_array( $model_id, $free_models, true );
	}

	/**
	 * Proxies the request to the appropriate AI service (OpenAI).
	 *
	 * @param WP_REST_Request $request Incoming request data.
	 * @return WP_Error|WP_REST_Response Vendor data or error.
	 */
	public function ai_api_proxy( WP_REST_Request $request ) {
		$api_path = $request->get_param( 'api_path' );
		$method   = $request->get_method();
		$body     = $request->get_body();
		$headers  = $request->get_headers();

		// Validate and sanitize API path
		$validation_result = $this->validate_api_path( $api_path );
		if ( is_wp_error( $validation_result ) ) {
			return $validation_result;
		}
		$api_path = $this->sanitize_api_path( $api_path );

		// Choose provider based on options
		$target_service = WP_AI_API_Options::get_provider();
		switch ( $target_service ) {
			case 'openrouter':
				$host = WP_AI_API_Options::get_openrouter_api_host();
				if ( empty( $host ) ) {
					$host = self::OPENROUTER_API_ROOT;
				}
				$target_url  = esc_url_raw( rtrim( $host, '/' ) . '/' . ltrim( $api_path, '/' ) );
				$auth_header = sprintf( 'Bearer %s', WP_AI_API_Options::get_openrouter_api_key() );
				break;
			case 'gemini':
				$target_url  = esc_url_raw( self::GEMINI_API_ROOT . $api_path );
				$auth_header = sprintf( 'Bearer %s', WP_AI_API_Options::get_gemini_api_key() );
				break;
			case 'openai':
			default:
				$target_url  = esc_url_raw( self::OPENAI_API_ROOT . $api_path );
				$auth_header = sprintf( 'Bearer %s', WP_AI_API_Options::get_openai_api_key() );
				break;
		}

		// Validate target URL
		if ( ! wp_http_validate_url( $target_url ) ) {
			return new WP_Error(
				'invalid_target_url',
				__( 'Invalid target URL generated.', 'wp-feature-api-agent' ),
				array( 'status' => 400 )
			);
		}

		$outgoing_headers = array(
			'Content-Type'  => isset( $headers['content_type'][0] ) ? sanitize_text_field( $headers['content_type'][0] ) : ( ! empty( $body ) ? 'application/json' : null ),
			'User-Agent'    => 'WordPress AI API Proxy/' . WP_AI_API_PROXY_VERSION,
			'Authorization' => $auth_header,
		);

		// Add OpenRouter specific headers for better compatibility
		if ( $target_service === 'openrouter' ) {
			$outgoing_headers['HTTP-Referer'] = home_url();
			$outgoing_headers['X-Title'] = get_bloginfo( 'name' ) . ' - WordPress Feature API Demo';
		}

		$outgoing_headers = array_filter( $outgoing_headers );

		$query_params = $request->get_query_params();
		if ( ! empty( $query_params ) ) {
			// Remove WordPress-specific parameters
			unset( $query_params['_envelope'] );
			unset( $query_params['_locale'] );
			unset( $query_params['_wpnonce'] );
			
			// Sanitize remaining parameters
			$sanitized_params = array();
			foreach ( $query_params as $key => $value ) {
				$clean_key = sanitize_key( $key );
				if ( is_string( $value ) ) {
					$sanitized_params[ $clean_key ] = sanitize_text_field( $value );
				} elseif ( is_array( $value ) ) {
					$sanitized_params[ $clean_key ] = array_map( 'sanitize_text_field', $value );
				}
			}
			
			if ( ! empty( $sanitized_params ) ) {
				$target_url = add_query_arg( $sanitized_params, $target_url );
			}
		}

		$response = wp_remote_request(
			$target_url,
			array(
				'method'      => $method,
				'headers'     => $outgoing_headers,
				'body'        => $body,
				'timeout'     => 60,
				'redirection' => 0, // Prevent redirects for security
				'sslverify'   => true, // Always verify SSL
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'proxy_request_failed',
				sprintf(
					/* translators: %s: Error message */
					__( 'Failed to connect to the AI service: %s', 'wp-feature-api-agent' ),
					$response->get_error_message()
				),
				array( 'status' => 502 )
			);
		}

		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_headers = wp_remote_retrieve_headers( $response );
		$response_body    = wp_remote_retrieve_body( $response );

		// Log response details for debugging errors
		if ( WP_DEBUG && $response_code >= 400 ) {
			error_log( 'WP Feature API Proxy: Error response from AI service' );
			error_log( 'WP Feature API Proxy: Response Code: ' . $response_code );
			error_log( 'WP Feature API Proxy: Target URL: ' . $target_url );
		}

		// Handle empty response body
		if ( empty( $response_body ) ) {
			return new WP_Error(
				'empty_response',
				__( 'Empty response from AI service.', 'wp-feature-api-agent' ),
				array( 'status' => 502 )
			);
		}

		$client_headers = array();
		if ( isset( $response_headers['content-type'] ) ) {
			$client_headers['Content-Type'] = sanitize_text_field( $response_headers['content-type'] );
		}

		if ( isset( $response_headers['x-request-id'] ) ) {
			$client_headers['X-Request-ID'] = sanitize_text_field( $response_headers['x-request-id'] );
		}

		// Create response with original body first
		$wp_response = new WP_REST_Response( null, $response_code );

		foreach ( $client_headers as $key => $value ) {
			$wp_response->header( $key, $value );
		}

		// Process JSON responses with better error handling
		$is_json = isset( $client_headers['Content-Type'] ) && 
				   ( str_contains( strtolower( $client_headers['Content-Type'] ), 'application/json' ) ||
					 str_contains( strtolower( $client_headers['Content-Type'] ), 'text/event-stream' ) );

		if ( $is_json ) {
			// Handle streaming responses (Server-Sent Events)
			if ( str_contains( strtolower( $client_headers['Content-Type'] ), 'text/event-stream' ) ) {
				// For streaming responses, return the raw body
				$wp_response->set_data( $response_body );
			} else {
				// Handle regular JSON responses
				$decoded_body = json_decode( $response_body, true );
				if ( json_last_error() === JSON_ERROR_NONE && $decoded_body !== null ) {
					$wp_response->set_data( $decoded_body );
				} else {
					// If JSON decode fails, return raw body but log the error
					if ( WP_DEBUG ) {
						error_log( 'WP Feature API Proxy: JSON decode error: ' . json_last_error_msg() );
						error_log( 'WP Feature API Proxy: Response body: ' . substr( $response_body, 0, 500 ) );
					}
					
					// Try to return a structured error response
					$wp_response->set_data( array(
						'error' => array(
							'message' => 'Invalid JSON response from AI service',
							'type' => 'proxy_error',
							'raw_response' => substr( $response_body, 0, 1000 ) // First 1000 chars for debugging
						)
					) );
				}
			}
		} else {
			// For non-JSON responses, return raw body
			$wp_response->set_data( $response_body );
		}

		return $wp_response;
	}

	/**
	 * Returns the list of available models for a specific provider.
	 * Uses caching.
	 *
	 * @param string $provider The provider key ('openai').
	 * @return array List of models (structure depends on provider) or empty array on error/cache miss failure.
	 */
	private function get_provider_model_list( string $provider ): array {
		if ( ! in_array( $provider, self::SUPPORTED_AI_API_SERVICES, true ) ) {
			return array();
		}

		$api_key = '';
		switch ( $provider ) {
			case 'openrouter':
				$api_key = WP_AI_API_Options::get_openrouter_api_key();
				break;
			case 'gemini':
				$api_key = WP_AI_API_Options::get_gemini_api_key();
				break;
			case 'openai':
			default:
				$api_key = WP_AI_API_Options::get_openai_api_key();
				break;
		}
		if ( empty( $api_key ) ) {
			return array();
		}

		// Use transients for persistent cache across requests
		$cache_key = sprintf( '%s_%s_%s', self::AI_API_PROXY_CACHE_NAMESPACE, self::AI_API_PROXY_MODELS_CACHE_KEY_PREFIX, sanitize_key( $provider ) );

		$cached_models = get_transient( $cache_key );
		if ( false !== $cached_models && is_array( $cached_models ) ) {
			return $cached_models;
		}

		$headers  = array();
		$api_path = '';

		switch ( $provider ) {
			case 'openrouter':
				$headers = array(
					'Authorization' => sprintf( 'Bearer %s', WP_AI_API_Options::get_openrouter_api_key() ),
					'User-Agent'    => 'WordPress AI API Proxy/' . WP_AI_API_PROXY_VERSION,
				);
				$host = WP_AI_API_Options::get_openrouter_api_host();
				if ( empty( $host ) ) {
					$host = self::OPENROUTER_API_ROOT;
				}
				$api_path = esc_url_raw( rtrim( $host, '/' ) . '/models' );
				break;
			case 'gemini':
				$headers = array(
					'Authorization' => sprintf( 'Bearer %s', WP_AI_API_Options::get_gemini_api_key() ),
					'User-Agent'    => 'WordPress AI API Proxy/' . WP_AI_API_PROXY_VERSION,
				);
				$api_path = esc_url_raw( self::GEMINI_API_ROOT . 'models' );
				break;
			case 'openai':
			default:
				$headers = array(
					'Authorization' => sprintf( 'Bearer %s', WP_AI_API_Options::get_openai_api_key() ),
					'User-Agent'    => 'WordPress AI API Proxy/' . WP_AI_API_PROXY_VERSION,
				);
				$api_path = esc_url_raw( self::OPENAI_API_ROOT . 'models' );
				break;
		}

		if ( empty( $api_path ) || ! wp_http_validate_url( $api_path ) ) {
			return array();
		}

		$response = wp_remote_get(
			$api_path,
			array(
				'headers'     => $headers,
				'timeout'     => 30,
				'redirection' => 0,
				'sslverify'   => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body = wp_remote_retrieve_body( $response );
		if ( ! $body ) {
			return array();
		}

		$json_data = json_decode( $body );
		if ( ! $json_data || ! is_object( $json_data ) ) {
			return array();
		}

		$models_data = array();
		if ( isset( $json_data->data ) && is_array( $json_data->data ) ) {
			$models_data = $json_data->data;
		} else {
			return array();
		}

		if ( is_array( $models_data ) ) {
			// Use transients for persistent cache (30 minutes)
			set_transient( $cache_key, $models_data, 30 * MINUTE_IN_SECONDS );
			return $models_data;
		} else {
			return array();
		}
	}

	/**
	 * Checks if the wordpress-mcp plugin is active and returns its status.
	 *
	 * @param WP_REST_Request $request Incoming request data.
	 * @return WP_REST_Response Response with MCP status.
	 */
	public function mcp_status_check( WP_REST_Request $request ) {
		$mcp_active = false;
		$tools_count = 0;
		$mcp_version = null;

		// Check multiple ways to detect wordpress-mcp plugin
		// Method 1: Check for WPMCP() global function (recommended way)
		if ( function_exists( 'WPMCP' ) ) {
			$mcp_active = true;
			$mcp_version = defined( 'WORDPRESS_MCP_VERSION' ) ? sanitize_text_field( WORDPRESS_MCP_VERSION ) : 'unknown';

			try {
				$mcp = WPMCP();
				if ( method_exists( $mcp, 'get_tools' ) ) {
					$tools = $mcp->get_tools();
					$tools_count = is_array( $tools ) ? count( $tools ) : 0;
				}
			} catch ( \Exception $e ) {
				// Function exists but might have issues
			}
		}
		// Method 2: Check for the main class
		elseif ( class_exists( 'Automattic\\WordpressMcp\\Plugin' ) ) {
			$mcp_active = true;
			$mcp_version = defined( 'WORDPRESS_MCP_VERSION' ) ? sanitize_text_field( WORDPRESS_MCP_VERSION ) : 'unknown';
		}
		// Method 3: Check for legacy class names
		elseif ( class_exists( 'WordPress_MCP' ) ) {
			$mcp_active = true;
			$mcp_version = defined( 'WORDPRESS_MCP_VERSION' ) ? sanitize_text_field( WORDPRESS_MCP_VERSION ) : 'unknown';
		}
		// Method 4: Check if the plugin file exists and is active
		elseif ( function_exists( 'is_plugin_active' ) ) {
			$plugin_slugs = array(
				'wordpress-mcp/wordpress-mcp.php',
				'wordpress-mcp/plugin.php',
			);
			foreach ( $plugin_slugs as $slug ) {
				if ( is_plugin_active( $slug ) ) {
					$mcp_active = true;
					break;
				}
			}
		}

		// If MCP is active, try to get tool count via REST endpoint
		if ( $mcp_active && $tools_count === 0 ) {
			// Try to call the MCP tools/list endpoint directly
			$tools_response = $this->get_mcp_tools_internal();
			if ( is_array( $tools_response ) ) {
				$tools_count = count( $tools_response );
			}
		}

		return new WP_REST_Response(
			array(
				'is_active'   => $mcp_active,
				'tools_count' => absint( $tools_count ),
				'version'     => $mcp_version,
				'status'      => $mcp_active ? 'connected' : 'inactive',
			)
		);
	}

	/**
	 * Internal method to get MCP tools.
	 *
	 * @return array|null Array of tools or null on failure.
	 */
	private function get_mcp_tools_internal() {
		// Try using WPMCP() function
		if ( function_exists( 'WPMCP' ) ) {
			try {
				$mcp = WPMCP();
				if ( method_exists( $mcp, 'get_tools' ) ) {
					return $mcp->get_tools();
				}
			} catch ( \Exception $e ) {
				// Continue to other methods
			}
		}

		// Try calling the MCP JSON-RPC endpoint internally
		$request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );
		$request->set_body( wp_json_encode( array(
			'jsonrpc' => '2.0',
			'method'  => 'tools/list',
			'id'      => 1,
			'params'  => new \stdClass(),
		) ) );
		$request->set_header( 'Content-Type', 'application/json' );

		$response = rest_do_request( $request );
		if ( ! is_wp_error( $response ) && $response->get_status() === 200 ) {
			$data = $response->get_data();
			if ( isset( $data['result']['tools'] ) ) {
				return $data['result']['tools'];
			}
		}

		return null;
	}

	/**
	 * Returns the list of available tools from wordpress-mcp.
	 *
	 * @param WP_REST_Request $request Incoming request data.
	 * @return WP_REST_Response Response with MCP tools or empty array.
	 */
	public function mcp_tools_list( WP_REST_Request $request ) {
		$tools = array();

		// Method 1: Try using WPMCP() function
		if ( function_exists( 'WPMCP' ) ) {
			try {
				$mcp = WPMCP();
				if ( method_exists( $mcp, 'get_tools' ) ) {
					$raw_tools = $mcp->get_tools();
					if ( is_array( $raw_tools ) ) {
						$tools = array_values( $raw_tools );
					}
				}
			} catch ( \Exception $e ) {
				// Continue to other methods
			}
		}

		// Method 2: Try calling the MCP JSON-RPC endpoint internally
		if ( empty( $tools ) ) {
			$mcp_request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );
			$mcp_request->set_body( wp_json_encode( array(
				'jsonrpc' => '2.0',
				'method'  => 'tools/list',
				'id'      => 1,
				'params'  => new \stdClass(),
			) ) );
			$mcp_request->set_header( 'Content-Type', 'application/json' );

			$response = rest_do_request( $mcp_request );
			if ( ! is_wp_error( $response ) && $response->get_status() === 200 ) {
				$data = $response->get_data();
				if ( isset( $data['result']['tools'] ) && is_array( $data['result']['tools'] ) ) {
					$tools = $data['result']['tools'];
				}
			}
		}

		// Method 3: Legacy methods
		if ( empty( $tools ) ) {
			if ( function_exists( 'wp_mcp_get_tools' ) ) {
				$raw_tools = wp_mcp_get_tools();
				if ( is_array( $raw_tools ) ) {
					$tools = array_values( $raw_tools );
				}
			}
		}

		return new WP_REST_Response(
			array(
				'tools' => $tools,
				'count' => absint( count( $tools ) ),
			)
		);
	}

	/**
	 * Executes an MCP tool.
	 *
	 * @param WP_REST_Request $request Incoming request data.
	 * @return WP_REST_Response Response with tool execution result.
	 */
	public function mcp_call_tool( WP_REST_Request $request ) {
		$tool_name = sanitize_text_field( $request->get_param( 'tool' ) );
		$arguments = $request->get_param( 'arguments' );

		if ( empty( $tool_name ) ) {
			return new WP_REST_Response(
				array(
					'error'   => 'Missing tool parameter',
					'message' => 'Tool name is required',
				),
				400
			);
		}

		// Sanitize arguments
		if ( ! is_array( $arguments ) ) {
			$arguments = array();
		}
		$arguments = $this->sanitize_mcp_arguments( $arguments );

		// Method 1: Try using WPMCP() function
		if ( function_exists( 'WPMCP' ) ) {
			try {
				$mcp = WPMCP();
				if ( method_exists( $mcp, 'call_tool' ) ) {
					$result = $mcp->call_tool( $tool_name, $arguments );
					return new WP_REST_Response(
						array(
							'result' => $result,
							'tool'   => $tool_name,
						)
					);
				}
			} catch ( \Exception $e ) {
				return new WP_REST_Response(
					array(
						'error'   => 'Tool execution failed',
						'message' => sanitize_text_field( $e->getMessage() ),
						'tool'    => $tool_name,
					),
					500
				);
			}
		}

		// Method 2: Try calling the MCP JSON-RPC endpoint
		$mcp_request = new WP_REST_Request( 'POST', '/wp/v2/wpmcp' );
		$mcp_request->set_body( wp_json_encode( array(
			'jsonrpc' => '2.0',
			'method'  => 'tools/call',
			'id'      => 1,
			'params'  => array(
				'name'      => $tool_name,
				'arguments' => $arguments,
			),
		) ) );
		$mcp_request->set_header( 'Content-Type', 'application/json' );

		$response = rest_do_request( $mcp_request );
		if ( ! is_wp_error( $response ) && $response->get_status() === 200 ) {
			$data = $response->get_data();
			if ( isset( $data['result'] ) ) {
				return new WP_REST_Response(
					array(
						'result' => $data['result'],
						'tool'   => $tool_name,
					)
				);
			}
			if ( isset( $data['error'] ) ) {
				return new WP_REST_Response(
					array(
						'error'   => 'MCP tool error',
						'message' => isset( $data['error']['message'] ) ? sanitize_text_field( $data['error']['message'] ) : 'Unknown error',
						'tool'    => $tool_name,
					),
					500
				);
			}
		}

		// Method 3: Legacy methods
		try {
			if ( function_exists( 'wp_mcp_call_tool' ) ) {
				$result = wp_mcp_call_tool( $tool_name, $arguments );
				return new WP_REST_Response(
					array(
						'result' => $result,
						'tool'   => $tool_name,
					)
				);
			}

			return new WP_REST_Response(
				array(
					'error'   => 'MCP not available',
					'message' => 'wordpress-mcp plugin is not active or not properly configured',
				),
				503
			);
		} catch ( \Exception $e ) {
			return new WP_REST_Response(
				array(
					'error'   => 'Tool execution failed',
					'message' => sanitize_text_field( $e->getMessage() ),
					'tool'    => $tool_name,
				),
				500
			);
		}
	}
}
