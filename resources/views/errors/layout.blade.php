<!DOCTYPE html>
<html lang="en-US" dir="ltr" data-navbar-horizontal-shape="default">
<head>
    @include('partials.head')
    <title>@yield('title')</title>
</head>
<body>
    <main class="main" id="top">
        <div class="px-3">
            <div class="row min-vh-100 flex-center p-5">
                <div class="col-12 col-xl-10 col-xxl-8">
                    <div class="row justify-content-center align-items-center g-5">
                        <div class="col-12 col-lg-6 text-center order-lg-1">
                            <img class="img-fluid w-lg-100 d-dark-none" src="@yield('image', asset('theme/img/spot-illustrations/404-illustration.png'))" alt="" width="400">
                            <img class="img-fluid w-md-50 w-lg-100 d-light-none" src="@yield('image_dark', asset('theme/img/spot-illustrations/dark_404-illustration.png'))" alt="" width="540">
                        </div>
                        <div class="col-12 col-lg-6 text-center text-lg-start">
                            <img class="img-fluid mb-6 w-50 w-lg-75 d-dark-none" src="@yield('image_small', asset('theme/img/spot-illustrations/404.png'))" alt="">
                            <img class="img-fluid mb-6 w-50 w-lg-75 d-light-none" src="@yield('image_small_dark', asset('theme/img/spot-illustrations/dark_404.png'))" alt="">
                            <h2 class="text-body-secondary fw-bolder mb-3">@yield('title')</h2>
                            <p class="text-body mb-5">@yield('message')</p>
                            <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-lg-start">
                                <a class="btn btn-lg btn-primary" href="{{ url('/') }}">Go Home</a>
                                <a class="btn btn-lg btn-secondary" href="{{ url()->previous() }}">Go Back</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    @include('partials.scripts')
</body>
</html>
