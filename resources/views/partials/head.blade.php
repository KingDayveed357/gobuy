<meta charset="utf-8">
<script>document.documentElement.classList.add('js')</script>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Quintessential Mart')</title>
<link rel="canonical" href="{{ url()->current() }}">
@stack('meta')

{{-- Quintessential Mart favicons. The SVG is the master; PNG/ICO are fallbacks
     for older browsers. Export the raster sizes per docs/branding.md. --}}
<link rel="icon" type="image/svg+xml" href="{{ asset('branding/favicons/favicon.svg') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('theme/img/favicons/favicon-32x32.png') }}">
<link rel="shortcut icon" type="image/x-icon" href="{{ asset('theme/img/favicons/favicon.ico') }}">
<link rel="mask-icon" href="{{ asset('branding/favicons/mask-icon.svg') }}" color="#3874ff">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="apple-touch-icon" href="{{ asset('theme/img/favicons/apple-touch-icon.png') }}">
<meta name="theme-color" content="#3874ff">

{{-- Open Graph / Twitter — social share previews (Facebook, LinkedIn, X, WhatsApp).
     og:image expects a 1200x630 PNG exported from branding/social/og-image.svg. --}}
@php($ogTitle = trim($__env->yieldContent('title', setting('store_name', 'Quintessential Mart'))))
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ setting('store_name', 'Quintessential Mart') }}">
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="@yield('meta_description', 'Premium commerce, delivered. Shop trusted brands with fast, reliable delivery.')">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ asset('branding/social/og-image.png') }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $ogTitle }}">
<meta name="twitter:description" content="@yield('meta_description', 'Premium commerce, delivered. Shop trusted brands with fast, reliable delivery.')">
<meta name="twitter:image" content="{{ asset('branding/social/og-image.png') }}">

<script src="{{ asset('theme/vendors/simplebar/simplebar.min.js') }}"></script>
<script src="{{ asset('theme/js/config.js') }}"></script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
<link href="{{ asset('theme/vendors/simplebar/simplebar.min.css') }}" rel="stylesheet">
<link href="{{ asset('theme/vendors/swiper/swiper-bundle.min.css') }}" rel="stylesheet">
<link href="{{ asset('theme/css/theme.min.css') }}" type="text/css" rel="stylesheet" id="style-default">
<link href="{{ asset('theme/css/user.min.css') }}" type="text/css" rel="stylesheet" id="user-style-default">
<link href="{{ asset('theme/css/gobuy.css') }}" type="text/css" rel="stylesheet">
<link href="{{ asset('theme/css/mega-menu.css') }}" type="text/css" rel="stylesheet">
@stack('styles')
