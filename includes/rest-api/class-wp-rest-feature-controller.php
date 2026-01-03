<?php
/**
 * REST API controller for features.
 *
 * @package WP_Feature_API
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controller class for the Feature API REST endpoints.
 *
 * @since 0.1.0
 */
class WP_REST_Feature_Controller extends WP_REST_Controller {

	/**
	 * Default fields to include on feature responses.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	private $default_fields = array( 'id', 'name', 'description', 'type', 'categories', 'metadata', 'input_schema', 'output_schema' );

	/**
	 * Path for the run endpoint.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private $run_path = 'run';

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = 'wp/v2';
		$this->rest_base = 'features';

		add_filter( 'rest_authentication_errors', array( $this, 'authenticate_cookie' ) );
	}

	/**
	 * Authenticate using cookies.
	 *
	 * @param WP_Error|null|bool $result Error from another authentication handler,
	 *                                   null if we should handle it, or another value if not.
	 * @return WP_Error|null|bool
	 */
	public function authenticate_cookie( $result ) {
		if ( ! empty( $result ) ) {
			return $result;
		}

		if ( is_user_logged_in() ) {
			return true;
		}

		return $result;
	}

	/**
	 * Registers the routes for the Feature API.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		// Register GET endpoint for retrieving all features with pagination.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_items_schema' ),
			)
		);

		// Register GET endpoint for retrieving feature categories.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/categories',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_categories' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'schema'              => WP_Feature_Category::get_schema(),
				),
			)
		);

		// Register GET endpoint for retrieving a single feature category.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/categories/(?P<id>[\w-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_category' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description'       => __( 'Unique identifier for the feature category.', 'wp-feature-api' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
							'required'          => true,
						),
					),
					'schema' => WP_Feature_Category::get_schema(),
				),
			)
		);

		// Get features after they've been registered.
		$features = wp_feature_registry()->get();
		foreach ( $features as $feature ) {
			$this->register_feature_routes( $feature );
		}
	}

	/**
	 * Retrieves a collection of features.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$query_params = $request->get_query_params();
		
		// Sanitize query parameters
		$sanitized_params = array();
		foreach ( $query_params as $key => $value ) {
			$sanitized_params[ sanitize_key( $key ) ] = sanitize_text_field( $value );
		}
		
		$query = new WP_Feature_Query( $sanitized_params );
		$features = wp_feature_registry()->get( $query );

		// Handle pagination with validation
		$page     = max( 1, absint( $request['page'] ?? 1 ) );
		$per_page = min( 100, max( 1, absint( $request['per_page'] ?? 10 ) ) );
		$offset   = ( $page - 1 ) * $per_page;

		$total_features = count( $features );
		$max_pages      = ceil( $total_features / $per_page );

		// Apply pagination.
		$features = array_slice( $features, $offset, $per_page );

		$data = array();
		foreach ( $features as $feature ) {
			$item   = $this->prepare_item_for_response( $feature, $request );
			$data[] = $this->prepare_response_for_collection( $item );
		}

		$response = rest_ensure_response( $data );

		// Add pagination headers.
		$response->header( 'X-WP-Total', (string) $total_features );
		$response->header( 'X-WP-TotalPages', (string) $max_pages );

		// Add pagination links.
		$request_params = $request->get_query_params();
		$base = add_query_arg( 
			urlencode_deep( array_map( 'sanitize_text_field', $request_params ) ), 
			rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ) 
		);

		if ( $page > 1 ) {
			$prev_page = $page - 1;
			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}

		if ( $page < $max_pages ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	/**
	 * Checks if a given request has access to read features.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'read' ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to view features.', 'wp-feature-api' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		
		return true;
	}

	/**
	 * Prepares a feature for response.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Feature      $feature The feature object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $feature, $request ) {
		$data = $this->transform_feature_data( $feature, $request );

		$response = rest_ensure_response( $data );
		$links = $this->get_links( $feature );
		if ( ! empty( $links ) ) {
			$response->add_links( $links );
		}

		/**
		 * Filters the feature data for a REST API response.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WP_Feature       $feature  The original feature object.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'rest_prepare_feature', $response, $feature, $request );
	}

	/**
	 * Retrieves the query params for collections.
	 *
	 * @since 0.1.0
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		$base = array(
			'page'     => array(
				'description'       => __( 'Current page of the collection.', 'wp-feature-api' ),
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'minimum'           => 1,
			),
			'per_page' => array(
				'description'       => __( 'Maximum number of items to be returned in result set.', 'wp-feature-api' ),
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'fields' => array(
				'description'       => __( 'Limit response to specific fields. Defaults to all fields.', 'wp-feature-api' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);

		$schema = array_merge( $base, WP_Feature_Query::schema() );

		return $schema;
	}

	/**
	 * Retrieves the query params for feature queries.
	 *
	 * @since 0.1.0
	 *
	 * @return array Query parameters.
	 */
	public function get_query_params() {
		return array_merge(
			$this->get_collection_params(),
			array(
				'type'     => array(
					'description'       => __( 'Limit results to features of a specific type.', 'wp-feature-api' ),
					'type'              => 'string',
					'enum'              => array( 'resource', 'tool' ),
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'category' => array(
					'description'       => __( 'Limit results to features in a specific category.', 'wp-feature-api' ),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'location' => array(
					'description'       => __( 'Limit results to features with a specific location.', 'wp-feature-api' ),
					'type'              => 'string',
					'enum'              => array( 'server', 'client' ),
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				),
			)
		);
	}

	/**
	 * Retrieves the schema for a single feature item.
	 *
	 * @since 0.1.0
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->schema;
		}

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'feature',
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the feature.', 'wp-feature-api' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'name' => array(
					'description' => __( 'The name of the feature.', 'wp-feature-api' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'description' => array(
					'description' => __( 'The description of the feature.', 'wp-feature-api' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'type' => array(
					'description' => __( 'The type of the feature (resource or tool).', 'wp-feature-api' ),
					'type'        => 'string',
					'enum'        => array( 'resource', 'tool' ),
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'categories' => array(
					'description' => __( 'The categories that the feature belongs to.', 'wp-feature-api' ),
					'type'        => 'array',
					'items'       => array(
						'type' => 'string',
					),
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'location' => array(
					'description' => __( 'Where the feature is executed (server or client).', 'wp-feature-api' ),
					'type'        => 'string',
					'enum'        => array( 'server', 'client' ),
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'input_schema' => array(
					'description' => __( 'JSON Schema defining the input parameters for the feature.', 'wp-feature-api' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'output_schema' => array(
					'description' => __( 'JSON Schema defining the output format of the feature.', 'wp-feature-api' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'meta' => array(
					'description' => __( 'Additional metadata associated with the feature.', 'wp-feature-api' ),
					'type'        => 'object',
					'properties'  => array(),
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
			),
			'required' => array( 'id', 'name', 'description', 'type' ),
		);

		return $this->schema;
	}

	/**
	 * Retrieves the schema for features collection.
	 *
	 * @since 0.1.0
	 *
	 * @return array Collection schema data.
	 */
	public function get_items_schema() {
		$schema = $this->get_item_schema();

		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'features',
			'type'       => 'array',
			'items'      => $schema,
			'properties' => array(
				'_links' => array(
					'type'        => 'object',
					'description' => __( 'Links related to the collection.', 'wp-feature-api' ),
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Transforms feature data for the response.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Feature      $feature The feature object.
	 * @param WP_REST_Request $request The request object.
	 * @return array Transformed feature data.
	 */
	private function transform_feature_data( $feature, $request ) {
		$data = array_filter( $feature->to_array() );

		$fields = $this->default_fields;
		if ( ! empty( $request['fields'] ) ) {
			$requested_fields = array_map( 'trim', explode( ',', sanitize_text_field( $request['fields'] ) ) );
			$fields = array_unique( array_filter( $requested_fields ) );
		}

		return array_intersect_key( $data, array_flip( $fields ) );
	}

	/**
	 * Retrieves the links for a feature.
	 * Helps with discoverability of the feature and its alternate types.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Feature $feature The feature object.
	 * @return array The links for the feature.
	 */
	private function get_links( $feature ) {
		$links = array(
			'self' => array(
				'href' => $this->get_feature_url( $feature ),
			),
			'run' => array(
				array(
					'href'   => $this->get_feature_run_url( $feature ),
					'method' => $feature->get_rest_method(),
				),
			),
		);

		// Add related links for other feature types with the same ID.
		$alternate_features = $feature->get_alternate_types();
		if ( $alternate_features ) {
			foreach ( $alternate_features as $alternate_feature ) {
				$links['related'][] = array(
					'href'   => $this->get_feature_url( $alternate_feature ),
					'method' => 'GET',
				);

				$links['related'][] = array(
					'href'   => $this->get_feature_run_url( $alternate_feature ),
					'method' => $alternate_feature->get_rest_method(),
				);
			}
		}

		return $links;
	}

	/**
	 * Retrieves the base resource path for a feature.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Feature $feature The feature object.
	 * @return string The base path for the feature.
	 */
	private function get_base_path( $feature ) {
		if ( $feature ) {
			return sprintf( '%s/%s/%s', $this->namespace, $this->rest_base, sanitize_text_field( $feature->get_id() ) );
		}

		return sprintf( '%s/%s', $this->namespace, $this->rest_base );
	}

	/**
	 * Retrieves the URL for a feature.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Feature $feature The feature object.
	 * @return string The URL for the feature.
	 */
	private function get_feature_url( $feature ) {
		return rest_url( $this->get_base_path( $feature ) );
	}

	/**
	 * Retrieves the URL for the run endpoint of a feature.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Feature $feature The feature object.
	 * @return string The URL for the run endpoint of the feature.
	 */
	private function get_feature_run_url( $feature ) {
		return $this->get_feature_url( $feature ) . '/' . $this->run_path;
	}

	/**
	 * Registers the routes for a feature.
	 * Includes the run endpoint and the GET endpoint for the feature.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Feature $feature The feature object.
	 */
	private function register_feature_routes( $feature ) {
		$resource_base = '/' . $this->rest_base . '/' . sanitize_text_field( $feature->get_id() );

		// Register run endpoint for executing features.
		register_rest_route(
			$this->namespace,
			$resource_base . '/' . $this->run_path,
			array(
				array(
					'methods'             => $feature->get_rest_method(),
					'callback'            => function ( $request ) use ( $feature ) {
						$result = $feature->call( $request );
						return rest_ensure_response( $result );
					},
					'permission_callback' => array( $feature, 'get_permission_callback' ),
					'args'                => $feature->get_input_schema(),
				),
				'schema' => array( $feature, 'get_output_schema' ),
			)
		);

		// Register GET endpoint for retrieving a specific feature by ID.
		register_rest_route(
			$this->namespace,
			$resource_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => function ( $request ) use ( $feature ) {
						$data = $this->prepare_item_for_response( $feature, $request );
						return rest_ensure_response( $data );
					},
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieves the feature categories.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_categories( $request ) {
		$categories = wp_feature_registry()->get_categories();
		
		if ( ! is_array( $categories ) ) {
			return new WP_Error(
				'rest_feature_categories_error',
				__( 'Unable to retrieve feature categories.', 'wp-feature-api' ),
				array( 'status' => 500 )
			);
		}
		
		return rest_ensure_response( $categories );
	}

	/**
	 * Retrieves a single feature category.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_category( $request ) {
		$id = sanitize_text_field( $request['id'] );
		
		if ( empty( $id ) ) {
			return new WP_Error(
				'rest_missing_callback_param',
				__( 'Missing category ID.', 'wp-feature-api' ),
				array( 'status' => 400 )
			);
		}
		
		$category = wp_feature_registry()->get_category( $id );

		if ( ! $category ) {
			return new WP_Error( 
				'rest_feature_category_not_found', 
				__( 'Feature category not found.', 'wp-feature-api' ), 
				array( 'status' => 404 ) 
			);
		}
		
		return rest_ensure_response( $category->to_array() );
	}
}
