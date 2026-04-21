declare global {
    interface Window {
        wp: any;
        mcpAdminData: any;
    }
}

import { createRoot } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { TabPanel } from '@wordpress/components';
import PermissionsPage from './pages/PermissionsPage';
import TokensPage from './pages/TokensPage';
import LogsPage from './pages/LogsPage';
import ConnectionPage from './pages/ConnectionPage';
import './style.scss';

const App = () => {
    const tabs = [
        {
            name: 'permissions',
            title: __('Permisos', 'wp-mcp-control'),
            className: 'tab-permissions',
        },
        {
            name: 'tokens',
            title: __('Tokens', 'wp-mcp-control'),
            className: 'tab-tokens',
        },
        {
            name: 'logs',
            title: __('Registros', 'wp-mcp-control'),
            className: 'tab-logs',
        },
        {
            name: 'connection',
            title: __('Conexión', 'wp-mcp-control'),
            className: 'tab-connection',
        },
    ];

    return (
        <div className="mcp-admin-app">
            <TabPanel tabs={tabs}>
                {(tab) => {
                    switch (tab.name) {
                        case 'permissions':
                            return <PermissionsPage />;
                        case 'tokens':
                            return <TokensPage />;
                        case 'logs':
                            return <LogsPage />;
                        case 'connection':
                            return <ConnectionPage />;
                        default:
                            return null;
                    }
                }}
            </TabPanel>
        </div>
    );
};

// StrictMode for dev
if (mcpAdminData) {
    const container = document.getElementById('mcp-admin-root');
    if (container) {
        createRoot(container).render(<App />);
    }
}

