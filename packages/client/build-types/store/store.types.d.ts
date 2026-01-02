/**
 * Custom type declarations for WordPress Data Store
 * This file provides proper TypeScript types for @wordpress/data store usage
 * Compatible with @wordpress/data >= 10.x
 */
/**
 * Type for StoreDescriptor from @wordpress/data
 * In @wordpress/data >= 10.x, StoreDescriptor is a simple object with a name property
 */
export interface WPStoreDescriptor<State = any> {
    name: string;
}
/**
 * Type for the store returned by createReduxStore
 */
export interface WPStore<State = any> {
    dispatch: (action: any) => any;
    select: (selector: string | WPStoreDescriptor<State>) => any;
    resolveSelect: (selector: string | WPStoreDescriptor<State>) => Promise<any>;
    getState: () => State;
}
/**
 * Type for Redux store config
 */
export interface WPReduxStoreConfig<State = any> {
    reducer: (state: State | undefined, action: any) => State;
    actions?: Record<string, Function>;
    selectors?: Record<string, Function>;
    resolvers?: Record<string, Function>;
    controls?: Record<string, Function>;
    infers?: boolean;
}
/**
 * Type helper for WordPress store selectors
 */
export type WPSelectorResult<T> = T extends (state: any, ...args: infer Args) => infer Return ? (...args: Args) => Return : never;
//# sourceMappingURL=store.types.d.ts.map