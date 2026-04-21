import { __ } from '@wordpress/i18n';

declare global {
    interface Window {
        wp: any;
        mcpAdminData: any;
    }
}

const LogsPage = () => {
    // Fetch /mcp/v1/logs via apiFetch, display table w/ filters
    return (
        <div className="logs-page">
            <h2>{__('Registros de Actividad MCP', 'wp-mcp-control')}</h2>
            <p>Historial de llamadas (últimas 1000).</p>
            {/* Table: token, tool, args, result, IP, time */}
            <div>Logs table (implement fetch/display)</div>
        </div>
    );
};

export default LogsPage;

