/**
 * External dependencies
 */
import { type ReactNode, type Dispatch, type SetStateAction } from 'react';
/**
 * Internal dependencies
 */
import type { Message } from '../types/messages';
export interface McpStatus {
    is_active: boolean;
    tools_count: number;
    version?: string;
    status: 'connected' | 'inactive' | 'error';
}
export interface ConversationContextType {
    messages: Message[];
    setMessages: Dispatch<SetStateAction<Message[]>>;
    sendMessage: (query: string) => Promise<void>;
    isLoading: boolean;
    clearConversation: () => void;
    toolNameMap: Record<string, string>;
    models: Array<{
        id: string;
        owned_by?: string;
        raw?: any;
    }>;
    selectedModel: string | null;
    setSelectedModel: (modelId: string) => void;
    mcpStatus: McpStatus;
}
export declare const ConversationContext: import("react").Context<ConversationContextType | null>;
interface ConversationProviderProps {
    children: ReactNode;
}
export declare const ConversationProvider: ({ children, }: ConversationProviderProps) => import("react").JSX.Element;
export {};
//# sourceMappingURL=ConversationProvider.d.ts.map