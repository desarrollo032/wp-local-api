<?php
/**
 * MCP Registry - Extends WP_Feature_Registry, registers all tools.
 */

defined('ABSPATH') || exit;

namespace WP_MCP_Control;

use WP_Feature_Registry; // From existing API

class MCP_Registry extends WP_Feature_Registry {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        parent::__construct(); // Init parent
    }

    /**
     * Register all MCP tools (stub - populate Phase 2).
     */
    public function register_all_tools(): void {
        // Load tool classes
        $tools_path = WP_MCP_CONTROL_PATH . 'includes/tools/';
        $tool_files = glob($tools_path . 'class-tool-*.php');
        
        foreach ($tool_files as $file) {
            require_once $file;
            $class_name = 'WP_MCP_Control\\Tool_' . basename($file, '.php'); // Naming conv
            if (class_exists($class_name)) {
                $tool = new $class_name();
                $tool->register_features($this);
            }
        }

        /**
         * Action after all tools registered.
         */
        do_action('wp_mcp_tools_registered');
    }

    /**
     * Get eligible tools for MCP discovery (token-aware).
     */
    public function get_mcp_tools(string $token_hash = null): array {
        $query = new \WP_Feature_Query(['type' => 'tool', 'is_eligible' => true]);
        $tools = $this->get($query);
        
        if ($token_hash) {
            $perms = MCP_Permissions::get_instance();
            $tools = array_filter($tools, function($tool) use ($perms, $token_hash) {
                $tool_name = basename($tool->get_id(), '-tool'); // Extract tool
                return $perms->can($token_hash, $tool_name, 'read'); // At least read
            });
        }
        
        return array_map(function($tool) {
            return [
                'name' => $tool->get_id(),
                'description' => $tool->get_description(),
                'inputSchema' => $tool->get_input_schema(),
                'outputSchema' => $tool->get_output_schema(),
            ];
        }, $tools);
    }
}
?>

