const path = require('path');
const webpackConfig = require('@nextcloud/webpack-vue-config');

const buildMode = process.env.NODE_ENV;
const isDev = buildMode === 'development';

webpackConfig.entry = {
    'app-main': path.join(__dirname, 'src', 'js', 'app-main.js'),
};

// Add any custom webpack configurations here
if (isDev) {
    webpackConfig.devtool = 'cheap-source-map';
}

module.exports = webpackConfig;