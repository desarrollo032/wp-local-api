<?php
/**
 * Plugin Name: WP Feature API - AI Agent Proxy
 * Plugin URI: https://github.com/Automattic/wp-feature-api
 * Description: Provides a REST API proxy for interacting with external AI services and includes a demo chat interface.
 * Version: 0.1.14
 * Author: Automattic AI
 * Author URI: https://automattic.ai/
 * Text Domain: wp-feature-api-agent
 * License: GPL-2.0-or-later
 * License URI: https://spdx.org/licenses/GPL-2.0-or-later.html
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package WordPress\Feature_API_Agent
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP_AI_API_PROXY_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_AI_API_PROXY_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_AI_API_PROXY_VERSION', '0.1.11' );

// Include the main proxy class.
require_once WP_AI_API_PROXY_PATH . 'includes/class-wp-ai-api-proxy.php';

// Include the options class.
require_once WP_AI_API_PROXY_PATH . 'includes/class-wp-ai-api-options.php';

// Include the feature registration class.
require_once WP_AI_API_PROXY_PATH . 'includes/class-wp-feature-register.php';

$proxy_instance = new A8C\WpFeatureApiAgent\WP_AI_API_Proxy();
$proxy_instance->register_hooks();

$options_instance = new A8C\WpFeatureApiAgent\WP_AI_API_Options();
$options_instance->init();

// Register additional demo features.
$feature_register_instance = new A8C\WpFeatureApiAgent\WP_Feature_Register();
// Only register features if the main wp-feature-api plugin is active
if ( function_exists( 'wp_register_feature' ) ) {
	add_action( 'wp_feature_api_init', array( $feature_register_instance, 'register_features' ) );
} else {
	// Log a warning if WP_DEBUG is enabled
	if ( WP_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
		trigger_error( 'WP Feature API plugin not found. Some features may not be available.', E_USER_NOTICE );
	}
}

/**
 * Enqueues scripts and styles for the admin area.
 *
 * @since 0.1.11
 * @return void
 */
function wp_feature_api_agent_enqueue_assets() {
	// Only load in admin area
	if ( ! is_admin() ) {
		return;
	}

	$script_asset_path = WP_AI_API_PROXY_PATH . 'build/index.asset.php';
	if ( ! file_exists( $script_asset_path ) ) {
		if ( WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			trigger_error( 'AI Agent assets not found. Please run the build process.', E_USER_WARNING );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'WP Feature API Agent: Asset file not found at: ' . $script_asset_path );
		}
		return;
	}
	
	$script_asset = require $script_asset_path;

	// Validate asset structure
	if ( ! is_array( $script_asset ) || ! isset( $script_asset['dependencies'] ) || ! isset( $script_asset['version'] ) ) {
		if ( WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			trigger_error( 'Invalid AI Agent asset structure.', E_USER_WARNING );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'WP Feature API Agent: Invalid asset structure: ' . print_r( $script_asset, true ) );
		}
		return;
	}

	// Enqueue the main script.
	$dependencies = $script_asset['dependencies'];

	if ( WP_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'WP Feature API Agent: Enqueuing assets successfully' );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'WP Feature API Agent: Script URL: ' . WP_AI_API_PROXY_URL . 'build/index.js' );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'WP Feature API Agent: Style URL: ' . WP_AI_API_PROXY_URL . 'build/style-index.css' );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'WP Feature API Agent: Dependencies: ' . print_r( $dependencies, true ) );
	}
	
	// Only add wp-features dependency if the main plugin is active
	if ( function_exists( 'wp_register_feature' ) ) {
		$dependencies[] = 'wp-features';
	}
	
	wp_enqueue_script(
		'wp-feature-api-agent-script',
		WP_AI_API_PROXY_URL . 'build/index.js',
		$dependencies,
		$script_asset['version'],
		array( 'in_footer' => true )
	);

	// Note: wp-scripts names the CSS file based on the importing JS/TS entry point (index.tsx -> style-index.css)
	// Only enqueue wp-components CSS if it's not already loaded by core.
	if ( ! wp_style_is( 'wp-components', 'enqueued' ) ) {
		wp_enqueue_style(
			'wp-components',
			includes_url( 'css/dist/components/style.min.css' ),
			array(),
			$script_asset['version']
		);
	}

	wp_enqueue_style(
		'wp-feature-api-agent-style',
		WP_AI_API_PROXY_URL . 'build/style-index.css',
		array( 'wp-components' ),
		$script_asset['version']
	);
}
add_action( 'admin_enqueue_scripts', 'wp_feature_api_agent_enqueue_assets' );

/**
 * Adds the root container div to the admin footer.
 *
 * @since 0.1.11
 * @return void
 */
function wp_feature_api_agent_add_root_container() {
	// Only show to users who can manage options
	if ( ! current_user_can( 'manage_options' ) ) {
		if ( WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'WP Feature API Agent: User does not have manage_options capability' );
		}
		return;
	}
	
	if ( WP_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'WP Feature API Agent: Adding chat container to admin footer' );
	}
	?>
	<div id="wp-feature-api-agent-chat"></div>
	<?php
}
add_action( 'admin_footer', 'wp_feature_api_agent_add_root_container' );