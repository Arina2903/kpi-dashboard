import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import path from 'node:path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        tailwindcss(),
        react(),
    ],
    resolve: {
        alias: {
            // Lets every Platform page import shared components as
            // '@/Components/Platform/...' regardless of how deeply nested
            // the page itself is (Pages/Platform/Import/Show.tsx vs.
            // Pages/Platform/Dashboard.tsx need a different number of `../`
            // segments to reach the same file with a relative import — the
            // exact mistake that kept happening while building the shared
            // Platform layout/component set).
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
    server: {
        // Explicit IPv4 loopback: Node's default 'localhost' binding on this
        // machine resolves to IPv6 (::1) only, which the browser can't
        // always reach even though other tools (e.g. curl) can -- causing
        // the injected @vite/client and entry-point <script> tags to fail
        // loading silently, with no visible error, just a blank page.
        host: '127.0.0.1',
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
