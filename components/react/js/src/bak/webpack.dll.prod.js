var path = require("path");
var webpack = require("webpack");

module.exports = {
    entry: {
        react: [path.join(__dirname, "vendors.all.js")],
        vendor: [path.join(__dirname, "vendors.vendor.js")],
    },
    output: {
        path: path.join(__dirname, "dll"),
        filename: "dll.prod.[name].js",
        library: "[name]"
    },
    plugins: [
        new webpack.DllPlugin({
            path: path.join(__dirname, "dll", "[name]-prod-manifest.json"),
            name: "[name]",
            context: path.resolve(__dirname, "client")
        }),
        new webpack.optimize.UglifyJsPlugin(),
        new webpack.DefinePlugin({
            'process.env': {
                'NODE_ENV': JSON.stringify('production')
            }
        })
    ],
    module: {
        loaders: [{
            test: /\.js$/,
            loader: 'babel-loader',
            exclude: /node_modules/
        }, {
            test: /\.css$/,
            use: ['style-loader', 'css-loader?modules']
        }]
    },
    resolve: {
        modules: [path.resolve(__dirname, "src"), "node_modules"]
    }
};