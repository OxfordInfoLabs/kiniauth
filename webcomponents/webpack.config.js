const path = require('path');
const webpack = require('webpack');
const ROOT = path.resolve(__dirname, 'ts');
const DESTINATION = path.resolve(__dirname, 'dist');

module.exports = {
    context: ROOT,

    entry: {
        'Kiniauth': 'index.ts'
    },

    module: {
        rules: [
            {
                test: /\.tsx?$/,
                use: [{
                    loader: 'ts-loader',
                    options: {
                        "allowTsInNodeModules": true
                    }

                },
                    'uglify-template-string-loader']
            }
        ]
    },

    output: {
        library: 'Kiniauth',
        libraryTarget: 'umd',
        libraryExport: 'default',
        filename: 'kiniauth.js',
        path: DESTINATION
    },
    resolve: {
        extensions: ['.ts', '.js'],
        modules: [
            ROOT,
            'node_modules'
        ]
    },
    mode: "production"
};

