/**
 * WordPress dependencies
 */
/**
 * Internal dependencies
 */
/**
 * Type definition for the WordPress registry
 * (Kept for future reference or if needed, but currently unused locally)
 */
/**
 * Type for the context passed to feature callbacks
 */
type FeatureCallbackContext = {
    data: {
        dispatch: any;
        select: any;
    };
};
/**
 * Select function type for WordPress stores
 */
/**
 * Dispatch function type for WordPress stores
 */
export interface Feature {
    id: string;
    name: string;
    description: string;
    type: 'resource' | 'tool';
    meta?: Record<string, any>;
    categories: string[];
    input_schema?: Record<string, any>;
    output_schema?: Record<string, any>;
    location: 'server' | 'client';
    icon?: any;
    is_eligible?: () => boolean;
    callback?: (args: any, context: FeatureCallbackContext) => unknown | Promise<unknown>;
}
export interface FeaturesState {
    featuresById: Record<string, Feature>;
    featureInputInProgressId: string | null;
}
declare global {
    const ajaxurl: string | undefined;
}
export {};
//# sourceMappingURL=types.d.ts.map