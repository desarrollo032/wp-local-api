declare global {
    interface Window {
        __WP_FEATURE_API_STORE_REGISTERED?: boolean;
    }
}
export declare const store: import("@wordpress/data").StoreDescriptor<import("@wordpress/data").ReduxStoreConfig<unknown, Record<string, import("@wordpress/data").ActionCreator>, unknown>>;
export type WPFeatureStore = typeof store;
//# sourceMappingURL=index.d.ts.map