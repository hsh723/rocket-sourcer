import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      '@': resolve(__dirname, './resources/js'),
      '@components': resolve(__dirname, './resources/js/components'),
      '@pages': resolve(__dirname, './resources/js/pages'),
      '@hooks': resolve(__dirname, './resources/js/hooks'),
      '@services': resolve(__dirname, './resources/js/services'),
      '@context': resolve(__dirname, './resources/js/context'),
      '@layouts': resolve(__dirname, './resources/js/layouts'),
      '@styles': resolve(__dirname, './resources/scss')
    }
  },
  server: {
    port: 3000,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true
      }
    }
  },
  build: {
    outDir: 'public/build',
    assetsDir: 'assets',
    manifest: true,
    rollupOptions: {
      input: 'resources/js/main.tsx'
    }
  }
}); 