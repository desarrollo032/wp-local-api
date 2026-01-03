/**
 * WordPress dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ConversationProvider } from './context/ConversationProvider';
import { ChatApp } from './components/ChatApp';

import './style.scss';

/**
 * Initialize the chat when WordPress is ready
 */
function initializeChat() {
	const mountPoint = document.getElementById( 'wp-feature-api-agent-chat' );
	
	if ( ! mountPoint ) {
		// eslint-disable-next-line no-console
		console.warn( 'WP Feature API Agent: Chat container #wp-feature-api-agent-chat not found in admin footer' );
		return;
	}

	// eslint-disable-next-line no-console
	console.log( 'WP Feature API Agent: Initializing chat interface' );

	try {
		const root = createRoot( mountPoint );
		root.render(
			<ConversationProvider>
				<ChatApp />
			</ConversationProvider>
		);
		
		// eslint-disable-next-line no-console
		console.log( 'WP Feature API Agent: Chat interface initialized successfully' );
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( 'WP Feature API Agent: Failed to initialize chat interface:', error );
	}
}

// Mount when WordPress is ready
if ( typeof window !== 'undefined' ) {
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initializeChat );
	} else {
		// DOM already loaded, initialize immediately
		initializeChat();
	}
}
