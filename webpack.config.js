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
            presets: ['@babel/preset-env'],
            cacheDirectory: true
          }
        }
      },
      {
        test: /\.css$/,
        use: [
          {
            loader: 'style-loader',
            options: {
              injectType: 'styleTag'
            }
          },
          {
            loader: 'css-loader',
            options: {
              esModule: false,
              modules: {
                namedExport: false,
                localIdentName: '[name]__[local]--[hash:base64:5]'
              }
            }
          }
        ]
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