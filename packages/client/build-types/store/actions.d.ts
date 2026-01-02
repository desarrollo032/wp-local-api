import type { Feature } from '../types';
export declare function registerFeature(feature: Feature): {
    type: string;
    feature: Feature;
};
export declare function receiveFeature(feature: Feature): {
    type: string;
    feature: Feature;
};
export declare function unregisterFeature(featureId: string): {
    type: string;
    feature: {
        id: string;
    };
};
export declare function receiveFeatures(features: Feature[]): {
    type: string;
    features: Feature[];
};
export declare function registerFeatureCallback(id: string, callback: () => unknown | Promise<unknown>): ({ registry, dispatch }: {
    registry: any;
    dispatch: any;
}) => Promise<void>;
export declare function setFeatureInputInProgress(id: string): {
    type: string;
    id: string;
};
//# sourceMappingURL=actions.d.ts.map