<?php
/**
 * Options class for the AI API Proxy & WP Feature Agent Demo.
 *
 * @package WordPress\Feature_API_Agent
 */

namespace A8C\WpFeatureApiAgent;

/**
 * Handles the settings page for the AI API Proxy & WP Feature Agent Demo.
 */
class WP_AI_API_Options {

	/**
	 * Option name for OpenAI API key.
	 *
	 * @var string
	 */
	const OPENAI_OPTION_NAME = 'wp_ai_api_proxy_openai_key';

	/**
	 * Option name for OpenRouter API key.
	 *
	 * @var string
	 */
	const OPENROUTER_OPTION_NAME = 'wp_ai_api_proxy_openrouter_key';

	/**
	 * Option name for OpenRouter API host (optional override).
	 *
	 * @var string
	 */
	const OPENROUTER_HOST_OPTION = 'wp_ai_api_proxy_openrouter_host';

	/**
	 * Option name for selected provider.
	 *
	 * @var string
	 */
	const PROVIDER_OPTION_NAME = 'wp_ai_api_proxy_provider';

	/**
	 * Option name for WordPress MCP authentication token.
	 *
	 * @var string
	 */
	const MCP_TOKEN_OPTION_NAME = 'wp_ai_api_proxy_mcp_token';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const OPTION_PAGE = 'wp-ai-api-proxy-settings';

	/**
	 * Initializes the options page.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
		add_action( 'admin_head', array( $this, 'inject_custom_styles' ) );
	}

	/**
	 * Adds the options page to the admin menu.
	 */
	public function add_options_page() {
		add_menu_page(
			__( 'WP Feature Agent Demo - Settings', 'wp-feature-api-agent' ),
			__( 'Agent Demo', 'wp-feature-api-agent' ),
			'manage_options',
			self::OPTION_PAGE,
			array( $this, 'render_options_page' ),
			'dashicons-superhero',
			30
		);
	}

	/**
	 * Registers the settings.
	 */
	public function register_settings() {
		// Register settings for API keys.
		register_setting(
			self::OPTION_PAGE,
			self::OPENAI_OPTION_NAME,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			self::OPTION_PAGE,
			self::OPENROUTER_OPTION_NAME,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			self::OPTION_PAGE,
			self::OPENROUTER_HOST_OPTION,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
			)
		);

		register_setting(
			self::OPTION_PAGE,
			self::PROVIDER_OPTION_NAME,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'openai',
			)
		);

		register_setting(
			self::OPTION_PAGE,
			self::MCP_TOKEN_OPTION_NAME,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		add_settings_section(
			'wp_ai_api_proxy_api_section',
			__( 'AI API Settings', 'wp-feature-api-agent' ),
			array( $this, 'render_api_section_description' ),
			self::OPTION_PAGE
		);

		add_settings_field(
			'provider_select',
			__( 'AI Provider', 'wp-feature-api-agent' ),
			array( $this, 'render_provider_select_field' ),
			self::OPTION_PAGE,
			'wp_ai_api_proxy_api_section'
		);

		add_settings_field(
			'openai_api_key',
			__( 'OpenAI API Key', 'wp-feature-api-agent' ),
			array( $this, 'render_openai_api_key_field' ),
			self::OPTION_PAGE,
			'wp_ai_api_proxy_api_section'
		);

		add_settings_field(
			'openrouter_api_key',
			__( 'OpenRouter API Key', 'wp-feature-api-agent' ),
			array( $this, 'render_openrouter_api_key_field' ),
			self::OPTION_PAGE,
			'wp_ai_api_proxy_api_section'
		);

		add_settings_field(
			'openrouter_api_host',
			__( 'OpenRouter API Host (optional)', 'wp-feature-api-agent' ),
			array( $this, 'render_openrouter_api_host_field' ),
			self::OPTION_PAGE,
			'wp_ai_api_proxy_api_section'
		);

		add_settings_section(
			'wp_ai_api_proxy_mcp_section',
			__( 'WordPress MCP Settings', 'wp-feature-api-agent' ),
			array( $this, 'render_mcp_section_description' ),
			self::OPTION_PAGE
		);

		add_settings_field(
			'mcp_token',
			__( 'MCP Authentication Token', 'wp-feature-api-agent' ),
			array( $this, 'render_mcp_token_field' ),
			self::OPTION_PAGE,
			'wp_ai_api_proxy_mcp_section'
		);
	}

	/**
	 * Renders the API section description.
	 */
	public function render_api_section_description() {
		echo '<p>' . esc_html__( 'Configure your API keys for the AI services you want to use.', 'wp-feature-api-agent' ) . '</p>';
	}

	/**
	 * Renders the MCP section description.
	 */
	public function render_mcp_section_description() {
		?>
		<p><?php esc_html_e( 'Configure authentication for the WordPress MCP server to enable AI-powered WordPress management.', 'wp-feature-api-agent' ); ?></p>
		<p class="description">
			<?php
			printf(
				/* translators: %s: URL to wordpress-mcp plugin */
				esc_html__( 'Requires the %s plugin to be installed and activated.', 'wp-feature-api-agent' ),
				'<a href="https://github.com/Automattic/wordpress-mcp" target="_blank">wordpress-mcp</a>'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Renders the OpenAI API key field.
	 */
	public function render_openai_api_key_field() {
		$value = get_option( self::OPENAI_OPTION_NAME );
		?>
		<input type="password"
			   name="<?php echo esc_attr( self::OPENAI_OPTION_NAME ); ?>"
			   value="<?php echo esc_attr( $value ); ?>"
			   class="regular-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Enter your OpenAI API key.', 'wp-feature-api-agent' ); ?>
		</p>
		<?php
	}

	/**
	 * Renders the provider select field.
	 */
	public function render_provider_select_field() {
		$value = get_option( self::PROVIDER_OPTION_NAME, 'openai' );
		?>
		<select name="<?php echo esc_attr( self::PROVIDER_OPTION_NAME ); ?>">
			<option value="openai" <?php selected( $value, 'openai' ); ?>><?php esc_html_e( 'OpenAI', 'wp-feature-api-agent' ); ?></option>
			<option value="openrouter" <?php selected( $value, 'openrouter' ); ?>><?php esc_html_e( 'OpenRouter', 'wp-feature-api-agent' ); ?></option>
		</select>
		<p class="description">
			<?php esc_html_e( 'Select the AI provider to use for the demo proxy.', 'wp-feature-api-agent' ); ?>
		</p>
		<?php
	}

	/**
	 * Renders the OpenRouter API key field.
	 */
	public function render_openrouter_api_key_field() {
		$value = get_option( self::OPENROUTER_OPTION_NAME );
		?>
		<input type="password"
			   name="<?php echo esc_attr( self::OPENROUTER_OPTION_NAME ); ?>"
			   value="<?php echo esc_attr( $value ); ?>"
			   class="regular-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Enter your OpenRouter API key.', 'wp-feature-api-agent' ); ?>
		</p>
		<?php
	}

	/**
	 * Renders the OpenRouter API host override field.
	 */
	public function render_openrouter_api_host_field() {
		$value = get_option( self::OPENROUTER_HOST_OPTION );
		?>
		<input type="url"
			   name="<?php echo esc_attr( self::OPENROUTER_HOST_OPTION ); ?>"
			   value="<?php echo esc_attr( $value ); ?>"
			   class="regular-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Optional: Override the OpenRouter API host (e.g., https://your-openrouter-host/v1).', 'wp-feature-api-agent' ); ?>
		</p>
		<?php
	}

	/**
	 * Renders the MCP authentication token field.
	 */
	public function render_mcp_token_field() {
		$value = get_option( self::MCP_TOKEN_OPTION_NAME );
		$mcp_active = function_exists( 'WPMCP' ) || class_exists( 'Automattic\\WordpressMcp\\Plugin' );
		?>
		<input type="password"
			   name="<?php echo esc_attr( self::MCP_TOKEN_OPTION_NAME ); ?>"
			   value="<?php echo esc_attr( $value ); ?>"
			   class="regular-text"
			   <?php echo ! $mcp_active ? 'disabled' : ''; ?>
		/>
		<?php if ( ! $mcp_active ) : ?>
			<p class="description" style="color: #d63638;">
				<strong><?php esc_html_e( 'WordPress MCP plugin is not installed or activated.', 'wp-feature-api-agent' ); ?></strong><br>
				<?php
				printf(
					/* translators: %s: URL to wordpress-mcp plugin */
					esc_html__( 'Install from: %s', 'wp-feature-api-agent' ),
					'<a href="https://github.com/Automattic/wordpress-mcp" target="_blank">https://github.com/Automattic/wordpress-mcp</a>'
				);
				?>
			</p>
		<?php else : ?>
			<p class="description">
				<?php esc_html_e( 'Enter the authentication token for the WordPress MCP server. This allows the AI to perform actions on your WordPress site.', 'wp-feature-api-agent' ); ?>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Renders the options page.
	 */
	public function render_options_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::OPTION_PAGE );
				do_settings_sections( self::OPTION_PAGE );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Injects custom futuristic styles for the settings page.
	 */
	public function inject_custom_styles() {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, self::OPTION_PAGE ) === false ) {
			return;
		}
		?>
		<style>
			/* Futuristic Settings Page Styles */
			.wrap {
				max-width: 1200px;
				margin: 40px auto;
				padding: 0 20px;
			}

			.wrap > h1 {
				font-size: 32px;
				font-weight: 700;
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				-webkit-background-clip: text;
				-webkit-text-fill-color: transparent;
				background-clip: text;
				margin-bottom: 30px;
				display: flex;
				align-items: center;
				gap: 12px;
			}

			.wrap > h1::before {
				content: '🚀';
				font-size: 36px;
				filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
			}

			.wrap > form {
				background: #ffffff;
				border-radius: 20px;
				box-shadow: 
					0 20px 60px rgba(0, 0, 0, 0.1),
					0 0 0 1px rgba(0, 0, 0, 0.05);
				padding: 40px;
				position: relative;
				overflow: hidden;
			}

			.wrap > form::before {
				content: '';
				position: absolute;
				top: 0;
				left: 0;
				right: 0;
				height: 4px;
				background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #667eea 100%);
				background-size: 200% 100%;
				animation: shimmer 3s linear infinite;
			}

			@keyframes shimmer {
				0% { background-position: -200% 0; }
				100% { background-position: 200% 0; }
			}

			/* Section Headers */
			h2.title {
				font-size: 20px;
				font-weight: 600;
				color: #1e293b;
				margin: 30px 0 20px 0;
				padding: 16px 24px;
				background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
				border-radius: 12px;
				border-left: 4px solid #667eea;
				position: relative;
				overflow: hidden;
			}

			h2.title::before {
				content: '';
				position: absolute;
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;
				background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
				transform: translateX(-100%);
				transition: transform 0.6s ease;
			}

			h2.title:hover::before {
				transform: translateX(100%);
			}

			/* Form Table */
			.form-table {
				margin-top: 0;
				border-spacing: 0 12px;
			}

			.form-table th {
				padding: 20px 16px;
				font-weight: 600;
				color: #475569;
				vertical-align: top;
				width: 200px;
			}

			.form-table td {
				padding: 20px 16px;
				background: rgba(248, 250, 252, 0.5);
				border-radius: 12px;
				transition: all 0.3s ease;
			}

			.form-table tr:hover td {
				background: rgba(241, 245, 249, 0.8);
				transform: translateX(4px);
			}

			/* Input Fields */
			.regular-text,
			select {
				padding: 12px 16px;
				border: 2px solid #e2e8f0;
				border-radius: 10px;
				font-size: 14px;
				transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
				background: white;
				box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
			}

			.regular-text:focus,
			select:focus {
				border-color: #667eea;
				outline: none;
				box-shadow: 
					0 0 0 3px rgba(102, 126, 234, 0.1),
					0 4px 12px rgba(102, 126, 234, 0.15);
				transform: translateY(-2px);
			}

			.regular-text:disabled {
				background: #f1f5f9;
				border-color: #cbd5e1;
				cursor: not-allowed;
				opacity: 0.6;
			}

			/* Descriptions */
			.description {
				color: #64748b;
				font-size: 13px;
				margin-top: 8px;
				line-height: 1.6;
			}

			.description a {
				color: #667eea;
				text-decoration: none;
				font-weight: 500;
				transition: all 0.2s ease;
			}

			.description a:hover {
				color: #764ba2;
				text-decoration: underline;
			}

			/* Submit Button */
			.submit .button-primary {
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				border: none;
				color: white;
				padding: 14px 32px;
				font-size: 15px;
				font-weight: 600;
				border-radius: 12px;
				cursor: pointer;
				transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
				box-shadow: 
					0 4px 15px rgba(102, 126, 234, 0.4),
					0 1px 3px rgba(0, 0, 0, 0.1);
				text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
				position: relative;
				overflow: hidden;
			}

			.submit .button-primary::before {
				content: '';
				position: absolute;
				top: 0;
				left: -100%;
				width: 100%;
				height: 100%;
				background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
				transition: left 0.6s ease;
			}

			.submit .button-primary:hover {
				background: linear-gradient(135deg, #7c8ff0 0%, #8b5cb8 100%);
				box-shadow: 
					0 6px 25px rgba(102, 126, 234, 0.5),
					0 2px 6px rgba(0, 0, 0, 0.15);
				transform: translateY(-2px);
			}

			.submit .button-primary:hover::before {
				left: 100%;
			}

			.submit .button-primary:active {
				transform: translateY(0);
				box-shadow: 
					0 2px 10px rgba(102, 126, 234, 0.3),
					0 1px 2px rgba(0, 0, 0, 0.1);
			}

			/* Responsive */
			@media (max-width: 768px) {
				.wrap {
					margin: 20px auto;
				}

				.wrap > form {
					padding: 24px;
					border-radius: 16px;
				}

				.form-table th,
				.form-table td {
					display: block;
					width: 100%;
					padding: 12px;
				}

				.form-table th {
					padding-bottom: 8px;
				}

				.regular-text {
					width: 100%;
				}
			}
		</style>
		<?php
	}

	/**
	 * Displays admin notices.
	 */
	public function display_admin_notices() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$provider = get_option( self::PROVIDER_OPTION_NAME, 'openai' );
		$openai_key = get_option( self::OPENAI_OPTION_NAME );
		$openrouter_key = get_option( self::OPENROUTER_OPTION_NAME );

		$missing_for_selected = false;
		if ( $provider === 'openai' && empty( $openai_key ) ) {
			$missing_for_selected = true;
		} elseif ( $provider === 'openrouter' && empty( $openrouter_key ) ) {
			$missing_for_selected = true;
		}

		if ( $missing_for_selected ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<?php
				printf(
					/* translators: %s: URL to the settings page */
					esc_html__( 'The selected AI provider requires an API key. Configure it in the %s.', 'wp-feature-api-agent' ),
					'<a href="' . esc_url( admin_url( 'options-general.php?page=' . self::OPTION_PAGE ) ) . '">' . esc_html__( 'WP Feature Agent Demo settings', 'wp-feature-api-agent' ) . '</a>'
				);
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Get the OpenAI API key.
	 *
	 * @return string The OpenAI API key.
	 */
	public static function get_openai_api_key(): string {
		return get_option( self::OPENAI_OPTION_NAME, '' );
	}

	/**
	 * Get the selected provider key.
	 *
	 * @return string Provider key string.
	 */
	public static function get_provider(): string {
		return get_option( self::PROVIDER_OPTION_NAME, 'openai' );
	}

	/**
	 * Get the OpenRouter API key.
	 *
	 * @return string The OpenRouter API key.
	 */
	public static function get_openrouter_api_key(): string {
		return get_option( self::OPENROUTER_OPTION_NAME, '' );
	}

	/**
	 * Get the OpenRouter API host override.
	 *
	 * @return string The OpenRouter API host or empty string.
	 */
	public static function get_openrouter_api_host(): string {
		return get_option( self::OPENROUTER_HOST_OPTION, '' );
	}

	/**
	 * Get the MCP authentication token.
	 *
	 * @return string The MCP token or empty string.
	 */
	public static function get_mcp_token(): string {
		return get_option( self::MCP_TOKEN_OPTION_NAME, '' );
	}
}
