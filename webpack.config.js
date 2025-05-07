module.exports = {
  entry: './js/index.js',
  mode: process.env.NODE_ENV === 'production' ? 'production' : 'development',
  output: {
    path: __dirname + '/public/js'
  },
  module: {
    rules: [
        {test: /\.js$/, use: "babel-loader"}
    ]
  }
};

//workarround of a webpack bug not allowing to exit webpack regularly
process.once('SIGINT', () => {
    console.log("")
    process.exit();
});
