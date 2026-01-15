<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="dark" data-header-styles="dark" data-width="fullwidth" data-menu-styles="dark" data-page-style="flat" data-toggled="close"  data-vertical-style="doublemenu" data-toggled="double-menu-open">

    <head>

        <!-- Meta Data -->
        <meta charset="UTF-8">
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="Description" content="Laravel Bootstrap Responsive Admin Web Dashboard Template">
        <meta name="Author" content="Spruko Technologies Private Limited">
    
        <!-- Title-->
        <title> {{ config('app.name', 'Domain Monitor Pro') }} </title>
        
        <!-- Favicon -->
        {{-- <link rel="icon" href="{{asset('build/assets/images/brand-logos/favicon.ico')}}" type="image/x-icon"> --}}
        <link rel="icon" href="{{asset('img/2.jpg')}}" type="image/x-icon">
    
        <!-- Main Theme Js -->
        <script src="{{asset('build/assets/main.js')}}"></script>

        <!-- ICONS CSS -->
        <link href="{{asset('build/assets/icon-fonts/icons.css')}}" rel="stylesheet">

        @include('layouts.components.styles')
      
        <!-- APP CSS & APP SCSS -->
        @vite(['resources/sass/app.scss'])

        @yield('styles')

    </head>

    <body class="">

        <div class="progress-top-bar"></div>

        <!-- Start::main-switcher -->
        @include('layouts.components.switcher')
        <!-- End::main-switcher -->

        <!-- Loader -->
        <div id="loader" >
            <img src="{{asset('build/assets/images/media/loader.svg')}}" alt="">
        </div>
        <!-- Loader -->

        <div class="page">

            <!-- Start::main-header -->
            @include('layouts.components.main-header')
            <!-- End::main-header -->

            <!-- Start::main-sidebar -->
            @include('layouts.components.main-sidebar')
            <!-- End::main-sidebar -->

            <!-- Start::app-content -->
            <div class="main-content app-content">
                <div class="container-fluid page-container main-body-container">

                    @yield('content')
                    
                </div>
            </div>
            <!-- End::content  -->

            <!-- Start::main-footer -->
            @include('layouts.components.footer')
            <!-- End::main-footer -->

            <!-- Start::main-modal -->
            @include('layouts.components.modal')
            <!-- End::main-modal -->

            @yield('modals')  

        </div>

        <!-- Scripts -->
        @include('layouts.components.scripts')

        <!-- Sticky JS -->
        <script src="{{asset('build/assets/sticky.js')}}"></script>

        <!-- Custom-Switcher JS -->
        @vite('resources/assets/js/custom-switcher.js')

        <!-- App JS-->
        @vite('resources/js/app.js')

        <!-- End Scripts -->

    </body> 

</html>
