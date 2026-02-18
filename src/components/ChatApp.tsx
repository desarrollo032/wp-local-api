/**
 * WordPress dependencies
 */
import { useState, useRef, useEffect } from '@wordpress/element';
import {
	Button,
	TextareaControl,
	Icon,
	SelectControl,
} from '@wordpress/components';
import { arrowRight, trash, chevronDown } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { useConversation } from '../hooks/useConversation';
import {
	UserMessage,
	AssistantMessage,
	PendingAssistantMessage,
} from './ChatMessage';
import { McpStatusIndicator } from './McpStatusIndicator';

const CHAT_OPEN_STATE_KEY = 'wp-feature-api-agent-chat-open';

export const ChatApp = () => {
	const {
		messages,
		sendMessage,
		isLoading,
		clearConversation,
		models,
		selectedModel,
		setSelectedModel,
		mcpStatus,
	} = useConversation();
	const [ input, setInput ] = useState( '' );
	const [ isOpen, setIsOpen ] = useState( () => {
		if ( typeof window === 'undefined' ) {
			return false;
		}

		try {
			const stored = window.localStorage.getItem( CHAT_OPEN_STATE_KEY );
			return stored === '1';
		} catch {
			return false;
		}
	} );
	const messagesEndRef = useRef< HTMLDivElement | null >( null );

	// Scroll to bottom when messages change
	useEffect( () => {
		messagesEndRef.current?.scrollIntoView( { behavior: 'smooth' } );
	}, [ messages ] );

	// Persist open/closed state in localStorage
	useEffect( () => {
		if ( typeof window === 'undefined' ) {
			return;
		}

		try {
			window.localStorage.setItem(
				CHAT_OPEN_STATE_KEY,
				isOpen ? '1' : '0'
			);
		} catch {
			// Ignore storage errors.
		}
	}, [ isOpen ] );

	// Global keyboard shortcut: Ctrl+Shift+K to toggle chat
	useEffect( () => {
		if ( typeof window === 'undefined' ) {
			return;
		}

		const handler = ( event: KeyboardEvent ) => {
			if ( event.ctrlKey && event.shiftKey && event.key.toLowerCase() === 'k' ) {
				event.preventDefault();
				setIsOpen( ( prev ) => ! prev );
			}
		};

		window.addEventListener( 'keydown', handler );
		return () => window.removeEventListener( 'keydown', handler );
	}, [] );

	const handleSend = () => {
		if ( input.trim() && ! isLoading ) {
			sendMessage( input.trim() );
			setInput( '' );
		}
	};

	const handleKeyDown = (
		event: React.KeyboardEvent< HTMLTextAreaElement >
	) => {
		// Send on Enter, allow Shift+Enter for newline
		if ( event.key === 'Enter' && ! event.shiftKey ) {
			event.preventDefault();
			handleSend();
		}
	};

	// When closed, show only the floating launcher button
	if ( ! isOpen ) {
		return (
			<Button
				onClick={ () => setIsOpen( true ) }
				className="chat-launcher-button"
				variant="primary"
				label="Abrir chat de IA"
			>
				AI
			</Button>
		);
	}

	return (
		<div className="chat-container">
			<div className="chat-header">
				<h2>AI Agent</h2>
				{ models && models.length > 0 && (
					<div style={ { marginLeft: '12px', minWidth: '200px' } }>
						<SelectControl
							value={ selectedModel ?? '' }
							onChange={ ( val ) => setSelectedModel( val ) }
							options={ models.map( ( m ) => ( {
								label: `${ m.id } (${ m.owned_by ?? '' })`,
								value: m.id,
							} ) ) }
						/>
					</div>
				) }
				<div className="chat-header-actions">
					<McpStatusIndicator status={ mcpStatus } />
					<Button
						onClick={ clearConversation }
						label="Clear Conversation"
						icon={ <Icon icon={ trash } /> }
						isSmall
						variant="tertiary"
					/>
					<Button
						onClick={ () => setIsOpen( false ) }
						label="Close"
						icon={ <Icon icon={ chevronDown } /> }
						isSmall
						variant="tertiary"
					/>
				</div>
			</div>
			<div className="chat-body">
				<div className="chat-messages">
					{ messages.map( ( msg, index ) => {
						const key = `msg-${ index }`;
						if ( msg.role === 'user' ) {
							return (
								<UserMessage
									key={ key }
									text={ msg.content ?? '' }
								/>
							);
						}
						return <AssistantMessage key={ key } message={ msg } />;
					} ) }
					{ isLoading && <PendingAssistantMessage /> }
					<div ref={ messagesEndRef } />
				</div>
				<div className="chat-input">
					<TextareaControl
						value={ input }
						onChange={ setInput }
						placeholder="Type your message..."
						onKeyDown={ handleKeyDown }
						disabled={ isLoading }
						className="chat-input-textarea"
						rows={ 1 }
					/>
					<Button
						onClick={ handleSend }
						disabled={ isLoading || ! input.trim() }
						className="chat-input-submit"
						label="Send Message"
						icon={ <Icon icon={ arrowRight } /> }
						variant="primary"
					/>
				</div>
			</div>
		</div>
	);
};
