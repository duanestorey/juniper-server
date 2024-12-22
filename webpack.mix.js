// webpack.mix.js

let mix = require('laravel-mix');
mix.js( 'src/juniper.js', '_public/dist' ).setPublicPath( '_public/dist' );
mix.sass( 'src/juniper.scss', '_public/dist' );

