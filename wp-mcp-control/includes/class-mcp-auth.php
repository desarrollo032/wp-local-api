<?php
/**
 * MCP Auth Class - Token generation and verification.
 */

defined('ABSPATH') || exit;

namespace WP_MCP_Control;

class MCP_Auth {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    /**
     * Generate new token.
     * Returns plain token (shown ONCE), stores hashed.
     */
    public function generate_token(string $name, ?string $expires = null, array $permissions = []): ?array {
        $plain_token = bin2hex(random_bytes(32));
        $token_id = sanitize_key($name . '-' . time());
        $hashed_token = wp_hash($plain_token);

        $tokens = get_option('wp_mcp_tokens', []);
        $tokens[$token_id] = [
            'id' => $token_id,
            'name' => sanitize_text_field($name),
            'token_hash' => $hashed_token,
            'token_plain' => $plain_token, // Remove after first show
            'created' => current_time('mysql'),
            'expires' => $expires ?? date('Y-m-d H:i:s', strtotime('+30 days')),
            'permissions' => $permissions,
            'last_used' => null,
            'ip_whitelist' => [],
        ];

        if (update_option('wp_mcp_tokens', $tokens)) {
            // Cleanup plain token for security
            unset($tokens[$token_id]['token_plain']);
            update_option('wp_mcp_tokens', $tokens);
            return [
                'id' => $token_id,
                'plain_token' => $plain_token, // Show ONLY once!
                'url' => rest_url('mcp/v1/'),
            ];
        }
        return null;
    }

    /**
     * Revoke token.
     */
    public function revoke_token(string $token_id): bool {
        $tokens = get_option('wp_mcp_tokens', []);
        if (!isset($tokens[$token_id])) return false;
        unset($tokens[$token_id]);
        return update_option('wp_mcp_tokens', $tokens);
    }

    /**
     * Verify token from Bearer header.
     * Returns token_data or false.
     */
    public function verify_token(WP_REST_Request $request): ?array {
        $auth_header = $request->get_header('authorization');
        if (!$auth_header || !preg_match('/Bearer\s+(\S+)/', $auth_header, $matches)) {
            return null;
        }
        $token_hash = $matches[1];

        $perms = MCP_Permissions::get_instance();
        $token_data = $perms->get_token_data ? $perms->get_token_data($token_hash) : null; // Reuse perms method
        
        if ($token_data && !$perms->is_expired($token_data)) {
            $token_data['token_hash'] = $token_hash;
            $this->update_last_used($token_data['id']);
            return $token_data;
        }
        return null;
    }

    private function update_last_used(string $token_id): void {
        $tokens = get_option('wp_mcp_tokens', []);
        if (isset($tokens[$token_id])) {
            $tokens[$token_id]['last_used'] = current_time('mysql');
            update_option('wp_mcp_tokens', $tokens);
        }
    }
}
?>

