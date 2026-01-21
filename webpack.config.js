const path = require('path');
const HtmlWebpackPlugin = require('html-webpack-plugin');

const commonLibDir = path.resolve(__dirname, 'vendor-prefixed/stratease/flux-plugins-common');
const { createBaseWebpackConfig } = require(path.join(commonLibDir, 'webpack.config.helpers'));

// Get base config from flux-plugins-common
const baseConfig = createBaseWebpackConfig({
  pluginDir: __dirname,
  pluginSlug: 'flux-ai-media-alt-creator',
});

// Merge with plugin-specific config
module.exports = {
  ...baseConfig,
  entry: {
    ...baseConfig.entry,
    admin: './assets/js/src/admin/index.js',
    // Note: license-page and logs-page are built separately by flux-plugins-common
    // and loaded via MenuService enqueue
  },
  output: {
    ...baseConfig.output,
    path: path.resolve(__dirname, 'assets/js/dist'),
    filename: '[name].bundle.js',
    clean: true,
  },
  resolve: {
    ...baseConfig.resolve,
    extensions: ['.js', '.jsx'],
    // Add common library's node_modules to module resolution
    // This allows the plugin to import and compile shared React components from common library
    // Order matters: check plugin's node_modules first, then common library's
    modules: [
      path.resolve(__dirname, 'node_modules'), // Plugin's node_modules first
      path.join(commonLibDir, 'node_modules'),   // Common library's node_modules
      'node_modules',                             // Fallback
    ],
    alias: {
      ...baseConfig.resolve.alias,
      '@flux-ai-media-alt-creator': path.resolve(__dirname, 'assets/js/src'),
      // For importing shared components (like PageLayout) from common library
      // Use the source path - these will be compiled by plugin's webpack using React from plugin's node_modules
      // Note: Paths use src/assets/ structure since Strauss copies from src/
      '@flux-plugins-common': path.join(commonLibDir, 'src/assets/js/src'),
      // Alias for images directory in common library
      '@flux-plugins-common/images': path.join(commonLibDir, 'src/assets/images'),
    },
  },
  module: {
    // Module rules are inherited from baseConfig
    // Additional plugin-specific rules can be added here if needed
    rules: [
      ...baseConfig.module.rules,
    ],
  },
  plugins: [
    new HtmlWebpackPlugin({
      template: './assets/js/src/admin/index.html',
      filename: 'admin.html',
      chunks: ['admin'],
    }),
  ],
  devServer: {
    static: {
      directory: path.join(__dirname, 'assets/js/dist'),
    },
    compress: true,
    port: 3002, // Different port from other Flux plugins (3000, 3003, etc.)
    hot: true,
    headers: {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
      'Access-Control-Allow-Headers': 'X-Requested-With, content-type, Authorization',
    },
  },
  externals: {
    ...baseConfig.externals,
  },
};
