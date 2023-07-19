import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import svgLoader from 'vite-svg-loader';
import svgPlugin from 'vite-plugin-svg';

export default defineConfig({
    plugins: [
        laravel(
            {
            input: ['resources/js/src/main.js', 'resources/js/src/assets/scss/app.scss'],
            refresh: true,
            }
        ),
        svgLoader({defaultImport: 'url'}),
        svgPlugin(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false
                }
            }
        }),

    ],
    resolve: {
        loader: {
            '.js': 'jsx'
        },
        alias: {
            '@': '/resources/js/src',
            '@components': '/resources/js/src/components',
            '@assets': '/resources/js/src/assets'
        },
    },
    build: {
        transpile: ['vue', 'vite']

    },
    // server : {
    //     hmr: {
    //         host: '127.0.0.1'
    //         // overlay: false
    //     }
    // }
    server: {
        proxy: {
          '/api': {
            target: 'http://localhost:8000',
            changeOrigin: true,
          },
        },
    },
});
