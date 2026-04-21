<?php
/**
 * MCP Permissions Class
 * Granular token-based permissions matrix.
 */

defined('ABSPATH') || exit;

namespace WP_MCP_Control;

class MCP_Permissions {
    // PERMISSION_SCHEMA per task spec
    const PERMISSION_SCHEMA = [
        'content' => [
            'label' => 'Contenido',
            'tools' => ['posts', 'pages', 'blocks', 'media', 'taxonomies', 'comments'],
            'actions' => ['read', 'create', 'update', 'delete'],
        ],
        'configuration' => [
            'label' => 'Configuración',
            'tools' => ['settings', 'plugins', 'themes', 'menus', 'widgets', 'fse'],
            'actions' => ['read', 'modify'],
        ],
        'users' => [
            'label' => 'Usuarios',
            'tools' => ['users', 'roles'],
            'actions' => ['read', 'create', 'update', 'delete'],
        ],
        'data' => [
            'label' => 'Datos',
            'tools' => ['meta', 'options', 'database'],
            'actions' => ['read', 'write'],
        ],
    ];

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    /**
     * Check if token can perform tool/action.
     */
    public function can(string $token_hash, string $tool, string $action): bool {
        $token_data = $this->get_token_data($token_hash);
        if (!$token_data || $this->is_expired($token_data)) return false;
        
        $perms = $token_data['permissions'] ?? [];
        return isset($perms[$tool][$action]) && $perms[$tool][$action];
    }

    /**
     * Save permissions for token.
     */
    public function save_token_permissions(string $token_id, array $permissions): bool {
        $tokens = get_option('wp_mcp_tokens', []);
        if (!isset($tokens[$token_id])) return false;
        $tokens[$token_id]['permissions'] = $this->sanitize_permissions($permissions);
        return update_option('wp_mcp_tokens', $tokens);
    }

    /**
     * Get token data by hash.
     */
    private function get_token_data(string $token_hash): ?array {
        $tokens = get_option('wp_mcp_tokens', []);
        foreach ($tokens as $data) {
            if (hash_equals(wp_hash($data['token_plain']), $token_hash)) {
                return $data;
            }
        }
        return null;
    }

    private function is_expired(array $token_data): bool {
        return isset($token_data['expires']) && time() > strtotime($token_data['expires']);
    }

    /**
     * Sanitize permissions to schema.
     */
    private function sanitize_permissions(array $perms): array {
        $clean = [];
        foreach (self::PERMISSION_SCHEMA as $category => $config) {
            foreach ($config['tools'] as $tool) {
                foreach ($config['actions'] as $action) {
                    $clean[$tool][$action] = !empty($perms[$tool][$action] ?? false);
                }
            }
        }
        return $clean;
    }

    /**
     * Get full schema for UI.
     */
    public static function get_schema(): array {
        return self::PERMISSION_SCHEMA;
    }
}
?>

