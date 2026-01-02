/**
 * WordPress dependencies
 */
import type { store as coreStore } from '@wordpress/core-data';
import type { store as featureStore } from './store';

/**
 * Internal dependencies
 */

/**
 * Type definition for the WordPress registry
 */
type RegistryDispatch = {
	dispatch: ( storeName: any ) => any;
	select: ( storeName: any ) => any;
};

/**
 * Type for the context passed to feature callbacks
 */
type FeatureCallbackContext = {
	data: {
		dispatch: ( storeName: any ) => any;
		select: ( storeName: any ) => any;
	};
};

/**
 * Select function type for WordPress stores
 */
type SelectFunction = ( storeName: string | any ) => any;

/**
 * Dispatch function type for WordPress stores
 */
type DispatchFunction = ( storeName: string | any ) => any;

export interface Feature {
	id: string;
	name: string;
	description: string;
	type: 'resource' | 'tool';
	meta?: Record< string, any >;
	categories: string[];
	input_schema?: Record< string, any >;
	output_schema?: Record< string, any >;
	location: 'server' | 'client';
	icon?: any;
	is_eligible?: () => boolean;
	callback?: (
		args: any,
		context: FeatureCallbackContext
	) => unknown | Promise< unknown >;
}

export interface FeaturesState {
	featuresById: Record< string, Feature >;
	featureInputInProgressId: string | null;
}

// Declare global variables provided by WordPress
// Currently used for the navigate feature, but we may want to handle this a different way
declare global {
	const ajaxurl: string | undefined;
}

