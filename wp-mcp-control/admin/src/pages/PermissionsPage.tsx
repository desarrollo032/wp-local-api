import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { SelectControl, Button } from '@wordpress/components';
import PermissionMatrix from '../components/PermissionMatrix';
// Global mcpAdminData

const PermissionsPage = () => {
    const [selectedToken, setSelectedToken] = useState('');
    const [permissions, setPermissions] = useState({});
    const [loading, setLoading] = useState(false);

    const schema = mcpAdminData.permissionsSchema;

    const loadPermissions = async () => {
        if (!selectedToken) return;
        setLoading(true);
        try {
            const response = await window.wp.apiFetch({ path: `/mcp/v1/tokens/${selectedToken}` });
            setPermissions(response.permissions || {});
        } catch (error) {
            // Handle error
        }
        setLoading(false);
    };

    const savePermissions = async () => {
        setLoading(true);
        try {
            await window.wp.apiFetch({
                path: `/mcp/v1/tokens/${selectedToken}/permissions`,
                method: 'POST',
                data: { permissions },
            });
        } catch (error) {
            // Handle
        }
        setLoading(false);
    };

    return (
        <div className="permissions-page">
            <SelectControl
                label={__('Seleccionar Token', 'wp-mcp-control')}
                value={selectedToken}
                options={[]} // Fetch tokens
                onChange={setSelectedToken}
            />
            <Button isPrimary onClick={loadPermissions} disabled={loading || !selectedToken}>
                {__('Cargar Permisos', 'wp-mcp-control')}
            </Button>
            <PermissionMatrix
                schema={schema}
                permissions={permissions}
                onChange={setPermissions}
            />
            <Button isPrimary onClick={savePermissions}>
                {__('Guardar Permisos', 'wp-mcp-control')}
            </Button>
        </div>
    );
};

export default PermissionsPage;

