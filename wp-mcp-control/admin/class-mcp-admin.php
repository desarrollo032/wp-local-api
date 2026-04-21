<?php
/**
 * MCP Admin Page.
 */

defined('ABSPATH') || exit;

namespace WP_MCP_Control;

class MCP_Admin {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function init(): void {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function add_admin_menu(): void {
        add_menu_page(
            'WP MCP Control',
            'MCP Control',
            'manage_options',
            'wp-mcp-control',
            [$this, 'render_admin_page'],
            'dashicons-admin-tools',
            80
        );
    }

    public function enqueue_admin_scripts(string $hook): void {
        if ('toplevel_page_wp-mcp-control' !== $hook) return;

        // Enqueue React build
        wp_enqueue_script(
            'mcp-admin',
            WP_MCP_CONTROL_URL . 'admin/build/index.js',
            ['wp-element', 'wp-api-fetch', 'wp-components', 'wp-i18n'],
            WP_MCP_CONTROL_VERSION,
            true
        );
        wp_enqueue_style('mcp-admin-style', WP_MCP_CONTROL_URL . 'admin/build/index.css', [], WP_MCP_CONTROL_VERSION);

        // Localize REST URLs
        wp_localize_script('mcp-admin', 'mcpAdminData', [
            'root' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
            'tokensUrl' => rest_url('mcp/v1/tokens'),
            'permissionsSchema' => MCP_Permissions::get_schema(),
            'siteUrl' => site_url(),
        ]);
    }

    public function render_admin_page(): void {
        ?>
        <div class="wrap mcp-admin">
            <h1><?php esc_html_e('WP MCP Control', 'wp-mcp-control'); ?></h1>
            <div id="mcp-admin-root"></div>
        </div>
        <?php
    }
}
?>

