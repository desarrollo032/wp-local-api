/**
 * MCP Tool Provider
 *
 * Provides tools from the wordpress-mcp plugin when it's installed and active.
 * Falls back gracefully when MCP is not available.
 *
 * @package
 */

import type { Tool, ToolResult } from '../types/messages';
import type { ToolProvider } from './tool-executor';

/**
 * Configuration for the MCP tool provider.
 */
interface McpToolProviderConfig {
	/**
	 * Custom API client function for making REST requests.
	 * Defaults to using wp.apiFetch if available.
	 */
	apiClient?: ( endpoint: string, data?: unknown ) => Promise< unknown >;
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
 * Tool definition from MCP server.
 */
interface McpTool {
	name: string;
	description: string;
	input_schema?: Record< string, unknown >;
}

/**
 * Response from the MCP tools list endpoint.
 */
interface McpToolsResponse {
	tools: McpTool[];
	count: number;
	error?: string;
	message?: string;
}

/**
 * Factory function to create a ToolProvider that sources tools
 * from the wordpress-mcp plugin.
 *
 * @param config Configuration options for the provider.
 * @return A ToolProvider instance for MCP tools.
 */
export const createMcpToolProvider = (
	config: McpToolProviderConfig = {}
): ToolProvider => {
	/**
	 * Custom API client or default wp.apiFetch.
	 * @param endpoint
	 */
	const apiClient =
		config.apiClient ??
		( async ( endpoint: string ) => {
			const wpApiFetch = ( window as any ).wp?.apiFetch;
			if ( ! wpApiFetch ) {
				throw new Error(
					'wp.apiFetch is not available. Ensure script dependencies are loaded.'
				);
			}
			return await wpApiFetch( { path: endpoint } );
		} );

	/**
	 * Checks if the MCP plugin is active and returns its status.
	 */
	const checkMcpStatus = async (): Promise< McpStatus > => {
		try {
			const response = ( await apiClient(
				'/wp/v2/ai-api-proxy/v1/mcp/status'
			) ) as McpStatus;
			return response;
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.warn( 'MCP status check failed:', error );
			return {
				is_active: false,
				tools_count: 0,
				status: 'inactive',
			};
		}
	};

	/**
	 * Fetches tools from the MCP server and maps them to the agent's Tool format.
	 */
	const getTools = async (): Promise< Tool[] > => {
		try {
			// First, check if MCP is active
			const status = await checkMcpStatus();

			if ( ! status.is_active ) {
				// eslint-disable-next-line no-console
				console.log(
					'MCP plugin is not active. MCP tools will not be available.'
				);
				return [];
			}

			// Fetch tools from MCP server
			const response = ( await apiClient(
				'/wp/v2/ai-api-proxy/v1/mcp/tools'
			) ) as McpToolsResponse;

			if ( response.error ) {
				// eslint-disable-next-line no-console
				console.error( 'MCP tools fetch error:', response.message );
				return [];
			}

			if ( ! response.tools || ! Array.isArray( response.tools ) ) {
				return [];
			}

			// Map MCP tools to the agent's Tool interface
			const tools: Tool[] = response.tools.map(
				( tool: McpTool ): Tool => {
					return {
						name: `mcp_${ tool.name }`,
						displayName: tool.name,
						description: tool.description,
						parameters: tool.input_schema || {},
						execute: async (
							args: Record< string, unknown >
						): Promise< ToolResult > => {
							try {
								// Execute MCP tool via REST API
								const wpApiFetch = ( window as any ).wp
									?.apiFetch;
								if ( ! wpApiFetch ) {
									return {
										result: null,
										error: 'wp.apiFetch is not available',
									};
								}

								const result = await wpApiFetch( {
									path: '/wp/v2/ai-api-proxy/v1/mcp/call',
									method: 'POST',
									data: {
										tool: tool.name,
										arguments: args,
									},
								} );

								return { result };
							} catch ( error ) {
								// eslint-disable-next-line no-console
								console.error(
									`Error executing MCP tool "${ tool.name }":`,
									error
								);
								return {
									result: null,
									error:
										error instanceof Error
											? error.message
											: 'Unknown error executing MCP tool',
								};
							}
						},
					};
				}
			);

			return tools;
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Error fetching MCP tools:', error );
			return [];
		}
	};

	return {
		getTools,
	};
};

/**
 * Utility function to check if MCP is active.
 * Can be used to conditionally enable features based on MCP availability.
 */
export const isMcpActive = async (): Promise< boolean > => {
	try {
		const wpApiFetch = ( window as any ).wp?.apiFetch;
		if ( ! wpApiFetch ) {
			return false;
		}

		const response = await wpApiFetch( {
			path: '/wp/v2/ai-api-proxy/v1/mcp/status',
		} );

		return ( response as McpStatus ).is_active === true;
	} catch ( error ) {
		return false;
	}
};

/**
 * Utility function to get the MCP status with full details.
 */
export const getMcpStatus = async (): Promise< McpStatus > => {
	try {
		const wpApiFetch = ( window as any ).wp?.apiFetch;
		if ( ! wpApiFetch ) {
			return {
				is_active: false,
				tools_count: 0,
				status: 'inactive',
			};
		}

		const response = await wpApiFetch( {
			path: '/wp/v2/ai-api-proxy/v1/mcp/status',
		} );

		return response as McpStatus;
	} catch ( error ) {
		return {
			is_active: false,
			tools_count: 0,
			status: 'error',
		};
	}
};
