// import { defineConfig } from 'vite';
// import laravel from 'laravel-vite-plugin';
// import { resolve } from 'path';

// export default defineConfig({
//     plugins: [
//         laravel({
//             input: [
//                 'resources/css/app.css',
//                 'resources/js/app.js',
//             ],
//             refresh: true,
//         }),
//     ],
//     base: process.env.NODE_ENV === 'production' ? 'https://training1.berkeleyme.com/' : '/',
//     resolve: {
//         alias: {
//             '@': resolve(__dirname, 'resources/js'),
//             // Alias for fonts directory
//             'fonts': resolve(__dirname, 'resources/fonts')
//         },
//     },
//     css: {
//         postcss: {
//             plugins: [
//                 require('tailwindcss'),
//                 require('autoprefixer'),
//             ],
//         },
//     },
//     // Asset handling configuration
//     build: {
//         assetsInlineLimit: 0, // To prevent small assets from being inlined
//     },
//     optimizeDeps: {
//         // Include your font files here
//         include: ['**/*.ttf', '**/*.woff', '**/*.woff2'],
//     },
// });

import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
});

