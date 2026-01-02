/**
 * Selectors
 */
import type { Feature, FeaturesState } from '../types';
export declare const getRegisteredFeatures: ((state: FeaturesState) => Feature[]) & import("rememo").EnhancedSelector;
export declare const getRegisteredFeature: (state: FeaturesState, id: string) => Feature | null;
export declare const getRegisteredFeatureCallback: Function;
export declare const getFeatureInputInProgress: (state: FeaturesState) => string | null;
//# sourceMappingURL=selectors.d.ts.map