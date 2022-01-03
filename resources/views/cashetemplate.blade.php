<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>CreditLinks</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Nunito', sans-serif;
            }
        </style>
    </head>
    <body class="antialiased">
        <div>
            Hi {{ $firstName }},<br/><br/>Thank you for selecting CASHe!<br/>
            <p>
            <div>To continue your loan application you must download CASHe’s mobile app. </div>
            <div> You can download now from:</div>
            <p style='display: flex; align-items:center;'><a href='{{ $casheDownloadUrl }}' ><img alt='Get it on Google Play' width='142' src='https://play.google.com/intl/en_us/badges/static/images/badges/en_badge_web_generic.png' /></a><a href='{{ $casheDownloadUrl }}' ><img alt='Get it on Google Play' height='35' style='padding: 9px 0;' width='142' src='https://1000logos.net/wp-content/uploads/2020/08/apple-app-store-logo.jpg' /></a></p>
            </p><br />
            <p>
            <div>CreditLinks Team </div>
        </div>
    </body>
</html>
