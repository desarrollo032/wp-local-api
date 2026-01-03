/**
 * WordPress dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ConversationProvider } from './context/ConversationProvider';
import { ChatApp } from './components/ChatApp';
import { registerCoreFeatures } from './features-registry';

import './style.scss';

// Register features
registerCoreFeatures();

// Mount when WordPress is ready
if (typeof window !== 'undefined') {
	const mountChatApp = () => {
		const mountPoint = document.getElementById(
			'wp-feature-api-agent-chat'
		);

		if (mountPoint) {
			const root = createRoot(mountPoint);
			root.render(
				<ConversationProvider>
					<ChatApp />
				</ConversationProvider>
			);
		} else {
			// eslint-disable-next-line no-console
			console.warn(
				'Chat container #wp-feature-api-agent-chat not found in admin footer'
			);
		}
	};

	// Try to mount immediately if DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', mountChatApp);
	} else {
		// DOM already loaded, mount immediately
		mountChatApp();
	}
}
