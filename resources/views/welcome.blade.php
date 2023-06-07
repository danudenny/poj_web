<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" href="/favicon.png">
        <title>POJ</title>

        @vite(['resources/js/src/main.js', 'resources/js/src/assets/scss/app.scss'])
    </head>
    <body class="antialiased">
        <div id="app">

        </div>
    </body>
</html>
