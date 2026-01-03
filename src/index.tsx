/**
 * WordPress dependencies
 */
import { createRoot, useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ConversationProvider } from './context/ConversationProvider';
import { ChatApp } from './components/ChatApp';

import './style.scss';

/**
 * ChatRoot Component - Handles mounting the chat in WordPress admin
 */
const ChatRoot = () => {
	const rootRef = useRef< ReturnType< typeof createRoot > | null >( null );

	useEffect( () => {
		// Find the container in the DOM
		const container = document.getElementById(
			'wp-feature-api-agent-chat'
		);

		if ( container && ! rootRef.current ) {
			rootRef.current = createRoot( container );
			rootRef.current.render(
				<ConversationProvider>
					<ChatApp />
				</ConversationProvider>
			);
		}

		return () => {
			// Cleanup on unmount
			if ( rootRef.current ) {
				rootRef.current.unmount();
				rootRef.current = null;
			}
		};
	}, [] );

	return null; // Container is rendered by PHP in admin_footer
};

// Mount when WordPress is ready
if ( typeof window !== 'undefined' ) {
	// Try to mount immediately if DOM is ready
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', () => {
			const mountPoint = document.getElementById(
				'wp-feature-api-agent-chat'
			);
			if ( mountPoint ) {
				const root = createRoot( mountPoint );
				root.render( <ChatRoot /> );
			}
		} );
	} else {
		// DOM already loaded, mount immediately
		const mountPoint = document.getElementById(
			'wp-feature-api-agent-chat'
		);
		if ( mountPoint ) {
			const root = createRoot( mountPoint );
			root.render( <ChatRoot /> );
		} else {
			// eslint-disable-next-line no-console
			console.warn(
				'Chat container #wp-feature-api-agent-chat not found in admin footer'
			);
		}
	}
}
