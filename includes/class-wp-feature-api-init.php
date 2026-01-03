<?php
/**
 * WordPress Feature API Initialization
 *
 * @package WordPress\Feature_API
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the initialization of WordPress Feature API components.
 */
class WP_Feature_API_Init {

	/**
	 * Initializes the WordPress Feature API core components.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function initialize() {

		// Run the wp_feature_api_init action after the regular REST routes are registered.
		add_action( 'rest_api_init', array( __CLASS__, 'run_init_action' ), 110 );
		add_action( 'parse_request', array( __CLASS__, 'run_init_action' ), 11 );

		// Register REST routes after the regular REST routes are registered
		// (at rest_api_init:99 action which is triggered by parse_request:10 action).
		// This ensures that the Feature API won't trigger creating the global WP rest api server
		// too early (during the `init` action).
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ), 120 );

		// enqueue admin scripts.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_scripts' ) );

		// Load demo plugin if enabled.
		if ( defined( 'WP_FEATURE_API_LOAD_DEMO' ) && WP_FEATURE_API_LOAD_DEMO ) {
			self::load_agent_demo();
		}
	}

	/**
	 * Enqueues admin scripts.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function enqueue_admin_scripts() {
		if ( ! is_admin() ) {
			return;
		}

		$build_path = WP_FEATURE_API_PLUGIN_DIR . 'build/index.asset.php';
		$build_url  = WP_FEATURE_API_PLUGIN_URL . 'build/index.js';

		// Check for distribution structure (client build moved to build/client)
		if ( file_exists( WP_FEATURE_API_PLUGIN_DIR . 'build/client/index.asset.php' ) ) {
			$build_path = WP_FEATURE_API_PLUGIN_DIR . 'build/client/index.asset.php';
			$build_url  = WP_FEATURE_API_PLUGIN_URL . 'build/client/index.js';
		} elseif ( file_exists( WP_FEATURE_API_PLUGIN_DIR . 'packages/client/build/index.asset.php' ) ) {
			// Check for repository structure (development)
			$build_path = WP_FEATURE_API_PLUGIN_DIR . 'packages/client/build/index.asset.php';
			$build_url  = WP_FEATURE_API_PLUGIN_URL . 'packages/client/build/index.js';
		}

		// Check for the file before requiring it.
		if ( ! file_exists( $build_path ) ) {
			if ( WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				trigger_error( 'Assets file not found, please run the build for the WordPress Feature API plugin.', E_USER_WARNING );
			}
			return;
		}
		
		$assets = require $build_path;
		
		// Validate assets structure
		if ( ! is_array( $assets ) || ! isset( $assets['dependencies'] ) || ! isset( $assets['version'] ) ) {
			if ( WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				trigger_error( 'Invalid assets file structure for WordPress Feature API plugin.', E_USER_WARNING );
			}
			return;
		}
		
		wp_enqueue_script( 
			'wp-features', 
			$build_url, 
			$assets['dependencies'], 
			$assets['version'], 
			array( 'in_footer' => true )
		);

	}

	/**
	 * Runs the wp_feature_api_init action.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function run_init_action() {
		static $has_run = false;

		if ( ! $has_run ) {
			$has_run = true;
			
			/**
			 * Fires when the WordPress Feature API is initialized.
			 *
			 * @since 0.1.0
			 */
			do_action( 'wp_feature_api_init' );
		}
	}

	/**
	 * Registers the REST API routes for the Feature API.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function register_rest_routes() {
		$controller = new WP_REST_Feature_Controller();
		$controller->register_routes();
	}

	/**
	 * Loads the WP Feature API Demo plugin.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function load_agent_demo() {
		// Default path (old or potential future structure).
		$demo_plugin_file = WP_FEATURE_API_PLUGIN_DIR . 'demo/wp-feature-api-agent/wp-feature-api-agent.php';

		// Repository structure (development).
		if ( file_exists( WP_FEATURE_API_PLUGIN_DIR . 'packages/demo-agent/wp-feature-api-agent.php' ) ) {
			$demo_plugin_file = WP_FEATURE_API_PLUGIN_DIR . 'packages/demo-agent/wp-feature-api-agent.php';
		}

		if ( file_exists( $demo_plugin_file ) ) {
			require_once $demo_plugin_file;

			// Notify admin that demo plugin is loaded if in admin area.
			if ( is_admin() ) {
				add_action( 'admin_notices', array( __CLASS__, 'demo_loaded_notice' ) );
			}
		}
	}

	/**
	 * Displays an admin notice when the demo plugin is loaded.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function demo_loaded_notice() {
		// Only show to users who can manage options
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<?php
				printf(
					/* translators: %s: WP_FEATURE_API_LOAD_DEMO constant */
					esc_html__( 'WordPress Feature API Demo plugin is loaded. To disable it, set %s to false in your wp-config.php file.', 'wp-feature-api' ),
					'<code>WP_FEATURE_API_LOAD_DEMO</code>'
				);
				?>
			</p>
		</div>
		<?php
	}
}

WP_Feature_API_Init::initialize();
