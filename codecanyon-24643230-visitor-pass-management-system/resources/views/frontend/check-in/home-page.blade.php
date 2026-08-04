@extends('frontend.layouts.frontend')

@section('content')
<section class="h-screen">
    <div class="container">
        <div class="pb-12 mt-12 md:mt-14 lg:mt-20">
            <div class="row">
                <div class="lg:col-6 md:col-6 col-12 md:pr-0  md:mt-5 mb-12">
                    <div class="flex items-center font-medium text-xs md:text-sm gap-3 mb-4 md:mb-8">
                        <hr class="w-8 md:w-10 h-[1px] bg-[#0A183F] border-none">
                        <p class="uppercase">{{setting('site_name')}}</p>
                    </div>
                    <h1 class="text-2xl sm:text-[32px] md:text-[42px] xl:text-[52px] font-extrabold mb-6 md:mb-8 leading-tight">{{setting('site_description')}}</h1>
                    <p class="text-sm md:text-lg xl:text-xl font-normal max-w-[550px] w-full pr-7">{{strip_tags(setting('welcome_screen'))}}</p>
                    <div class="flex gap-4 md:gap-6 mt-6 md:mt-12">
                        <a href="{{ route('check-in.step-one') }}"><button type="submit" class="lg:py-3 py-2 lg:pl-8 pl-6 pr-2 whitespace-nowrap bg-primary text-white rounded-[30px] lg:text-[22px] text-base font-bold flex  items-center gap-4">{{__('frontend.check_in')}}
                                <div class="w-9 h-9 rounded-full bg-[rgba(255,255,255,0.3)] flex justify-center items-center -rotate-45 group-hover:rotate-0 transition-all duration-300"><i class="fa-solid fa-arrow-right"></i></div>
                            </button></a>
                        <a href="{{ route('check-in.scan-qr') }}"> <button type="submit" class="lg:py-3 py-2 lg:pl-8 pl-6 pr-2 whitespace-nowrap backdrop-blur text-primary border-primary border rounded-[30px] lg:text-[22px] text-base font-bold flex justify-end items-center gap-4 leading-tight">{{__('frontend.scan_qr')}}
                                <div class="w-9 h-9 rounded-full bg-[#DEDFFF] flex justify-center items-center"><img src="{{ asset('frontend/images/hero/Qr Code.png') }}" alt="qr"></div>
                            </button></a>
                    </div>
                </div>
                <div class="lg:col-6 md:col-6 col-12  flex justify-center md:justify-end lg:justify-end">
                    <div class="imgGroup xl:max-w-[571px] lg:max-w-[490px] md:max-w-[400px] max-w-[350px] w-full">
                        <img src="{{ asset('frontend/images/hero/img1.png') }}" alt="image1" class="img1">
                        <img src="{{ asset('frontend/images/hero/img2.png') }}" alt="image2" class="img2">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection