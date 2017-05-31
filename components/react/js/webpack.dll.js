var path = require("path");
var webpack = require("webpack");
var rimraf = require("rimraf");
rimraf(path.resolve(__dirname, 'dll'), () => {
    console.log("Dir Deleted");
});



module.exports = {
    entry: {
        vendor: [path.join(__dirname, "webpack.dll-import.js")]
    },
    output: {
        path: path.join(__dirname, "dll"),
        filename: "dll.[name].js",
        library: "[name]"
    },
    plugins: [
        new webpack.DllPlugin({
            path: path.join(__dirname, "dll", "[name]-manifest.json"),
            name: "[name]",
            context: path.resolve(__dirname, "client")
        }),
        new webpack.optimize.UglifyJsPlugin(), //minify everything
        new webpack.optimize.AggressiveMergingPlugin(), //Merge chunks
        new webpack.DefinePlugin({
            'process.env': {
                'NODE_ENV': JSON.stringify('production')
            }
        })
    ],
    resolve: {
        modules: ["node_modules"]
    }
};