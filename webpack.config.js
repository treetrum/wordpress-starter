const path = require("path");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CopyWebpackPlugin = require("copy-webpack-plugin");
const ImageminPlugin = require("imagemin-webpack-plugin").default;
const imageminMozjpeg = require("imagemin-mozjpeg");
const autoprefixer = require("autoprefixer");
const CleanTerminalPlugin = require("clean-terminal-webpack-plugin");
const ip = require("ip");
const webpack = require("webpack");
const chokidar = require("chokidar");
const request = require("request");
const devConfig = require("./dev.config");

const isProduction = process.env.NODE_ENV === "production";

module.exports = {
    mode: "development",
    devtool: isProduction ? "" : "source-map",
    entry: {
        bundle: path.join(__dirname, "./src/js/index.js"),
        main: path.join(__dirname, "./src/scss/main.scss"),
        rte: path.join(__dirname, "./src/scss/rte.scss"),
    },
    stats: isProduction ? "normal" : "errors-warnings",
    output: {
        path: path.join(__dirname, "./dist"),
        filename: "js/bundle.js",
        publicPath: `${devConfig.publicPath}/`,
    },
    module: {
        rules: [
            {
                test: /\.jsx?$/,
                exclude: /node_modules/,
                use: [
                    {
                        loader: "babel-loader",
                        options: {
                            presets: ["@babel/env", "@babel/react"],
                            plugins: [
                                "@babel/plugin-proposal-class-properties",
                            ],
                        },
                    },
                    "eslint-loader",
                ],
            },
            {
                test: /\.scss$/,
                use: [
                    {
                        loader: isProduction
                            ? MiniCssExtractPlugin.loader
                            : "style-loader",
                    },
                    {
                        loader: "css-loader",
                        options: {
                            url: true,
                        },
                    },
                    {
                        loader: "postcss-loader",
                        options: {
                            ident: "postcss",
                            plugins: [autoprefixer()],
                        },
                    },
                    {
                        loader: "sass-loader",
                    },
                ],
            },
            {
                test: /\.(gif|png|jpe?g|svg)$/i,
                use: [
                    {
                        loader: "file-loader",
                        options: {
                            name: "images/[name].[ext]",
                        },
                    },
                    {
                        loader: "image-webpack-loader",
                        options: {
                            bypassOnDebug: true, // webpack@1.x
                            disable: true, // webpack@2.x and newer
                        },
                    },
                ],
            },
            {
                test: /\.(woff|woff2|ttf|otf)$/i,
                use: [
                    {
                        loader: "file-loader",
                        options: {
                            name: "fonts/[name].[ext]",
                        },
                    },
                ],
            },
        ],
    },
    plugins: [
        new webpack.EnvironmentPlugin(["NODE_ENV"]),
        new webpack.HotModuleReplacementPlugin(),
        new MiniCssExtractPlugin({
            filename: "css/[name].css",
            chunkFilename: "css/[id].css",
        }),
        new CopyWebpackPlugin([
            {
                from: "./src/images/",
                to: "images",
            },
        ]),
        new ImageminPlugin({
            test: /\.(jpe?g|png|gif|svg)$/i,
            options: {},
            plugins: [
                imageminMozjpeg({
                    quality: 80,
                    progressive: true,
                }),
            ],
        }),
        new CleanTerminalPlugin({
            message: `
 ==========================================
 Dev server running on http://${devConfig.host}:${devConfig.port}
 ------------------------------------------
 ${
     devConfig.host === "0.0.0.0"
         ? `LOCAL: http://localhost:${devConfig.port}`
         : ""
 }
 ${
     devConfig.host === "0.0.0.0"
         ? `NETWORK: http://${ip.address()}:${devConfig.port}`
         : ""
 }
 ==========================================
                `,
        }),
    ],
    devServer: {
        open: true,
        useLocalIp: true,
        contentBase: path.join(__dirname, "./dist"),
        compress: true,
        port: devConfig.port,
        overlay: {
            errors: true,
            warnings: false,
        },
        host: devConfig.host,
        hot: true,
        index: "",
        proxy: {
            context: () => true,
            target: devConfig.proxyURL,
            changeOrigin: true,
            hostRewrite: true,
            autoRewrite: true,
            protocolRewrite: true,
            followRedirects: true,
            onProxyRes: (proxyRes, req, res) => {
                // console.log(proxyRes);

                // proxyRes.statusMessage

                let originalBody = Buffer.from([]);
                proxyRes.on("data", data => {
                    originalBody = Buffer.concat([originalBody, data]);
                });
                proxyRes.on("end", () => {
                    const bodyString = originalBody.toString();

                    const hostRegex = new RegExp(`${devConfig.proxyURL}`, "g");
                    const escapedHostRegex = new RegExp(
                        `${devConfig.proxyURL.replace(/\//g, "\\\\/")}`,
                        "g",
                    );

                    const requestHost = `${req.protocol}://${req.get("host")}`;
                    const escapedRequestHost = requestHost.replace(
                        /\//g,
                        "\\/",
                    );

                    const newBody = bodyString
                        .replace(hostRegex, requestHost) // Replace path so networked clients can access content
                        .replace(escapedHostRegex, escapedRequestHost); // ^^ ditto but escaped versions
                    res.set(proxyRes.headers);
                    res.status(proxyRes.statusCode);

                    // Let request do the heavy lifting for images - I can't work it out
                    if (
                        proxyRes.headers["content-type"] &&
                        proxyRes.headers["content-type"].includes("image")
                    ) {
                        const fullUrl = `${devConfig.proxyURL}${req.url}`;
                        request(fullUrl).pipe(res);
                    } else {
                        res.end(newBody);
                    }
                });
            },
            selfHandleResponse: true,
        },
        writeToDisk: true,
        before: (app, server) => {
            chokidar.watch(["**/*.php", "**/*.twig"]).on("all", () => {
                server.sockWrite(server.sockets, "content-changed");
            });
        },
    },
};
