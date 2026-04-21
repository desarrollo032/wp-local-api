import { __ } from '@wordpress/i18n';

declare global {
    interface Window {
        wp: any;
        mcpAdminData: any;
    }
}

const ConnectionPage = () => (
    <div className="connection-page">
        <h2>{__('Conexión MCP', 'wp-mcp-control')}</h2>
        <p><strong>URL Servidor:</strong> <code>{window.mcpAdminData?.siteUrl}/wp-json/mcp/v1/</code></p>
        <p>Header: <code>Authorization: Bearer {token}</code></p>
        <p>Endpoints: <code>/tools</code> (descubrir), <code>/call</code> (ejecutar)</p>
        <button>Test conexión</button>
    </div>
);

export default ConnectionPage;

