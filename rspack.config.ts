// noinspection HttpUrlsUsage

import {defineConfig} from "@rspack/cli";
import {Compilation, Compiler, Configuration, SwcJsMinimizerRspackPlugin} from "@rspack/core";
import {fileURLToPath} from "node:url";
import {glob} from "glob";
import path from "node:path";
import fs from "node:fs";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

type ManifestName = {
    name: string;
    entryName: string;
    file: string;
    size: number;
    files: {
        file: string;
        size: number;
    }[];
}
type Manifest = Record<string, {
    css?: ManifestName;
    js?: ManifestName;
}>

const serverFile = __dirname + '/.server.json';
type ServerJsonType = {
    host: string;
    port: number;
    url: string;
    environment: "development" | "production";
};

class EntryManifestPlugin {
    apply(compiler: Compiler) {
        compiler.hooks.thisCompilation.tap("EntryManifestPlugin", (compilation: Compilation) => {
            const stage = compiler.webpack.Compilation.PROCESS_ASSETS_STAGE_REPORT;
            compilation.hooks.processAssets.tap({name: "EntryManifestPlugin", stage}, () => {
                const manifest: Manifest = {};

                for (const [entryName, entrypoint] of compilation.entrypoints) {
                    if (!manifest[entryName]) {
                        manifest[entryName] = {};
                    }

                    const name = entrypoint.name || entryName;
                    const allFiles = entrypoint.getFiles();

                    const filesDetails = allFiles.map((file: string) => {
                        const asset = compilation.getAsset(file);
                        return {
                            file,
                            size: asset ? asset.source.size() : 0
                        };
                    });
                    const jsFiles = filesDetails.filter(({file}) => file.endsWith(".js"));
                    const jsFile = jsFiles.map(({file}) => file)[0];
                    const cssFiles = filesDetails.filter(({file}) => file.endsWith(".css"));
                    const cssFile = cssFiles.map(({file}) => file)[0];
                    if (jsFile) {
                        manifest[entryName]["js"] = {
                            name,
                            entryName,
                            file: jsFile,
                            size: jsFiles.reduce((acc, f) => acc + f.size, 0),
                            files: jsFiles
                        };
                    }

                    if (cssFile) {
                        manifest[entryName]["css"] = {
                            name,
                            entryName,
                            file: cssFile,
                            size: cssFiles.reduce((acc, f) => acc + f.size, 0),
                            files: cssFiles
                        };
                    }
                }

                const source = new compiler.webpack.sources.RawSource(
                    JSON.stringify(manifest, null, 4)
                );
                compilation.emitAsset("manifest.json", source);
            });
        });
    }
}

const files: Record<string, string> = {};
const serverHost = '127.0.0.1';
const serverPort = 3001;
const aggregateTimeout = 500;

glob.sync('assets/js/*.{ts,tsx,js,jsx}', {
    ignore: ['**/node_modules/**', '**/vendor/**']
}).forEach((file: string) => {
    if (file.endsWith('.d.ts')) {
        return;
    }
    const name = path.basename(file, path.extname(file));
    files[name] = `./${file}`;
});

// noinspection JSUnusedGlobalSymbols
export default defineConfig((env, _argv): Configuration => {
    const mode = (env.RSPACK_SERVE ? 'development' : 'production');
    const isProduction = mode === "production";
    console.log(`Building for ${isProduction ? "production" : "development"} mode...`);
    return {
        mode,
        context: __dirname,
        entry: files,
        watchOptions: isProduction ? {} : {
            aggregateTimeout,
            ignored: /^(?!.*[\\/]assets[\\/]).*\.(?!tsx?|css)$/
        },
        optimization: {
            minimize: isProduction,
            minimizer: [
                new SwcJsMinimizerRspackPlugin({
                    minimizerOptions: {
                        compress: {
                            drop_console: isProduction,
                        },
                    },
                }),
            ]
        },
        output: {
            path: path.resolve(__dirname, "dist"),
            clean: true,
            chunkFormat: 'module',
            chunkLoading: 'import',
            module: true,
            library: {
                type: 'modern-module',
            },
            filename: isProduction ? "js/[name].[contenthash:8].js" : "js/[name].js",
            cssFilename: isProduction ? "css/[name].[contenthash:8].css" : "css/[name].css",
            assetModuleFilename: isProduction ? "images/[name].[contenthash:8][ext]" : "images/[name][ext]",
            publicPath: isProduction ? "/" : `http://${serverHost}:${serverPort}/`,
            crossOriginLoading: isProduction ? false : "anonymous",
            uniqueName: "traydigita_headless",
            hotUpdateGlobal: "webpackHotUpdatetraydigita_headless",
        },
        devtool: isProduction ? false : "cheap-module-source-map",
        resolve: {
            extensions: [".ts", ".tsx", ".js", ".jsx", ".json", ".css"]
        },
        module: {
            rules: [
                {
                    test: /\.tsx?$/,
                    exclude: /node_modules/,
                    use: {
                        loader: "builtin:swc-loader",
                        options: {
                            jsc: {
                                parser: {
                                    syntax: "typescript",
                                    tsx: true
                                }
                            }
                        }
                    }
                },
                {
                    test: /\.css$/,
                    use: [
                        {
                            loader: 'postcss-loader',
                            options: {
                                postcssOptions: {
                                    config: true,
                                },
                            },
                        },
                    ],
                    type: 'css/auto',
                },
                {
                    test: /\.(png|jpe?g|gif|webp|svg|heic|avif)$/i,
                    type: 'asset',
                    parser: {
                        dataUrlCondition: {
                            maxSize: 8 * 1024
                        }
                    }
                },
            ]
        },
        plugins: [
            new EntryManifestPlugin()
        ],
        devServer: {
            host: serverHost,
            port: serverPort,
            hot: true,
            liveReload: false,
            // liveReload: true,
            client: isProduction ? {} : {
                webSocketURL: `ws://${serverHost}:${serverPort}/ws`,
                overlay: true,
            },
            headers: {
                "Access-Control-Allow-Origin": "*",
                "Access-Control-Allow-Methods": "GET, POST, PUT, DELETE, PATCH, OPTIONS",
                "Access-Control-Allow-Headers": "X-Requested-With, content-type, Authorization"
            },
            static: {
                directory: path.resolve(__dirname, "dist"),
                watch: false
            },
            devMiddleware: {
                writeToDisk: (filePath) => !filePath.toLowerCase().includes('hot-update')
            },
            allowedHosts: "all",
            onListening: (server) => {
                const address = server.server.address();
                if (address && typeof address === "object") {
                    const host = address.address === "::" ? "localhost" : address.address;
                    const port = address.port;
                    // noinspection HttpUrlsUsage
                    const serverJson: ServerJsonType = {
                        host,
                        port,
                        environment: isProduction ? "production" : "development",
                        url: `http://${host}:${port}`
                    };
                    fs.writeFileSync(serverFile, JSON.stringify(serverJson, null, 4));
                }
                // Cleanup at stop
                const cleanup = () => {
                    if (fs.existsSync(serverFile)) {
                        fs.unlinkSync(serverFile);
                    }
                };
                process.once("SIGINT", cleanup);
                process.once("SIGTERM", cleanup);
                process.once("exit", cleanup);
            }
        }
    };
});

