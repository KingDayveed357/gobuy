@extends('errors.layout')

@section('title', 'Access Forbidden!')

@section('message')
    Halt! Thou art endeavouring to trespass upon a realm not granted unto thee.
@endsection

@section('image', asset('theme/img/spot-illustrations/403-illustration.png'))
@section('image_dark', asset('theme/img/spot-illustrations/dark_403-illustration.png'))
@section('image_small', asset('theme/img/spot-illustrations/403.png'))
@section('image_small_dark', asset('theme/img/spot-illustrations/dark_403.png'))
