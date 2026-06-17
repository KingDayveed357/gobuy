<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'gobuy')</title>

<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('theme/img/favicons/favicon-32x32.png') }}">
<link rel="shortcut icon" type="image/x-icon" href="{{ asset('theme/img/favicons/favicon.ico') }}">

<script src="{{ asset('theme/vendors/simplebar/simplebar.min.js') }}"></script>
<script src="{{ asset('theme/js/config.js') }}"></script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
<link href="{{ asset('theme/vendors/simplebar/simplebar.min.css') }}" rel="stylesheet">
<link href="{{ asset('theme/vendors/swiper/swiper-bundle.min.css') }}" rel="stylesheet">
<link href="{{ asset('theme/css/theme.min.css') }}" type="text/css" rel="stylesheet" id="style-default">
<link href="{{ asset('theme/css/user.min.css') }}" type="text/css" rel="stylesheet" id="user-style-default">
<link href="{{ asset('theme/css/gobuy.css') }}" type="text/css" rel="stylesheet">
@stack('styles')
