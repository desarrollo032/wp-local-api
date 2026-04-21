<?php
/**
 * MCP REST Server - /wp-json/mcp/v1/ endpoints.
 */

defined('ABSPATH') || exit;

namespace WP_MCP_Control;

class MCP_REST_Server extends \WP_REST_Controller {
    public function __construct() {
        parent::__construct('mcp', 'v1');
        $this->namespace = 'mcp/v1';
    }

    public function init() {
        register_rest_route($this->namespace, '/tools', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_tools'],
                'permission_callback' => [$this, 'auth_callback'],
            ],
        ]);

        register_rest_route($this->namespace, '/call', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'call_tool'],
                'permission_callback' => [$this, 'auth_callback'],
                'args' => [
                    'tool' => ['required' => true, 'type' => 'string'],
                    'arguments' => ['required' => true, 'type' => 'object'],
                ],
            ],
        ]);

        // Auth endpoints
        register_rest_route($this->namespace, '/tokens', [
            // POST create, GET list, DELETE revoke (admin only)
            'permission_callback' => function() { return current_user_can('manage_options'); },
        ]);
    }

    public function get_tools(\WP_REST_Request $request) {
        $token_data = MCP_Auth::get_instance()->verify_token($request);
        $tools = MCP_Registry::get_instance()->get_mcp_tools($token_data['token_hash'] ?? null);
        return rest_ensure_response(['tools' => $tools]);
    }

    public function call_tool(\WP_REST_Request $request) {
        $token_data = MCP_Auth::get_instance()->verify_token($request);
        if (!$token_data) {
            return new \WP_Error('mcp_unauthorized', 'Invalid token', ['status' => 401]);
        }

        // Rate limit check (transients)
        $rate_key = 'mcp_rate_' . $token_data['token_hash'];
        $calls = get_transient($rate_key) ?: 0;
        if ($calls >= 60) {
            return new \WP_Error('mcp_rate_limited', 'Rate limit exceeded', ['status' => 429]);
        }
        set_transient($rate_key, $calls + 1, 60); // 60/min

        $tool_name = sanitize_key($request['tool']);
        $args = $request['arguments'];

        // Resolve feature & check perms
        $perms = MCP_Permissions::get_instance();
        if (!$perms->can($token_data['token_hash'], $tool_name, 'execute')) { // Map action
            return new \WP_Error('mcp_forbidden', 'Insufficient permissions', ['status' => 403]);
        }

        $feature = wp_feature_registry()->find($tool_name . '-tool'); // Assume naming
        if (!$feature) {
            return new \WP_Error('mcp_tool_not_found', 'Tool not found', ['status' => 404]);
        }

        // Override permission_callback with token perms
        add_filter('wp_feature_permission_callback', function($cb) use ($token_data, $perms, $tool_name) {
            return function() use ($token_data, $perms, $tool_name) { return true; }; // Token already checked
        }, 10, 1);

        $result = $feature->call($request);

        // Log activity
        $this->log_activity($token_data['id'], $tool_name, $args, $result);

        return rest_ensure_response($result);
    }

    private function auth_callback(\WP_REST_Request $request) {
        return MCP_Auth::get_instance()->verify_token($request) !== null;
    }

    private function log_activity(string $token_id, string $tool, array $args, $result): void {
        $logs = get_option('wp_mcp_logs', []);
        $logs[] = [
            'token_id' => $token_id,
            'tool' => $tool,
            'args' => wp_json_encode($args),
            'result' => wp_json_encode($result),
            'timestamp' => current_time('mysql'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'success' => !is_wp_error($result),
        ];
        update_option('wp_mcp_logs', array_slice($logs, -1000)); // Last 1000
    }
}
?>

