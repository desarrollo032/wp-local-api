/**
 * MCP Tool Provider
 *
 * Provides tools from the wordpress-mcp plugin when it's installed and active.
 * Falls back gracefully when MCP is not available.
 *
 * @package
 */
import type { ToolProvider } from './tool-executor';
/**
 * Configuration for the MCP tool provider.
 */
interface McpToolProviderConfig {
    /**
     * Custom API client function for making REST requests.
     * Defaults to using wp.apiFetch if available.
     */
    apiClient?: (endpoint: string, data?: unknown) => Promise<unknown>;
}
/**
 * Status of the MCP connection.
 */
export interface McpStatus {
    is_active: boolean;
    tools_count: number;
    version?: string;
    status: 'connected' | 'inactive' | 'error';
}
/**
 * Factory function to create a ToolProvider that sources tools
 * from the wordpress-mcp plugin.
 *
 * @param config Configuration options for the provider.
 * @return A ToolProvider instance for MCP tools.
 */
export declare const createMcpToolProvider: (config?: McpToolProviderConfig) => ToolProvider;
/**
 * Utility function to check if MCP is active.
 * Can be used to conditionally enable features based on MCP availability.
 */
export declare const isMcpActive: () => Promise<boolean>;
/**
 * Utility function to get the MCP status with full details.
 */
export declare const getMcpStatus: () => Promise<McpStatus>;
export {};
//# sourceMappingURL=mcp-tool-provider.d.ts.map