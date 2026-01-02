/**
 * Selectors
 */
import type { Feature, FeaturesState } from '../types';
export declare const getRegisteredFeatures: ((state: FeaturesState) => Feature[]) & import("rememo").EnhancedSelector;
export declare const getRegisteredFeature: (state: FeaturesState, id: string) => Feature | null;
export declare const getRegisteredFeatureCallback: {
    (state: FeaturesState, id: string): (args: any, context: {
        data: {
            dispatch: any;
            select: any;
        };
    }) => unknown | Promise<unknown>;
    isRegistrySelector?: boolean;
    registry?: any;
};
export declare const getFeatureInputInProgress: (state: FeaturesState) => string | null;
//# sourceMappingURL=selectors.d.ts.map