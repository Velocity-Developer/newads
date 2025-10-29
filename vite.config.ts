import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig, loadEnv } from 'vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const version = env.APP_VERSION || 'dev';
    const isProd = mode === 'production';

    return {
        plugins: [
            laravel({
                input: ['resources/js/app.ts'],
                ssr: 'resources/js/ssr.ts',
                refresh: true,
            }),
            tailwindcss(),
            // Aktifkan Wayfinder hanya saat dev, nonaktifkan saat production
            ...(isProd ? [] : [wayfinder({ formVariants: true })]),
            vue({
                template: {
                    transformAssetUrls: { base: null, includeAbsolute: false },
                },
            }),
        ],

        server: {
            host: '127.0.0.1',
            port: 5193,
            strictPort: true,
        },

        define: {
            __APP_VERSION__: JSON.stringify(version),
        },

        build: {
            rollupOptions: {
                output: {
                    entryFileNames: `assets/[name]-v${version}-[hash].js`,
                    chunkFileNames: `assets/[name]-v${version}-[hash].js`,
                    assetFileNames: `assets/[name]-v${version}-[hash].[ext]`,
                },
            },
        },
    };
});
