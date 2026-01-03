/**
 * MCP Status Indicator Component
 *
 * Displays the current status of the wordpress-mcp plugin connection.
 * Shows a visual indicator (green = connected, gray = inactive).
 *
 * @package
 */

import { useState } from '@wordpress/element';
import { Tooltip } from '@wordpress/components';
import type { McpStatus } from '../context/ConversationProvider';

interface McpStatusIndicatorProps {
	status: McpStatus;
}

/**
 * Icon component for MCP status - shows connection state.
 * @param root0
 * @param root0.isActive
 */
const McpStatusIcon = ( { isActive }: { isActive: boolean } ) => {
	if ( isActive ) {
		return (
			<svg
				width="20"
				height="20"
				viewBox="0 0 20 20"
				fill="none"
				xmlns="http://www.w3.org/2000/svg"
				className="mcp-status-icon active"
			>
				<circle
					cx="10"
					cy="10"
					r="8"
					stroke="#10B981"
					strokeWidth="2"
					fill="rgba(16, 185, 129, 0.1)"
				/>
				<path
					d="M7 10L9 12L13 8"
					stroke="#10B981"
					strokeWidth="2"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
				<circle
					cx="10"
					cy="10"
					r="3"
					fill="#10B981"
					className="pulse-dot"
				/>
			</svg>
		);
	}

	return (
		<svg
			width="20"
			height="20"
			viewBox="0 0 20 20"
			fill="none"
			xmlns="http://www.w3.org/2000/svg"
			className="mcp-status-icon inactive"
		>
			<circle
				cx="10"
				cy="10"
				r="8"
				stroke="#9CA3AF"
				strokeWidth="2"
				fill="transparent"
			/>
			<path
				d="M10 6V10"
				stroke="#9CA3AF"
				strokeWidth="2"
				strokeLinecap="round"
			/>
			<circle cx="10" cy="13" r="1" fill="#9CA3AF" />
		</svg>
	);
};

/**
 * Get tooltip text based on MCP status.
 * @param status
 */
const getTooltipText = ( status: McpStatus ): string => {
	if ( status.is_active && status.status === 'connected' ) {
		if ( status.tools_count > 0 ) {
			return `MCP Connected (${ status.tools_count } tools available)${
				status.version ? ` - v${ status.version }` : ''
			}`;
		}
		return `MCP Connected${
			status.version ? ` - v${ status.version }` : ''
		}`;
	}

	return 'MCP is not active. Install wordpress-mcp plugin to enable automatic WordPress actions.';
};

/**
 * McpStatusIndicator Component
 * @param root0
 * @param root0.status
 */
export const McpStatusIndicator = ( { status }: McpStatusIndicatorProps ) => {
	const [ , setIsTooltipVisible ] = useState( false );

	const tooltipText = getTooltipText( status );
	const isActive = status.is_active && status.status === 'connected';

	return (
		<div className="mcp-status-indicator">
			<Tooltip text={ tooltipText } position="bottom left" delay={ 300 }>
				<div
					className={ `mcp-status-indicator-wrapper ${
						isActive ? 'connected' : 'inactive'
					}` }
					onMouseEnter={ () => setIsTooltipVisible( true ) }
					onMouseLeave={ () => setIsTooltipVisible( false ) }
					role="status"
					aria-label={ tooltipText }
				>
					<McpStatusIcon isActive={ isActive } />
					{ isActive && status.tools_count > 0 && (
						<span className="mcp-tools-count">
							{ status.tools_count }
						</span>
					) }
				</div>
			</Tooltip>
		</div>
	);
};

export default McpStatusIndicator;
