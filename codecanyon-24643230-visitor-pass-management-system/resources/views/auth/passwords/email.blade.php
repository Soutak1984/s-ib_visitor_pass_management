@extends('frontend.layouts.frontend')

@section('content')
<section class="h-screen ">
    <div class="container py-16">
        <div class="max-w-[770px] w-full mx-auto rounded-2xl  bg-cardBg shadow-card -z-50">
            <div class="row">
                <div class="col-12 md:col-6 !py-0">
                    <div class="py-6 px-4 md:px-6 md:pr-0">
                        <form method="POST" action="{{ route('password.email') }}">
                            @csrf
                            <h1 class="text-2xl sm:text-[32px] font-extrabold text-primary mb-8 md:mb-12 leading-none">{{ __('login.reset_password') }}</h1>
                            <div class="mb-6">
                                <label class="block tracking-wide text-black text-sm font-medium mb-2 required" for="email">{{ __('login.email') }}</label>
                                <input class=" appearance-none  w-full text-primary border border-[#97A3C0] rounded-[12px] py-3 px-4 mb-1 leading-tight @error('email') is-invalid @enderror" id="email" type="text" name="email" value="{{ old('email') }}">
                                @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                            <button type="submit" class="text-lg font-bold leading-snug text-white bg-primary rounded-[23.5px] px-6 py-3 w-full shadow-btnNext">{{ __('login.send_password_reset_link') }}</button>
                        </form>
                    </div>
                </div>
                <div class="hidden md:block md:col-6 !py-0 ">
                    <div class="max-w-[373px] max-h-[300px] w-full h-full">
                        <img src="{{ asset('frontend/images/login/login.png') }}" alt="" class="w-full h-full object-cover rounded-r-2xl">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
