/**
 * Internal dependencies
 */
import type { Message } from '../types/messages';
import type { ToolExecutor } from './tool-executor';
import type { McpStatus } from '../context/ConversationProvider';
/**
 * Defines the shape of the function responsible for making API calls.
 * This allows injecting different clients (e.g., wp.apiFetch, standard fetch).
 */
export type ApiClient = (endpoint: string, data: {
    messages: Message[];
    model: string;
    tools?: any[];
    tool_choice?: string;
}) => Promise<any>;
/**
 * Dependencies required by the agent orchestrator.
 */
export interface AgentDependencies {
    apiClient: ApiClient;
    toolExecutor?: ToolExecutor;
    mcpStatus?: McpStatus;
}
/**
 * The interface for the created agent.
 */
export interface Agent {
    /**
     * Processes a user query, interacts with the LLM via the ApiClient,
     * potentially uses tools, and yields messages representing the conversation flow.
     * @param query           The user's input string.
     * @param currentMessages The existing conversation history.
     * @param modelId         The ID of the model to use.
     * @return An async generator yielding Message objects.
     */
    processQuery: (query: string, currentMessages: Message[], modelId: string) => AsyncGenerator<Message>;
}
/**
 * Factory function to create an AI agent instance.
 * @param deps Dependencies like the API client and optional tool executor.
 * @return An Agent instance.
 */
export declare const createAgent: (deps: AgentDependencies) => Agent;
//# sourceMappingURL=orchestrator.d.ts.map