@extends('frontend.layouts.frontend')

@section('content')
<section class="h-screen">
    <form action="{{ route('check-in.step-two.next') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="container lg:px-24 mx-auto pb-8">
            <div class="row pt-8">
                <div class="lg:col-6 md:col-6 col-12">
                    <div class="p-6 flex w-full max-w-[472px] mx-auto flex-col items-center bg-userBg rounded-2xl backdrop-blur-lg shadow-card">
                        <h2 class="font-extrabold text-2xl md:text-[32px] text-primary leading-none">{{ __('frontend.take_visitor_photo') }}</h2>
                        <div class="w-full mt-6 mb-3 relative video-options">
                            <select class="appearance-none block w-full text-primary border !border-[#97A3C0] rounded-[12px] py-3 px-4 text-lg font-normal leading-tight focus:outline-none focus:bg-white active:border-gray-400" id="grid-state">
                                <option value="">Select camera</option>
                            </select>
                            <div class="pointer-events-none absolute top-1/3 top right-3 flex items-center px-2 text-primary">
                                <i class="fa-solid fa-chevron-down"></i>
                            </div>
                        </div>
                        <div class="mt-1 rounded-xl max-w-[424px] max-h-[282px] mb-8">
                            <video class="rounded-xl h-[300px]" id="videos" autoplay></video>
                            <canvas class="d-none"
                                style="border:5px solid #d3d3d3; display: none"></canvas>
                            <input type="hidden" id="image" name="photo" value="">
                        </div>
                        <button type="button" id="screenshot" title="ScreenShot" class="h-12 flex items-center gap-3 bg-primary text-white mt-6 ps-6 leading-snug pr-[6px] py-[6px] rounded-3xl font-bold text-lg retakephoto">{{ __('frontend.capture_photo') }}
                            <div class="p-[6px] rounded-full bg-[rgba(255,255,255,0.3)] w-10 h-10"><img src="{{ asset('frontend/images/photo_details/Camera.png') }}" alt="camera" class="w-full"></div>
                        </button>
                    </div>
                    <span class="text-center">{!! $errors->first('photo', '<p class="text-danger">:message</p>') !!}</span>
                </div>

                <div class="lg:col-6 md:col-6 col-12 ">
                    <div class="flex justify-center  md:justify-end h-full">
                        <div class="p-6 h-full flex w-full md:max-w-[372px] max-w-[472px] flex-col  bg-userBg rounded-2xl backdrop-blur-[15px] shadow-card -z-10">
                            <div>
                                <h3 class="text-2xl font-extrabold text-primary block text-left">{{ __('frontend.your_information') }}</h3>
                            </div>
                            <div class="text-base md:text-[20px] flex flex-col gap-3 md:gap-4 mt-6">
                                <div class="id-card-photo flex justify-center">
                                    <img id="card-img" style="width: 120px;height: 120px;"
                                        class="screenshot-image" alt="">
                                </div>
                                <div class="flex gap-2">
                                    <p class="font-semibold">{{ __('frontend.name') }}:</p>
                                    <p class="font-normal">{{ $visitingDetails['first_name'] }} {{ $visitingDetails['last_name'] }}</p>
                                </div>
                                @isset($visitingDetails['phone'])
                                <div class="flex gap-2">
                                    <p class="font-semibold">{{ __('frontend.phone') }}:</p>
                                    <p class="font-normal">{{ $visitingDetails['phone'] }}</p>
                                </div>
                                @endisset
                                @isset($visitingDetails['email'])
                                <div class="flex gap-2">
                                    <p class="font-semibold">{{ __('frontend.email') }}:</p>
                                    <p class="font-normal">{{ $visitingDetails['email'] }}</p>
                                </div>
                                @endisset
                                @isset($visitingDetails['national_identification_no'])
                                <div class="flex gap-2">
                                    <p class="font-semibold">{{ __('frontend.nid_no') }}:</p>
                                    <p class="font-normal">{{ $visitingDetails['national_identification_no'] }}</p>
                                </div>
                                @endisset
                                @if ($employee)
                                <div class="flex gap-2">
                                    <p class="font-semibold">{{ __('frontend.host') }}:</p>
                                    <p class="font-normal">{{ $employee->name }}</p>
                                </div>
                                @endif
                                @isset($visitingDetails['purpose'])
                                <div class="flex gap-2">
                                    <p class="font-semibold">{{ __('frontend.purpose') }}:</p>
                                    <p class="font-normal">{{ $visitingDetails['purpose'] }}</p>
                                </div>
                                @endisset
                                @isset($visitingDetails['address'])
                                <div class="flex gap-2">
                                    <p class="font-semibold">{{ __('frontend.address') }}:</p>
                                    <p class="font-normal">{{ $visitingDetails['address'] }}</p>
                                </div>
                                @endisset
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-between mt-6 sm:mt-8">
                <a href="{{ route('check-in.step-one') }}"><button type="reset" class="bg-danger text-white px-6 py-3 rounded-3xl shadow-btnDanger text-lg font-bold leading-snug">{{ __('frontend.cancel') }}</button></a>
                <button id="hide" type="submit" class="bg-primary text-lg font-bold text-white px-6 py-3 rounded-3xl shadow-btnNext leading-snug">{{ __('frontend.continue') }}</button>
            </div>
        </div>
    </form>
</section>

@endsection
@section('scripts')
<script src="{{ asset('js/photo.js') }}"></script>
<script>
    $(document).ready(function() {
        $(".btn-submit-two").attr("disabled", false);
        $(".btn-submit-two").click(function() {
            $(".btn-submit-two").attr("disabled", true);
            $('form').submit();
        });

    });
</script>
@endsection