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
            <div>Don't wait, click on the button below to install the CASHe App now</div>
            <div><p style='display: flex; align-items:center;'><a href='{{ $casheDownloadUrl }}' style="text-decoration: none;
    display: inline-block;
    color: #ffffff;
    background-color: #14689d;
    border-radius: 4px;
    width: auto;
    width: auto;
    font-size: 16px;
    padding: 9px 20px;
    border-top: 1px solid #14689d;
    border-right: 1px solid #14689d;
    border-bottom: 1px solid #14689d;
    border-left: 1px solid #14689d;
    font-family: Arial,Helvetica Neue,Helvetica,sans-serif;
    text-align: center;
    word-break: keep-all;">Install Now!</a></p></div>
            </p><br />
            <p>
            <div>CreditLinks Team </div>
        </div>
    </body>
</html>
