@extends('frontend.layouts.frontend')

@section('content')
<!-- Default Page -->

<section class="h-screen">
    <div class="container">
        <div class="pb-12 pt-12 md:pt-14 lg:pt-20">
            <div class="row">
                <div class="lg:col-6 md:col-6 col-12 md:pr-0 md:mt-16 mb-20">
                    <h1 class="text-2xl md:text-[32px] font-extrabold text-primary mb-6 leading-none">{{__('frontend.checkout-visitor')}}</h1>
                    <form action="{{ route('checkout.index') }}" method="POST">
                        @csrf
                        <label class="block text-black text-sm font-medium mb-2 required" for="email">{{__('frontend.visitor_id')}}</label>
                        <input class="appearance-none block w-full text-primary border border-[#97A3C0] rounded-[12px] py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white @error('visitorID')is-invalid @enderror" id="email" type="text" name="visitorID">
                        @error('visitorID')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                        <div class="flex justify-between mt-6 sm:mt-8">
                            <a href="{{ route('/') }}"><button type="reset" class="bg-danger text-white px-6 py-3 rounded-3xl shadow-btnDanger text-lg font-bold leading-snug">{{__('frontend.cancel')}}</button></a>
                            <button type="submit" class="bg-primary text-lg font-bold text-white px-6 py-3 rounded-3xl shadow-btnNext leading-snug"> {{__('frontend.continue')}}</button>
                        </div>
                    </form>
                </div>
                <div class="lg:col-6 md:col-6 col-12  flex justify-center md:justify-end lg:justify-end ">
                    <div class="imgGroup xl:max-w-[497px] lg:max-w-[409px] md:max-w-[350px] w-full">
                    <img src="{{ asset('frontend/images/visitor_details/image1.png') }}" alt="image1" class="img1">
                    <img src="{{ asset('frontend/images/visitor_details/image2.png') }}" alt="image2" class="img2">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
