const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.sass('resources/stylesheets/ladder.scss', 'public/css')
    .postCss('public/css/ladder.css', 'public/css', [
        require('postcss-import'),
        require('autoprefixer'),
    ])
    .options({
        processCssUrls: false
    });

