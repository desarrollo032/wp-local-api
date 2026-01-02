/**
 * WordPress dependencies
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
/**
 * External dependencies
 */
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		index: './src/index.tsx',
	},
	output: {
		...defaultConfig.output,
		filename: '[name].js',
		path: __dirname + '/build',
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
};

