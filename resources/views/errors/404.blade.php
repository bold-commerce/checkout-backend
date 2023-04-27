<!doctype html>
<html lang="en">
<head>
    <meta name="robots" content="noindex,nofollow">
    <meta charset="utf-8">
    <title>Error</title>
    <link rel="stylesheet" href="{{ URL::asset('css/normalize.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('css/errorstyle.css') }}">
    <link rel="stylesheet" href="/css/icon.css">
    <script src="https://use.fontawesome.com/c933859f35.js"></script>
    <!--[if lt IE 9]>
    <script src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
</head>

<body>
<div class="middle">
    <div class="Overlay">
        <div class="message">
            <i class="error-icon icon-error-triangle loader"></i>
            <h1>Page Not Found</h1>
            <button class="Button Button--Primary--Overlay Button--Small"
                    onclick=window.history.back()>
                Go Back
            </button>
        </div>
    </div>
</div>
</body>

</html>
