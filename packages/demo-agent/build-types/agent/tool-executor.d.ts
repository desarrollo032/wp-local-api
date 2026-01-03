/**
 * Internal dependencies
 */
import type { Tool, ToolResult } from '../types/messages';
/**
 * Interface for a provider that can supply tools to the executor.
 * Abstracted to show that WP Feature API is just one source of tools, and other sources can be added.
 */
export interface ToolProvider {
    /**
     * Retrieves the tools provided by this source.
     */
    getTools: () => Promise<Tool[]> | Tool[];
}
/**
 * Interface for the central tool execution engine.
 */
export interface ToolExecutor {
    /**
     * Retrieves a list of all currently registered tools.
     */
    listTools: () => Tool[];
    /**
     * Executes a registered tool by name with the given arguments.
     *
     * @param name The name (ID) of the tool to execute.
     * @param args The arguments to pass to the tool's execute function.
     * @return A promise resolving to the tool's result or error.
     */
    executeTool: (name: string, args: Record<string, unknown>) => Promise<ToolResult>;
    /**
     * Registers tools from a ToolProvider.
     *
     * @param provider The provider supplying the tools.
     * @return A promise that resolves when the provider's tools have been added.
     */
    addProvider: (provider: ToolProvider) => Promise<void>;
}
/**
 * Factory function to create a ToolExecutor instance.
 *
 * @return A new ToolExecutor instance.
 */
export declare const createToolExecutor: () => ToolExecutor;
//# sourceMappingURL=tool-executor.d.ts.map