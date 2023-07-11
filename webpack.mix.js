const mix = require('laravel-mix');

mix.setResourceRoot('../');
mix.js('client/main.js', 'dist/js/main.js');

