@extends('errors.layout')

@section('title', 'Page Not Found')
@section('message')
    But no worries! Our ostrich is looking everywhere <br class="d-none d-sm-block">while you wait safely.
@endsection

@section('image', asset('theme/img/spot-illustrations/404-illustration.png'))
@section('image_dark', asset('theme/img/spot-illustrations/dark_404-illustration.png'))
@section('image_small', asset('theme/img/spot-illustrations/404.png'))
@section('image_small_dark', asset('theme/img/spot-illustrations/dark_404.png'))
