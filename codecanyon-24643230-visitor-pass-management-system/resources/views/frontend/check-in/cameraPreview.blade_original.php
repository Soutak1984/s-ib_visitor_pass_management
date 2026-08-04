@extends('frontend.layouts.frontend')
@section('content')
<section class="h-screen">
    <div class="container">
        <div class="pb-12 pt-12 md:pt-14 lg:pt-24">
            <div class="row">
                <div class="lg:col-6 md:col-6 col-12 md:pr-0">
                    <div class="flex items-center font-medium text-xs md:text-sm gap-3 mb-4 md:mb-8">
                        <hr class="w-8 md:w-10 h-[1px] bg-[#0A183F] border-none">
                        <p class="uppercase">{{setting('site_name')}}</p>
                    </div>
                    <h1 class="text-2xl sm:text-[32px] md:text-[42px] xl:text-[52px] font-extrabold mb-6 md:mb-8 leading-tight">{{setting('site_description')}}</h1>
                    <p class="text-sm md:text-lg xl:text-xl font-normal max-w-[550px] w-full pr-7">{{strip_tags(setting('welcome_screen'))}}</p>
                    <div class="flex flex-shrink gap-4 md:gap-6 mb-8 mt-6 md:mt-12">
                        <a href="{{ route('check-in.step-one') }}"><button type="submit" class="group lg:py-3 py-2 lg:pl-8 pl-6 pr-2 whitespace-nowrap bg-primary text-white rounded-[30px] lg:text-[22px] text-base font-bold flex  items-center gap-4">{{__('frontend.check_in')}}<div class="w-9 h-9 rounded-full bg-[rgba(255,255,255,0.3)] flex justify-center items-center -rotate-45"><i class="fa-solid fa-arrow-right"></i></div> </button></a>
                        <a href="{{ route('check-in.scan-qr') }}"><button type="submit" class="lg:py-3 py-2 lg:pl-8 pl-6 pr-2 whitespace-nowrap text-primary border-primary border rounded-[30px] lg:text-[22px] text-base font-bold flex justify-end items-center gap-4 leading-tight">{{__('frontend.scan_qr')}}<div class="w-9 h-9 rounded-full bg-[#DEDFFF] flex justify-center items-center"><img src="{{ asset('frontend/images/hero/Qr Code.png') }}" alt="qr"></div></button></a>
                    </div>
                </div>
                <div class="lg:col-6 md:col-6 col-12 w-full h-fit flex justify-center lg:justify-end md:mt-6">
                    <div class="scan max-w-[435px] w-full aspect-square p-1">
                        <div class="my_qr_scanner w-full h-full">
                            <video autoplay id="preview" class="min-h-[427px] object-cover"></video>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('js')

{{-- For Qrcode Scan --}}
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/webrtc-adapter/3.3.3/adapter.min.js"></script>
<script type="text/javascript" src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>
<script>
    $(document).ready(function() {
        let scanner = new Instascan.Scanner({
            video: document.getElementById('preview')
        });
        Instascan.Camera.getCameras().then(function(cameras) {
            if (cameras.length > 0) {
                scanner.start(cameras[0]);
            } else {
                alert('No cameras found');
            }

        }).catch(function(e) {

            console.error(e);
        });

        scanner.addListener('scan', function(c) {
            let appurl = "{{env('APP_URL')}}";
            appUrl = (new URL(appurl));
            appUrl = appUrl.hostname;
            let domain = (new URL(c));
            domain = domain.hostname;

            if (appUrl == domain) {
                window.open(c, "_self");
            } else {
                alert('Please Enter Valid Qrcode');
            }

        });
    });
</script>

@endpush