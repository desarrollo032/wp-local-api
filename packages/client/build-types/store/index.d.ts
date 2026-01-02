import * as actions from './actions';
import * as selectors from './selectors';
declare global {
    interface Window {
        __WP_FEATURE_API_STORE_REGISTERED?: boolean;
    }
}
export declare const store: import("@wordpress/data/build-types/types").StoreDescriptor<import("@wordpress/data/build-types/types").ReduxStoreConfig<unknown, typeof actions, typeof selectors>>;
//# sourceMappingURL=index.d.ts.map