/**
 * Internal dependencies
 */
import type { Message } from '../types/messages';
interface MessageProps {
    text: string;
}
export declare const UserMessage: ({ text }: MessageProps) => import("react").JSX.Element;
export declare const AssistantMessage: ({ message }: {
    message: Message;
}) => import("react").JSX.Element | null;
/**
 * Pending message component that shows a loading indicator
 */
export declare const PendingAssistantMessage: () => import("react").JSX.Element;
export {};
//# sourceMappingURL=ChatMessage.d.ts.map