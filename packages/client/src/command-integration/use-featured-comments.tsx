/**
 * WordPress dependencies
 */
import { useMemo } from '@wordpress/element';

// Internal dependencies
/**
 * Internal dependencies
 */
import { store } from '../store';
import type { Feature } from '../types';
import { useDispatch, useSelect, useRegistry } from '@wordpress/data';
import { brush } from '@wordpress/icons'; // Default icon for commands

/**
 * Custom React hook to load registered features as dynamic commands.
 * It fetches features using getRegisteredFeatures, filters them based on
 * the presence of a callback and the user's search term, and formats them
 * for the Command Palette.
 *
 * @param {Object} options        Hook options passed by the command loader.
 * @param {string} options.search The search term entered by the user in the Command Palette.
 * @return {Object} An object containing the list of commands and the loading state.
 *                   { commands: Array<object>, isLoading: boolean }
 */
function useFeatureCommands( { search }: { search?: string } ) {
	// Get the registry
	const registry = useRegistry();
	
	// Get dispatch and select functions from our store
	const dispatchFromStore = useDispatch( store as any );
	
	// Select features and loading state using the imported selector
	const { features, isLoading } = useSelect(
		( _select: any ) => {
			const _store = _select( store as any );
			const resolvedFeatures = _store.getRegisteredFeatures() as Feature[];
			const hasFinishedResolution = _store.hasFinishedResolution(
				'getRegisteredFeatures',
				[]
			);

			return {
				features: resolvedFeatures || [],
				isLoading: ! hasFinishedResolution,
			};
		},
		[]
	); // Dependency array is empty as selectors handle their own memoization

	// Memoize the command generation process to avoid recalculating on every render
	const setFeatureInputInProgress = dispatchFromStore.setFeatureInputInProgress;
	
	const commands = useMemo( () => {
		// Filter features to include only those with a callback function
		const featuresWithCallback = features.filter(
			( feature ) => typeof feature.callback === 'function'
		);

		// Get dispatch and select from the registry for the feature callback context
		const { dispatch: registryDispatch, select: registrySelect } = registry as any;

		// Map features to the command object structure required by the Command Palette
		let commandList = featuresWithCallback.map( ( feature ) => ( {
			name: `feature/${ feature.id }`, // Unique command name (prefixing helps avoid conflicts)
			label: feature.name || feature.id, // Human-readable label (fallback to id)
			icon: feature.icon || brush, // Use feature's icon or a default one
			callback: ( { close }: { close: () => void } ) => {
				if ( ! feature.input_schema ) {
					// Wrapper callback provided by the command palette
					feature.callback?.( {}, { data: { dispatch: registryDispatch, select: registrySelect } } ); // Execute the original feature callback safely
					close(); // Close the palette after execution
				} else {
					// Open the modal
					setFeatureInputInProgress( feature.id );
				}
			},
		} ) );

		// Filter commands based on the search term (case-insensitive) if provided
		if ( search ) {
			commandList = commandList.filter( ( command ) =>
				command.label.toLowerCase().includes( search.toLowerCase() )
			);
		}
		return commandList;
	}, [ features, search, registry, setFeatureInputInProgress ] ); // Recalculate only if features or search term change

	// Return the prepared commands and the loading state
	return { commands, isLoading };
}

export default useFeatureCommands;

