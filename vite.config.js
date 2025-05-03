import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import fs from 'fs'; 

const host = 'city-explorer';
// Paths to the separate cert and key files inside the container
const certPath = '/etc/ssl/certs/project/city-explorer.pem';
const keyPath = '/etc/ssl/certs/project/city-explorer-key.pem';

export default defineConfig({
    server: { 
        host: '0.0.0.0', 
        // Configure HTTPS with separate key and cert files if they exist
        https: fs.existsSync(keyPath) && fs.existsSync(certPath) ? {
            key: fs.readFileSync(keyPath),
            cert: fs.readFileSync(certPath),
        } : false, // Fallback if files not found
        hmr: {
            host: host, 
        },
    },
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
