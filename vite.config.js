import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    return {
        plugins: [
            laravel({
                input: [
                    'resources/css/app.css',
                    'resources/js/app.js',
                ],
                refresh: true,
            }),
        ],

        server: {
            host: env.VITE_DEV_SERVER_HOST || '127.0.0.1',
            cors: true,
            hmr: {
                host: env.VITE_HMR_HOST || 'localhost',
            },
        },
    };
});
