<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Comme:wght@100;200;300;400;500;600;700;800;900&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Roboto+Condensed:ital,wght@0,300;0,400;0,700;1,300;1,400;1,700&display=swap');

        body {
            padding: 0;
            margin: 0;
            background-color: #F0F0F0;
            font-family: 'Roboto Condensed', 'Comme', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }

        .main-container {
            width: 100%;
            background-color: #F0F0F0;
            border-top: 1.4em solid #00425F;
            padding-top: 50px;
        }

        .body-container {
            width: 100%;
        }

        .message-container {
            background-color: #FFFFFF;
            border-radius: 10px;
            width: 50%;
            padding: 20px 10px;
            margin: 5px auto;
            margin-bottom: 50px;
            text-align: center;
        }

        h1 {
            color: #05748a;
            font-size: 2em;
            width: 80%;
            margin: 10px auto;
        }

        p.main-message {
            color: #00425F;
            margin: 3em auto 2%;
            width: 80%;
            font-size: 1.5em;
            font-weight: 500;
            text-align: left;
            overflow-wrap: break-word;
        }

        p.main-message a {
            color: #0275D8;
            text-decoration: none;
        }

        ol {
            color: #00425F;
            margin: 1em auto 0;
            width: 80%;
            font-size: 1.3em;
            font-weight: 500;
            text-align: left;
            overflow-wrap: break-word;
        }

        ol li {
            overflow-wrap: break-word;
            padding: 4px 0;
        }

        p.quick-message {
            color: #00425F;
            margin: .5em auto;
            width: 80%;
            font-size: 1em;
            font-weight: 500;
            text-align: left;
        }

        a.message-button {
            display: inline-block;
            margin: 30px 0;
            font-size: 1em;
            background: #479FC8;
            color: #ffffff;
            padding: 16px 80px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 8px;
        }

        a.message-button:hover {
            background: #032535;
        }

        p.help-message {
            color: #9F9F9F;
            font-size: 1em;
            font-weight: 500;
            text-align: center;
        }

        p.help-message a {
            color: #00B8DE;
            text-decoration: none;
        }

        p.copy-message {
            color: #9F9F9F;
            font-size: 1em;
            font-weight: 500;
            text-align: center;
            margin: 50px auto;
        }

        p.best-regards {
            color: #405c75;
            font-size: 1em;
            font-weight: 500;
            text-align: left;
            width: 80%;
            margin: 20px auto 12px;
        }

        @media (max-width: 1200px) {
            .message-container {
                width: 75%;
            }
        }

        @media (max-width: 750px) {
            .message-container {
                width: 90%;
            }
        }
    </style>
</head>

<body>
    <div class="main-container">
        <div class="body-container">
            <div class="message-container">
                @yield('mail_content')
                <p class="best-regards">
                    Best regards,<br />
                    The Afrelib Team
                </p>
            </div>
            <p class='help-message'>Need Help? Visit our <a href=''>Help Center</a></p>
            <p class="copy-message">Copyright (&copy;) 2023 Afrelib . All rights reserved. </p>
        </div>
    </div>
</body>

</html>
