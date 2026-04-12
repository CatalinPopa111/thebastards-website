// Import the original config from the @wordpress/scripts package.
import defaultConfig from '@wordpress/scripts/config/webpack.config.js';

// Add a new entry point by extending the Webpack config.
export default {
    ...defaultConfig,
    entry: {
        ...defaultConfig.entry(),
        'blocks/check-in-out/index': './src/blocks/check-in-out/index.js',
        'actions/index': './src/actions/index.js',
        'actions.v2/index': './src/actions.v2/index.js'
    },
    externalsType: 'window',
    externals: {
        'jet-form-builder-components': [ 'jfb', 'components' ],
        'jet-form-builder-data': [ 'jfb', 'data' ],
        'jet-form-builder-actions': [ 'jfb', 'actions' ],
        'jet-form-builder-blocks-to-actions': [ 'jfb', 'blocksToActions' ],
    },
};