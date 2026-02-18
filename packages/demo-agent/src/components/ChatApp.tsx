/**
 * WordPress dependencies
 */
import { useState, useRef, useEffect } from '@wordpress/element';
import {
	Button,
	TextareaControl,
	SelectControl,
} from '@wordpress/components';

/**
 * Inline SVG icons to avoid dependency on wp.icons which may not be loaded
 */
const TrashIcon = () => (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
		<path d="M19 6.5H5V18a2 2 0 002 2h10a2 2 0 002-2V6.5zm-8 10h-1v-7h1v7zm3 0h-1v-7h1v7zm3-12h-4V3H11v1.5H7v1.5h10V4.5z" />
	</svg>
);

const ArrowRightIcon = () => (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
		<path d="M14.3 6.7l-1.1 1.1 4.2 4.2H3v1.5h14.4l-4.2 4.2 1.1 1.1 5.7-5.7-5.7-5.4z" />
	</svg>
);

const ChevronDownIcon = () => (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
		<path d="M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z" />
	</svg>
);

const ChevronUpIcon = () => (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
		<path d="M6.5 12.4L12 8l5.5 4.4-.9 1.2L12 10l-4.5 3.6-1-1.2z" />
	</svg>
);

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
	const [ isOpen, setIsOpen ] = useState( false );
	const messagesEndRef = useRef< HTMLDivElement | null >( null );

	// Scroll to bottom when messages change
	useEffect( () => {
		messagesEndRef.current?.scrollIntoView( { behavior: 'smooth' } );
	}, [ messages ] );

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
					<div style={ { marginLeft: '12px', minWidth: '80px', flex: 1 } }>
						<SelectControl
							value={ selectedModel ?? '' }
							onChange={ ( val ) => setSelectedModel( val ) }
							options={ models.map( ( m ) => {
								const isFree = m.raw?.is_free;
								const provider = m.raw?.provider_name || m.owned_by || '';
								const freeLabel = isFree ? ' (FREE)' : '';
								return {
									label: `${ m.id }${ freeLabel } (${ provider })`,
									value: m.id,
								};
							} ) }
						/>
					</div>
				) }
				<div className="chat-header-actions">
					<McpStatusIndicator status={ mcpStatus } />
					<Button
						onClick={ clearConversation }
						label="Clear Conversation"
						icon={ <TrashIcon /> }
						isSmall
						variant="tertiary"
					/>
					<Button
						onClick={ () => setIsOpen( false ) }
						label="Close"
						icon={ <ChevronDownIcon /> }
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
						return (
							<AssistantMessage key={ key } message={ msg } />
						);
					} ) }
					{ isLoading && <PendingAssistantMessage /> }
					<div ref={messagesEndRef} />
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
						icon={ <ArrowRightIcon /> }
						variant="primary"
					/>
				</div>
			</div>
		</div>
	);
};
