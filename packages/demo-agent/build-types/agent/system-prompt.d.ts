/**
 * Formats the current date as a string for use in the system prompt.
 * @return The current date in human-readable format.
 */
export declare const getCurrentDateForPrompt: () => string;
/**
 * Internal dependencies
 */
import type { McpStatus } from '../context/ConversationProvider';
/**
 * Generates the system prompt based on MCP status.
 *
 * @param mcpStatus The current MCP status.
 * @return The system prompt string.
 */
export declare const generateSystemPrompt: (mcpStatus: McpStatus) => string;
/**
 * The default system prompt used for agent interactions (for backward compatibility).
 * @deprecated Use generateSystemPrompt with MCP status instead.
 */
export declare const defaultSystemPrompt: string;
//# sourceMappingURL=system-prompt.d.ts.map