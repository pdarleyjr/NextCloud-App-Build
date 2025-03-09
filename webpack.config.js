const path = require('path')

module.exports = {
  mode: process.env.NODE_ENV === 'production' ? 'production' : 'development',
  entry: {
    main: path.join(__dirname, 'src', 'main.js')
  },
  output: {
    path: path.resolve(__dirname, 'js'),
    publicPath: '/js/',
    filename: '[name].js',
    chunkFilename: '[name].chunk.js'
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env']
          }
        }
      },
      {
        test: /\.css$/,
        use: ['style-loader', 'css-loader']
      }
    ]
  },
  resolve: {
    extensions: ['.js'],
    alias: {
      '@': path.resolve(__dirname, 'src')
    }
  },
  devtool: process.env.NODE_ENV === 'production' ? 'source-map' : 'eval-source-map'
}