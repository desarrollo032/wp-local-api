import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';

interface PermissionMatrixProps {
    schema: any;
    permissions: any;
    onChange: (newPerms: any) => void;
}

const PermissionMatrix = ({ schema, permissions, onChange }: PermissionMatrixProps) => {
    const updatePerm = (tool: string, action: string, checked: boolean) => {
        const newPerms = { ...permissions };
        newPerms[tool] = { ...newPerms[tool], [action]: checked };
        onChange(newPerms);
    };

    return (
        <table className="permission-matrix">
            <thead>
                <tr>
                    <th>Herramienta</th>
                    {Object.values(schema)[0].actions.map((action: string) => (
                        <th key={action}>{action.toUpperCase()}</th>
                    ))}
                </tr>
            </thead>
            <tbody>
                {Object.entries(schema).map(([category, config]: any) => (
                    <React.Fragment key={category}>
                        <tr><td colSpan={100}><strong>{config.label}</strong></td></tr>
                        {config.tools.map((tool: string) => (
                            <tr key={tool}>
                                <td>{tool}</td>
                                {config.actions.map((action: string) => (
                                    <td key={action}>
                                        <CheckboxControl
                                            checked={permissions[tool]?.[action] || false}
                                            onChange={(checked) => updatePerm(tool, action, checked)}
                                        />
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </React.Fragment>
                ))}
            </tbody>
        </table>
    );
};

export default PermissionMatrix;

