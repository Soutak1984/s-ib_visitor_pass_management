<!DOCTYPE>
<html>

@include('frontend.layouts.partials.head._head')
<header class="mt-6">
    <div class="container">
        <div class="flex justify-between items-center py-2 px-2 backdrop-blur-xl bg-[rgba(255,255,255,0.6)] rounded-[32px]">
            @if(setting('site_logo'))
            <a href="{{route('/')}}" class="w-28">
                <img src="{{ asset('images/'.setting('site_logo')) }}" data-inject-svg="" alt="logo" class="w-full">
            </a>
            @endif
            <div class="lg:flex items-center gap-x-12 hidden">
                <nav class="lg:flex items-center gap-x-12 font-semibold text-lg">
                    <a href="{{route('check-in.pre.registered')}}" class="hover:text-primary">{{__('frontend.have_appoinment')}}</a>
                    <a href="{{route('check-in.return')}}" class="hover:text-primary ">{{__('frontend.been_here_before')}}</a>
                    @if(auth()->user())
                    <a href="{{route('checkout.index')}}" class="hover:text-primary ">{{__('frontend.check_out')}}</a>
                    @endif
                </nav>
                <div class="dropdown">
                    <button class="dropdownbtn flex items-center justify-center  gap-2 rounded-3xl capitalize text-sm font-medium transition text-heading">
                        @foreach ($language as $lang)
                        @if (Session()->has('applocale') and Session()->get('applocale') and setting('locale'))
                        @if (Session()->get('applocale') == $lang->code)
                        <span id="current-lang" class="whitespace-nowrap font-semibold text-lg"> {{ $lang->flag_icon == null ? '🇬🇧' : $lang->flag_icon }}
                            {{ $lang->name }}</span>
                        @endif
                        @else
                        @if (setting('locale') == $lang->code)
                        <span id="current-lang" class="whitespace-nowrap font-semibold text-lg"> {{ $lang->flag_icon == null ? '🇬🇧' : $lang->flag_icon }}
                            {{ $lang->name }}</span>
                        @endif
                        @endif
                        @endforeach
                        <i class="ms-1 fa-solid fa-chevron-down dropdown-icon"></i>
                    </button>

                    <ul class="dropdown-content p-2 min-w-[180px] rounded-lg shadow-xl absolute top-16  z-10 border border-gray-200 bg-white hidden ">
                        @if(!blank($language))
                        @foreach($language as $lang )
                        <li class=" py-1.5 px-2.5 rounded-md cursor-pointer hover:bg-gray-100 list-none">
                            <a href="{{ route('admin.lang.index',$lang->code) }}" data-lang="{{ $lang->flag_icon == null ? '🇬🇧' : $lang->flag_icon }} {{$lang->name}}" class="flex items-center gap-2 font-semibold text-lg"><span>{{ $lang->flag_icon == null ? '🇬🇧' : $lang->flag_icon }} {{ $lang->name }}</span></a>
                        </li>
                        @endforeach
                        @endif

                    </ul>
                </div>
                @if(auth()->user())
                <a href="{{route('admin.dashboard.index')}}"><button class="bg-primary text-white rounded-[23.5px] px-6 py-3 leading-tight text-lg font-semibold">{{__('frontend.go_to_dashboard')}}</button></a>

                @else
                <a href="{{route('login')}}"><button class="bg-primary text-white rounded-[23.5px] px-6 py-3 leading-tight text-lg font-semibold">{{__('frontend.login')}}</button></a>

                @endif
            </div>
            <div id="open-sidebar" class="text-3xl cursor-pointer lg:hidden open-sidebar-button text-primary p-1 bg-primary rounded-md">
                <div class="hamburger"></div>
                <div class="hamburger"></div>
                <div class="hamburger"></div>
            </div>
        </div>
    </div>
</header>

<aside id="sidebar"
    class="fixed inset-0 z-50 w-screen h-screen invisible opacity-0 bg-black/50 transition-all duration-300">
    <div class="w-full bg-white transition-all duration-300 -translate-y-full">
        <div class="flex justify-between items-start p-4">
            @if (setting('site_logo'))
            <a href="{{ route('/') }}" class="w-28">
                <img src="{{ asset('images/' . setting('site_logo')) }}" alt="logo" class="w-full">
            </a>
            @endif
            <span class="cursor-pointer ml-28 lg:hidden text-primary block" id="close-sidebar">
                <i class="fa-regular fa-circle-xmark"></i>
            </span>
        </div>
        <hr class="w-full h-[1px] bg-[#d4d4d4]">
        <div class="p-2.5 w-full ">
            <ul class="flex flex-col text-[18px]">
                <li class="w-full py-2 px-2 hover:bg-primary rounded-md hover:text-white mb-2"><a
                        href="{{ route('check-in.pre.registered') }}">{{ __('frontend.have_appoinment') }}</a>
                </li>
                <li class="w-full py-2 px-2 hover:bg-primary rounded-md hover:text-white mb-2"><a
                        href="{{ route('check-in.return') }}">{{ __('frontend.been_here_before') }}</a></li>
                @if (auth()->user())
                <li class="w-full py-2 px-2 hover:bg-primary rounded-md hover:text-white mb-2"><a
                        href="{{ route('checkout.index') }}">{{ __('frontend.check_out') }}</a></li>
                @endif
            </ul>
            <div class="dropdown py-2 px-2 mb-2">
                @if (!blank($language))
                <button
                    class="dropdownbtn w-full flex items-center gap-2 rounded-3xl capitalize text-sm font-medium text-heading transition-all duration-300 ease-in-out">
                    @foreach ($language as $lang)
                    @if (Session()->has('applocale') and Session()->get('applocale') and setting('locale'))
                    @if (Session()->get('applocale') == $lang->code)
                    <span id="current-lang"
                        class="flex-auto text-left whitespace-nowrap font-semibold text-lg">
                        {{ $lang->flag_icon == null ? '🇬🇧' : $lang->flag_icon }}
                        {{ $lang->name }}</span>
                    @endif
                    @else
                    @if (setting('locale') == $lang->code)
                    <span id="current-lang" class="whitespace-nowrap font-semibold text-lg">
                        {{ $lang->flag_icon == null ? '🇬🇧' : $lang->flag_icon }}
                        {{ $lang->name }} </span>
                    @endif
                    @endif
                    @endforeach
                    <i class="ms-1 fa-solid fa-chevron-down dropdown-icon"></i>
                </button>
                @endif
                @if (!blank($language))
                <ul
                    class="dropdown-content min-w-[180px] rounded-lg lg:shadow-xl z-10 border-3 hidden pt-4 px-4">
                    @foreach ($language as $lang)
                    <li class="py-1.5 rounded-md cursor-pointer hover:bg-gray-100 list-none">
                        <a href="{{ route('admin.lang.index', $lang->code) }}"
                            class="flex items-center gap-2 font-semibold text-lg">
                            <span> {{ $lang->flag_icon == null ? '🇬🇧' : $lang->flag_icon }}
                                {{ $lang->name }}</span></a>
                        </a>
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>
            @if (auth()->user())
            <a href="{{ route('admin.dashboard.index') }}"><button
                    class="bg-primary text-white rounded-md px-6 py-3 leading-tight w-full">{{ __('frontend.go_to_dashboard') }}</button></a>
            @else
            <a href="{{ route('login') }}"><button
                    class="bg-primary text-white rounded-md px-6 py-3 leading-tight w-full">{{ __('frontend.login') }}</button></a>
            @endif
        </div>
    </div>
</aside>


<!-- Main Content -->
<div class="main" data-mobile-height="">
    @yield('content')
</div>
<!-- Main Content -->

@yield('extras')

@stack('modals')

@include('frontend.layouts.partials.script._scripts')

@stack('js')

</body>

</html>