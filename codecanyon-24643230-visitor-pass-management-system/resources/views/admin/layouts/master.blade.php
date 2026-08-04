<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

@include('admin.layouts.components.head')

<body>
    <audio id="myAudio1">
        <source src="{{asset('beep.mp3')}}" type="audio/mpeg">
    </audio>
    <div id="app">
        <div class="main-wrapper">
            @include('admin.layouts.components.navigation')
            @include('admin.layouts.components.sidebar')

            <!-- Main Content -->
            <div class="main-content">
                @yield('main-content')
            </div>
            @include('admin.layouts.components.footer')
        </div>
    </div>
    @include('admin.layouts.components.script')
    @stack('js')
</body>

</html>
