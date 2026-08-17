import {defineConfig} from "@rspack/cli";
import {Compilation, Compiler, Configuration} from "@rspack/core";
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
                    const file = entrypoint
                        .getFiles()
                        ?.find((file: string) => file.endsWith(".js") || file.endsWith(".css")) || '';
                    const key : "js" | "css" = file.endsWith(".js") ? "js" : "css";
                    const name = entrypoint.name || entryName;
                    const files = entrypoint.getFiles().map((file: string) => {
                        const asset = compilation.getAsset(file);
                        return {
                            file,
                            size: asset ? asset.source.size() : 0
                        };
                    });
                    const size = files.reduce((acc: number, file: { size: number }) => acc + file.size, 0);
                    if (!manifest[entryName]) {
                        manifest[entryName] = {};
                    }
                    manifest[entryName][key] = {
                        name,
                        entryName,
                        file,
                        size,
                        files
                    };
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

glob.sync('assets/js/*.{ts,tsx,js,jsx}', {
    ignore: ['**/node_modules/**', '**/vendor/**']
}).forEach((file: string) => {
    const name = path.basename(file, path.extname(file));
    files[name] = `./${file}`;
});

// noinspection JSUnusedGlobalSymbols
export default defineConfig((env, _argv): Configuration => {
    const isProduction = (env.RSPACK_SERVE ? 'development' : 'production') === "production";
    console.log(`Building for ${isProduction ? "production" : "development"} mode...`);
    return {
        mode: isProduction ? "production" : "development",
        context: __dirname,
        entry: files,
        output: {
            path: path.resolve(__dirname, "dist"),
            filename: isProduction ? "js/[name].[contenthash:8].js" : "js/[name].js",
            clean: true,
            publicPath: "/"
        },
        devtool: isProduction ? "source-map" : "cheap-module-source-map",
        resolve: {
            extensions: [".ts", ".tsx", ".js", ".jsx", ".json"]
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
                }
            ]
        },
        plugins: [
            new EntryManifestPlugin()
        ],
        devServer: {
            host: "127.0.0.1",
            port: 3001,
            hot: true,
            liveReload: false,
            static: {
                directory: path.resolve(__dirname, "dist")
            },
            devMiddleware: {
                writeToDisk: true
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

