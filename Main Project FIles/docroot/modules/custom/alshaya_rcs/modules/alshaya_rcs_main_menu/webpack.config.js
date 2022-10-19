var path = require("path");
const buildPath = './dist/';

const config = {
  entry: {
    alshaya_rcs_main_menu_renderer: './js/alshaya_rcs_main_menu_renderer.es5.js',
  },
  output: {
    path: path.resolve(__dirname, 'dist'),
    filename: '[name].bundle.js',
    publicPath: buildPath
  },
  devServer: {
    contentBase: './',
    publicPath: buildPath
  },
};

module.exports = (env, argv) => {
  if (argv.mode === 'development') {
    config.devtool = 'source-map';
    config.externals = {};
  }
  return config;
};
