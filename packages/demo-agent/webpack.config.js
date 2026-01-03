/**
 * WordPress dependencies
 */
// eslint-disable-next-line import/no-extraneous-dependencies
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
// eslint-disable-next-line import/no-extraneous-dependencies
const DependencyExtractionWebpackPlugin = require('@wordpress/dependency-extraction-webpack-plugin');
/**
 * External dependencies
 */
const path = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		index: path.resolve(__dirname, 'src/index.tsx'),
	},
	output: {
		...defaultConfig.output,
		filename: '[name].js',
		path: path.resolve(__dirname, 'build'),
	},
	resolve: {
		...defaultConfig.resolve,
		alias: {
			...defaultConfig.resolve?.alias,
			'@automattic/wp-feature-api': path.resolve(
				__dirname,
				'../client/src'
			),
		},
	},
	externals: {
		'@wordpress/api-fetch': 'wp.apiFetch',
		'@wordpress/components': 'wp.components',
		'@wordpress/data': 'wp.data',
		'@wordpress/element': 'wp.element',
		'@wordpress/i18n': 'wp.i18n',
		'@automattic/wp-feature-api': 'wp.features',
		'react': 'React',
		'react-dom': 'ReactDOM',
	},
	plugins: [
		...defaultConfig.plugins.filter(
			(plugin) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new DependencyExtractionWebpackPlugin(),
	],
};
