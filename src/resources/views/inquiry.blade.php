<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>お問い合わせ - {{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">

        @vite(['resources/js/inquiry.js'])
    </head>
    <body>
        <div id="inquiry-app"></div>
    </body>
</html>
