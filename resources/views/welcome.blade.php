<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Hub - JS</title>

        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">
    </head>
    <body class="antialiased">
        @if (\Session::has('success'))
            <div class="alert alert-success border-0">
                <strong>{!! \Session::get('success') !!}</strong>
            </div>
        @endif
        <div class="text-center">
            <button class="btn"><a href="{{ url('oauth/redirect') }}">Get access token</a></button>
            <button class="btn"><a href="{{ url('bigquery') }}">Start data contact</a></button>
            <button class="btn"><a href="{{ url('test-api') }}">Test api</a></button>
            <button class="btn"><a href="{{ url('apiKey') }}">Create Api Token</a></button>
        </div>
    </body>
</html>
