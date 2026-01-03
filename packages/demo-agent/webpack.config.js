/**
 * WordPress dependencies
 */
// eslint-disable-next-line import/no-extraneous-dependencies
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
/**
 * External dependencies
 */
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		index: path.resolve( __dirname, 'src/index.tsx' ),
	},
	output: {
		...defaultConfig.output,
		filename: '[name].js',
		path: path.resolve( __dirname, 'build' ),
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
