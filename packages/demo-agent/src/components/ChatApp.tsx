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
import { arrowRight, chevronDown, chevronUp } from '@wordpress/icons';
import { trash } from '@wordpress/icons';

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
	const [input, setInput] = useState('');
	const [isMinimized, setIsMinimized] = useState(false);
	const messagesEndRef = useRef<HTMLDivElement | null>(null);

	// Scroll to bottom when messages change
	useEffect(() => {
		messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
	}, [messages]);

	const handleSend = () => {
		if (input.trim() && !isLoading) {
			sendMessage(input.trim());
			setInput('');
		}
	};

	const handleKeyDown = (
		event: React.KeyboardEvent<HTMLTextAreaElement>
	) => {
		// Send on Enter, allow Shift+Enter for newline
		if (event.key === 'Enter' && !event.shiftKey) {
			event.preventDefault();
			handleSend();
		}
	};

	const toggleMinimize = () => {
		setIsMinimized(!isMinimized);
	};

	return (
		<div className={`chat-container${isMinimized ? ' minimized' : ''}`}>
			<div className="chat-header">
				<h2>AI Agent</h2>
				{models && models.length > 0 && (
					<div style={{ marginLeft: '12px', minWidth: '200px' }}>
						<SelectControl
							value={selectedModel ?? ''}
							onChange={(val) => setSelectedModel(val)}
							options={models.map((m) => {
								const isFree = m.raw?.is_free;
								const provider = m.raw?.provider_name || m.owned_by || '';
								const freeLabel = isFree ? ' (FREE)' : '';
								return {
									label: `${m.id}${freeLabel} (${provider})`,
									value: m.id,
								};
							})}
						/>
					</div>
				)}
				<div className="chat-header-actions">
					<McpStatusIndicator status={mcpStatus} />
					<Button
						onClick={clearConversation}
						label="Clear Conversation"
						icon={trash}
						isSmall
						variant="tertiary"
					/>
					<Button
						onClick={toggleMinimize}
						label={isMinimized ? 'Maximize' : 'Minimize'}
						icon={
							<Icon
								icon={isMinimized ? chevronUp : chevronDown}
							/>
						}
						isSmall
						variant="tertiary"
					/>
				</div>
			</div>
			<div className="chat-body">
				<div className="chat-messages">
					{messages.map((msg, index) => {
						const key = `msg-${index}`;
						if (msg.role === 'user') {
							return (
								<UserMessage
									key={key}
									text={msg.content ?? ''}
								/>
							);
						}
						return <AssistantMessage key={key} message={msg} />;
					})}
					{isLoading && <PendingAssistantMessage />}
					<div ref={messagesEndRef} />
				</div>
				<div className="chat-input">
					<TextareaControl
						value={input}
						onChange={setInput}
						placeholder="Type your message..."
						onKeyDown={handleKeyDown}
						disabled={isLoading}
						className="chat-input-textarea"
						rows={1}
					/>
					<Button
						onClick={handleSend}
						disabled={isLoading || !input.trim()}
						className="chat-input-submit"
						label="Send Message"
						icon={<Icon icon={arrowRight} />}
						variant="primary"
					/>
				</div>
			</div>
		</div>
	);
};
