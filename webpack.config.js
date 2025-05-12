const path = require('path');

module.exports = {
  entry: path.resolve(__dirname, 'js/index.js'),
  mode: process.env.NODE_ENV === 'production' ? 'production' : 'development',
  output: {
    path: path.resolve(__dirname, 'public/js'),
    filename: 'bundle.js'
  },
  resolve: {
    extensions: ['.ts', '.tsx', '.js', '.jsx']
  },
  module: {
    rules: [
      {
        test: /\.(ts|tsx|js|jsx)$/,
        exclude: /node_modules/,
        loader: require.resolve('babel-loader'),
        options: {
          presets: [
            require.resolve('@babel/preset-env'),
            require.resolve('@babel/preset-react'),
            require.resolve('@babel/preset-typescript')
          ]
        }
      }
    ]
  }
};

//workarround of a webpack bug not allowing to exit webpack regularly
process.once('SIGINT', () => {
    console.log("")
    process.exit();
});
