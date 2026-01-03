/**
 * MCP Status Indicator Component
 *
 * Displays the current status of the wordpress-mcp plugin connection.
 * Shows a visual indicator (green = connected, gray = inactive).
 *
 * @package
 */
import type { McpStatus } from '../context/ConversationProvider';
interface McpStatusIndicatorProps {
    status: McpStatus;
}
/**
 * McpStatusIndicator Component
 * @param root0
 * @param root0.status
 */
export declare const McpStatusIndicator: ({ status }: McpStatusIndicatorProps) => import("react").JSX.Element;
export default McpStatusIndicator;
//# sourceMappingURL=McpStatusIndicator.d.ts.map