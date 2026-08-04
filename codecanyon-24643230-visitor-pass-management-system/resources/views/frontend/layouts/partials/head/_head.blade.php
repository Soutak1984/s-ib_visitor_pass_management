<!-- Head -->

<head>
    <!-- Meta Tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Title and Favicon -->
    <title>Visitor Pass Management - @yield('title')</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/site_logo2.png') }}">

    <!-- Vite CSS -->
    @vite('resources/css/app.css')

    <!-- FontAwesome CSS -->
    <link rel="stylesheet" href="{{ asset('frontend/fonts/fontawesome/css/fontawesome.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/fonts/fontawesome/css/brands.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/fonts/fontawesome/css/solid.css') }}">

    <!-- Typography -->
    <link rel="stylesheet" href="{{ asset('frontend/fonts/typography/plus-JAKARTA-SANS/PlusJakartaSans.css') }}">

    <!-- Plugins and Libraries CSS -->
    <link rel="stylesheet" href="{{ asset('assets/modules/izitoast/dist/css/iziToast.min.css') }}">

    <!-- Custom and Main Styles -->
    <link rel="stylesheet" href="{{ asset('frontend/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/custom.css') }}">

    <!-- Additional CSS from Child Views -->
    @yield('css')
    @stack('css')
</head>

<!-- Head -->