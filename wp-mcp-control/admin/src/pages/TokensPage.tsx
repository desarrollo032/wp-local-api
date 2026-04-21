import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, TextControl, Notice } from '@wordpress/components';

declare global {
    interface Window {
        wp: any;
        mcpAdminData: any;
    }
}

const TokensPage = () => {
    const [tokens, setTokens] = useState([]);
    const [newTokenName, setNewTokenName] = useState('');
    const [loading, setLoading] = useState(false);
    const [showToken, setShowToken] = useState('');

    const loadTokens = async () => {
        try {
            const response = await window.wp.apiFetch({ path: '/mcp/v1/tokens' });
            setTokens(response);
        } catch (error) {}
    };

    const createToken = async () => {
        setLoading(true);
        try {
            const response = await window.wp.apiFetch({
                path: '/mcp/v1/tokens',
                method: 'POST',
                data: { name: newTokenName },
            });
            setShowToken(response.plain_token);
            loadTokens();
        } catch (error) {}
        setLoading(false);
    };

    const revokeToken = async (tokenId: string) => {
        try {
            await window.wp.apiFetch({
                path: `/mcp/v1/tokens/${tokenId}`,
                method: 'DELETE',
            });
            loadTokens();
        } catch (error) {}
    };

    return (
        <div className="tokens-page">
            <h2>{__('Tokens API', 'wp-mcp-control')}</h2>
            <TextControl
                label={__('Nuevo Token Name', 'wp-mcp-control')}
                value={newTokenName}
                onChange={setNewTokenName}
            />
            <Button isPrimary onClick={createToken} disabled={loading || !newTokenName}>
                {__('Crear Token', 'wp-mcp-control')}
            </Button>
            {showToken && (
                <Notice status="success" isDismissible={false}>
                    <strong>{__('Token generado (guárdalo ahora!): ')} {showToken}</strong>
                </Notice>
            )}
            <h3>{__('Tokens Activos')}</h3>
            <ul>
                {tokens.map((token: any) => (
                    <li key={token.id}>
                        {token.name} (exp: {token.expires})
                        <Button 
                            variant="secondary" 
                            onClick={() => revokeToken(token.id)}
                        >
                            Revocar
                        </Button>
                    </li>
                ))}
            </ul>
            <Button onClick={loadTokens}>{__('Refrescar')}</Button>
        </div>
    );
};

export default TokensPage;

