import type { Feature } from "./types";
/**
 * Registers a feature with the feature registry.
 *
 * @param {Feature} feature The feature to register
 */
export declare function registerFeature(feature: Feature): void;
/**
 * Unregisters a feature from the feature registry.
 *
 * @param {string} featureId The ID of the feature to unregister
 */
export declare function unregisterFeature(featureId: string): void;
/**
 * Retrieves the definition of a registered feature.
 *
 * @param {string} featureId The ID of the feature to retrieve.
 * @return {Feature | null} The feature definition object or null if not found.
 */
export declare function getRegisteredFeature(featureId: string): Feature | null;
/**
 * Retrieves all registered features.
 *
 * @return An array of all registered feature definition objects, or null if the store is not ready.
 */
export declare function getRegisteredFeatures(): Promise<Feature[] | null>;
/**
 * Executes a registered feature.
 *
 * @param featureId The ID of the feature to execute
 * @param args      Arguments to pass to the feature callback
 * @return The result of the feature execution
 */
export declare function executeFeature(featureId: string, args: any): Promise<unknown>;
//# sourceMappingURL=api.d.ts.map