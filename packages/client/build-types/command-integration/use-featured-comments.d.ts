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
declare function useFeatureCommands({ search }: {
    search: any;
}): {
    commands: {
        name: string;
        label: string;
        icon: any;
        callback: ({ close }: {
            close: any;
        }) => void;
    }[];
    isLoading: boolean;
};
export default useFeatureCommands;
//# sourceMappingURL=use-featured-comments.d.ts.map