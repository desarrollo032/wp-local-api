/**
 * Internal dependencies
 */
import type { Feature } from '../types';
interface FeatureAction {
    type: string;
    feature?: Feature;
    features?: Feature[];
    id?: string;
    callback?: () => unknown | Promise<unknown>;
}
declare const _default: import("redux").Reducer<{
    featuresById: Record<string, Feature>;
    featureInputInProgressId: string;
}, FeatureAction, Partial<{
    featuresById: Record<string, Feature>;
    featureInputInProgressId: string;
}>>;
export default _default;
//# sourceMappingURL=reducer.d.ts.map