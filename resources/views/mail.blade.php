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
            <p> Dear Customer,</p>
            <p> Here is your one-time password</p>
            <p style="padding: 5px 5px 5px 10px;letter-spacing: 10px;border: 2px solid skyblue;width: 122px;text-align: center;font-weight: 700;color: #739feb;">{{ $otp }}</p>
            <br/>
            <p>Use this to complete your account profile with CreditLinks. We will never contact you for this code
            <br>Do not reveal it with anyone else.</p>
            <p>Regards,<br>
            The CreditLinks Team</p>
        </div>
    </body>
</html>
