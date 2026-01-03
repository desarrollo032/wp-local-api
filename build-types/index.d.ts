/**
 * Internal dependencies
 */
import { store } from './store';
import { registerFeature, unregisterFeature, executeFeature, getRegisteredFeature, getRegisteredFeatures } from './api';
declare const publicApi: {
    store: import("@wordpress/data").StoreDescriptor<import("@wordpress/data").ReduxStoreConfig<unknown, typeof import("./store/actions"), typeof import("./store/selectors")>>;
    registerFeature: typeof registerFeature;
    unregisterFeature: typeof unregisterFeature;
    executeFeature: typeof executeFeature;
    getRegisteredFeature: typeof getRegisteredFeature;
    getRegisteredFeatures: typeof getRegisteredFeatures;
};
export { store };
export * from './types';
export { registerFeature, unregisterFeature, executeFeature, getRegisteredFeature, getRegisteredFeatures, };
export * from './command-integration';
export { publicApi as wpFeatures };
export default publicApi;
//# sourceMappingURL=index.d.ts.map