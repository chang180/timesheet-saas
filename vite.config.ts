import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig, loadEnv, type ProxyOptions } from 'vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const tenantStrategy = env.VITE_TENANT_STRATEGY ?? 'subdomain';
    const backendUrl = env.VITE_BACKEND_URL ?? 'http://localhost:8000';
    const devHost = env.VITE_DEV_SERVER_HOST ?? '0.0.0.0';
    const devPort = Number(env.VITE_DEV_SERVER_PORT ?? '5173');

    // In production builds, base should be '/build/' so Vite generates absolute paths
    // for dynamic imports that work correctly when loaded from /build/assets/
    // Only use tenant path prefix in development when using path strategy
    const base =
        mode === 'production'
            ? '/build/'
            : tenantStrategy === 'path'
                ? env.VITE_TENANT_PATH_PREFIX ?? '/'
                : env.VITE_DEV_SERVER_BASE ?? '/';

    const tenantPathRewrite = (path: string): string => path.replace(/^\/[^/]+/, '');

    // Configure proxy based on tenant strategy
    const proxyConfig: Record<string, string | ProxyOptions> =
        tenantStrategy === 'path'
            ? {
                  '^/[^/]+/api': {
                      target: backendUrl,
                      changeOrigin: true,
                      secure: false,
                      rewrite: tenantPathRewrite,
                  },
                  '^/[^/]+/sanctum': {
                      target: backendUrl,
                      changeOrigin: true,
                      secure: false,
                      rewrite: tenantPathRewrite,
                  },
              }
            : {
                  '/api': {
                      target: backendUrl,
                      changeOrigin: true,
                      secure: false,
                  },
                  '/sanctum': {
                      target: backendUrl,
                      changeOrigin: true,
                      secure: false,
                  },
              };

    return {
        base,
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.tsx'],
                ssr: 'resources/js/ssr.tsx',
                refresh: true,
                // Only enable TLS detection in development mode (not production)
                // Can be overridden via VITE_DETECT_TLS environment variable
                ...(mode !== 'production' && {
                    detectTls: env.VITE_DETECT_TLS || 'timesheet-saas.test',
                }),
                buildDirectory: 'build',
            }),
            react({
                babel: {
                    plugins: ['babel-plugin-react-compiler'],
                },
            }),
            tailwindcss(),
            wayfinder({
                formVariants: true,
            }),
        ],
        esbuild: {
            jsx: 'automatic',
        },
        server: {
            host: devHost,
            port: devPort,
            proxy: proxyConfig,
        },
        preview: {
            host: devHost,
            port: Number(env.VITE_PREVIEW_PORT ?? '4173'),
        },
        build: {
            assetsDir: 'assets',
            rollupOptions: {
                output: {
                    assetFileNames: 'assets/[name].[hash].[ext]',
                    chunkFileNames: 'assets/[name].[hash].js',
                    entryFileNames: 'assets/[name].[hash].js',
                },
            },
        },
    };
});
