<?php
/**
 * Main class for the AI API Proxy REST endpoints.
 *
 * @package WordPress\Feature_API_Agent
 */

namespace A8C\WpFeatureApiAgent;

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
	private const SUPPORTED_AI_API_SERVICES = [ 'openai', 'openrouter' ];

	/**
	 * Base URL for the OpenAI API.
	 */
	private const OPENAI_API_ROOT = 'https://api.openai.com/v1/';

	/**
	 * Default base URL for OpenRouter API (can be overridden in options).
	 */
	private const OPENROUTER_API_ROOT = 'https://openrouter.ai/v1/';

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
	protected $namespace = 'wp/v2';

	/**
	 * REST API base route for the proxy.
	 *
	 * @var string
	 */
	protected $rest_base = 'ai-api-proxy/v1';

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
			'/' . $this->rest_base . '/healthcheck',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'ai_api_healthcheck' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/models',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'list_available_models' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		// MCP status endpoint - check if wordpress-mcp is active
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/mcp/status',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'mcp_status_check' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		// MCP tools endpoint - get available tools from wordpress-mcp
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/mcp/tools',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'mcp_tools_list' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		// MCP call endpoint - execute MCP tools
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/mcp/call',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'mcp_call_tool' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'tool'      => array(
						'description' => __( 'The MCP tool name to execute.', 'wp-feature-api-agent' ),
						'type'        => 'string',
						'required'    => true,
					),
					'arguments' => array(
						'description' => __( 'Arguments for the MCP tool.', 'wp-feature-api-agent' ),
						'type'        => 'object',
						'required'    => false,
						'default'     => array(),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<api_path>.*)',
			array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => array( $this, 'ai_api_proxy' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'api_path' => array(
						'description' => __( 'The path to proxy to the AI service API.', 'wp-feature-api-agent' ),
						'type'        => 'string',
						'required'    => true,
					),
				),
			)
		);
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
			case 'openai':
			default:
				$all_defined = ! empty( WP_AI_API_Options::get_openai_api_key() );
				break;
		}

		$status = $all_defined ? 'OK' : 'Configuration Error';
		$code   = $all_defined ? 200 : 500;

		return new WP_REST_Response( [ 'status' => $status ], $code );
	}

	/**
	 * Lists all the models available from the configured providers (OpenAI).
	 *
	 * @param WP_REST_Request $request Incoming request data.
	 * @return WP_Error|WP_REST_Response Model list data or error.
	 */
	public function list_available_models( WP_REST_Request $request ) {
		$all_models = [];

		foreach ( self::SUPPORTED_AI_API_SERVICES as $provider ) {
			$models = $this->get_provider_model_list( $provider );
			if ( is_array( $models ) ) {
				foreach ( $models as $model ) {
					if ( is_object( $model ) ) {
						$model->owned_by = $provider;
						$all_models[]    = $model;
					}
				}
			}
		}

		if ( empty( $all_models ) ) {
			return new WP_Error(
				'model_list_failed',
				__( 'Unable to retrieve model lists from any provider.', 'wp-feature-api-agent' ),
				[ 'status' => 500 ]
			);
		}

		$response_data = (object) [
			'object' => 'list',
			'data'   => $all_models,
		];

		return new WP_REST_Response( $response_data );
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

		// Choose provider based on options
		$target_service = WP_AI_API_Options::get_provider();
		switch ( $target_service ) {
			case 'openrouter':
				$host = WP_AI_API_Options::get_openrouter_api_host();
				if ( empty( $host ) ) {
					$host = self::OPENROUTER_API_ROOT;
				}
				$target_url  = rtrim( $host, '/' ) . '/' . ltrim( $api_path, '/' );
				$auth_header = sprintf( 'Bearer %s', WP_AI_API_Options::get_openrouter_api_key() );
				break;
			case 'openai':
			default:
				$target_url  = self::OPENAI_API_ROOT . $api_path;
				$auth_header = sprintf( 'Bearer %s', WP_AI_API_Options::get_openai_api_key() );
				break;
		}

		$outgoing_headers = array(
			'Content-Type' => $headers['content_type'][0] ?? ( ! empty( $body ) ? 'application/json' : null ),
			'User-Agent'   => 'WordPress AI API Proxy/' . WP_AI_API_PROXY_VERSION,
			'Authorization' => $auth_header,
		);

		$outgoing_headers = array_filter( $outgoing_headers );

		$query_params = $request->get_query_params();
		if ( ! empty( $query_params ) ) {
			unset( $query_params['_envelope'] );
			unset( $query_params['_locale'] );
			$target_url = add_query_arg( $query_params, $target_url );
		}

		$response = wp_remote_request(
			$target_url,
			array(
				'method'  => $method,
				'headers' => $outgoing_headers,
				'body'    => $body,
				'timeout' => 60,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'proxy_request_failed',
				__( 'Failed to connect to the AI service.', 'wp-feature-api-agent' ),
				array( 'status' => 502 )
			);
		}

		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_headers = wp_remote_retrieve_headers( $response );
		$response_body    = wp_remote_retrieve_body( $response );

		$client_headers = [];
		if ( isset( $response_headers['content-type'] ) ) {
			$client_headers['Content-Type'] = $response_headers['content-type'];
		}

		if ( isset( $response_headers['x-request-id'] ) ) {
			$client_headers['X-Request-ID'] = $response_headers['x-request-id'];
		}

		$wp_response = new WP_REST_Response( $response_body, $response_code );

		foreach ( $client_headers as $key => $value ) {
			$wp_response->header( $key, $value );
		}

		// Process JSON responses
		if ( isset( $client_headers['Content-Type'] ) && str_contains( strtolower( $client_headers['Content-Type'] ), 'application/json' ) ) {
			$decoded_body = json_decode( $response_body );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				$wp_response->set_data( $decoded_body );
			}
		} else {
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
			return [];
		}

		$api_key = '';
		switch ( $provider ) {
			case 'openrouter':
				$api_key = WP_AI_API_Options::get_openrouter_api_key();
				break;
			case 'openai':
			default:
				$api_key = WP_AI_API_Options::get_openai_api_key();
				break;
		}
		if ( empty( $api_key ) ) {
			return [];
		}

		$cache_key = sprintf( '%s-%s', self::AI_API_PROXY_MODELS_CACHE_KEY_PREFIX, $provider );
		$found     = false;

		$cached_models = wp_cache_get( $cache_key, self::AI_API_PROXY_CACHE_NAMESPACE, false, $found );
		if ( $found ) {
			return is_array( $cached_models ) ? $cached_models : [];
		}

		$headers  = [];
		$api_path = '';

		switch ( $provider ) {
			case 'openrouter':
				$headers = [
					'Authorization' => sprintf( 'Bearer %s', WP_AI_API_Options::get_openrouter_api_key() ),
					'User-Agent'    => 'WordPress AI API Proxy/' . WP_AI_API_PROXY_VERSION,
				];
				$host = WP_AI_API_Options::get_openrouter_api_host();
				if ( empty( $host ) ) {
					$host = self::OPENROUTER_API_ROOT;
				}
				$api_path = rtrim( $host, '/' ) . '/models';
				break;
			case 'openai':
			default:
				$headers = [
					'Authorization' => sprintf( 'Bearer %s', WP_AI_API_Options::get_openai_api_key() ),
					'User-Agent'    => 'WordPress AI API Proxy/' . WP_AI_API_PROXY_VERSION,
				];
				$api_path = self::OPENAI_API_ROOT . 'models';
				break;
		}

		if ( empty( $api_path ) ) {
			return [];
		}

		$response = wp_remote_get(
			$api_path,
			array(
				'headers' => $headers,
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return [];
		}

		$body = wp_remote_retrieve_body( $response );
		if ( ! $body ) {
			return [];
		}

		$json_data = json_decode( $body );
		if ( ! $json_data || ! is_object( $json_data ) ) {
			return [];
		}

		$models_data = [];
		if ( isset( $json_data->data ) && is_array( $json_data->data ) ) {
			$models_data = $json_data->data;
		} else {
			return [];
		}

		if ( is_array( $models_data ) ) {
			wp_cache_set( $cache_key, $models_data, self::AI_API_PROXY_CACHE_NAMESPACE, 30 * MINUTE_IN_SECONDS );
			return $models_data;
		} else {
			return [];
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

		// Check if wordpress-mcp plugin is active
		if ( class_exists( 'WordPress_MCP' ) ) {
			$mcp_active = true;
			$mcp_version = defined( 'WORDPRESS_MCP_VERSION' ) ? WORDPRESS_MCP_VERSION : 'unknown';

			// Try to get tool count from MCP registry if available
			if ( function_exists( 'wp_mcp_get_tools' ) ) {
				$tools = wp_mcp_get_tools();
				$tools_count = is_array( $tools ) ? count( $tools ) : 0;
			} elseif ( class_exists( 'WordPress_MCP\Server' ) ) {
				// Alternative detection for different MCP versions
				try {
					$server = \WordPress_MCP\Server::get_instance();
					if ( method_exists( $server, 'get_tools' ) ) {
						$tools = $server->get_tools();
						$tools_count = is_array( $tools ) ? count( $tools ) : 0;
					}
				} catch ( Exception $e ) {
					// Server not available
				}
			}
		}

		return new WP_REST_Response(
			array(
				'is_active'   => $mcp_active,
				'tools_count' => $tools_count,
				'version'     => $mcp_version,
				'status'      => $mcp_active ? 'connected' : 'inactive',
			)
		);
	}

	/**
	 * Returns the list of available tools from wordpress-mcp.
	 *
	 * @param WP_REST_Request $request Incoming request data.
	 * @return WP_REST_Response Response with MCP tools or empty array.
	 */
	public function mcp_tools_list( WP_REST_Request $request ) {
		$tools = array();

		// Try to get tools from MCP registry
		if ( function_exists( 'wp_mcp_get_tools' ) ) {
			$raw_tools = wp_mcp_get_tools();
			if ( is_array( $raw_tools ) ) {
				$tools = array_values( $raw_tools );
			}
		} elseif ( class_exists( 'WordPress_MCP\Server' ) ) {
			// Alternative detection for different MCP versions
			try {
				$server = \WordPress_MCP\Server::get_instance();
				if ( method_exists( $server, 'get_tools' ) ) {
					$raw_tools = $server->get_tools();
					if ( is_array( $raw_tools ) ) {
						$tools = array_values( $raw_tools );
					}
				}
			} catch ( Exception $e ) {
				return new WP_REST_Response(
					array(
						'error'   => 'MCP server not available',
						'message' => $e->getMessage(),
					),
					500
				);
			}
		}

		return new WP_REST_Response(
			array(
				'tools' => $tools,
				'count' => count( $tools ),
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
		$tool_name = $request->get_param( 'tool' );
		$arguments = $request->get_param( 'arguments' ) ?? array();

		if ( empty( $tool_name ) ) {
			return new WP_REST_Response(
				array(
					'error'   => 'Missing tool parameter',
					'message' => 'Tool name is required',
				),
				400
			);
		}

		// Check if MCP is active
		if ( ! class_exists( 'WordPress_MCP' ) ) {
			return new WP_REST_Response(
				array(
					'error'   => 'MCP not active',
					'message' => 'wordpress-mcp plugin is not active',
				),
				503
			);
		}

		try {
			// Try to execute tool using MCP registry
			if ( function_exists( 'wp_mcp_call_tool' ) ) {
				$result = wp_mcp_call_tool( $tool_name, $arguments );
				return new WP_REST_Response(
					array(
						'result' => $result,
						'tool'   => $tool_name,
					)
				);
			} elseif ( class_exists( 'WordPress_MCP\Server' ) ) {
				// Alternative execution for different MCP versions
				$server = \WordPress_MCP\Server::get_instance();
				if ( method_exists( $server, 'call_tool' ) ) {
					$result = $server->call_tool( $tool_name, $arguments );
					return new WP_REST_Response(
						array(
							'result' => $result,
							'tool'   => $tool_name,
						)
					);
				}
			}

			return new WP_REST_Response(
				array(
					'error'   => 'Tool execution not supported',
					'message' => 'MCP tool execution method not found',
				),
				501
			);
		} catch ( Exception $e ) {
			return new WP_REST_Response(
				array(
					'error'   => 'Tool execution failed',
					'message' => $e->getMessage(),
					'tool'    => $tool_name,
				),
				500
			);
		}
	}
}
