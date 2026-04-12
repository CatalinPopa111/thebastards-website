// Import the original config from the @wordpress/scripts package.
import defaultConfig from '@wordpress/scripts/config/webpack.config.js';

// Add a new entry point by extending the Webpack config.
export default {
	...defaultConfig,
	entry: {
		...defaultConfig.entry(),
		index: './src/index.js',
	},
};
