@extends('frontend.layouts.frontend')

@section('content')
<section class="h-screen">
    <div class="container pb-8">
        @if(isset($visitingDetails))
        <div class="mt-8 max-w-[571px] w-full mx-auto p-6 sm:px-16 sm:pb-16 pt-6 sm:pt-11 rounded-2xl backdrop-blur-lg bg-cardBg shadow-card flex flex-col items-center">
            <h1 class="text-2xl sm:text-[32px] font-extrabold text-primary leading-snug">{{ __('frontend.visitor_id_card') }}</h1>
            <div class="bg-white rounded-lg mt-6 sm:mt-11 w-full shadow-idcard" id="printidcard">
                <div class="idcard">
                    <div class="bg-gradient-to-r from-[#496FD7] to-[#46A5ED] rounded-t-lg lg p-4">
                        <div class="flex justify-between items-center card-header">
                            <div class="flex items-center justify-center gap-[6px] company-details">
                                <div class="w-8 h-8 bg-white flex items-center logo-img rounded-lg">
                                    <img class="w-full logo" src="{{ asset('images/'.setting('site_logo')) }}" alt="logo">
                                </div>
                                <div class="text-[10px] font-bold text-white company-name">
                                    <p>{{ setting('site_name') }} </p>
                                </div>
                            </div>

                            <div class="flex flex-col text-[10px] font-base text-white leading-none company-address">
                                <p>{{ setting('site_address') }}</p>
                                <p class="my-2">{{__('E-mail:')}} {{ setting('site_email') }}</p>
                                @if(setting('site_phone'))
                                <p>{{__('Phone:')}} {{ setting('site_phone') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="p-6 flex justify-start gap-x-[18px] card-body">
                        <div class="w-36 h-36 user-image">
                            @if($visitingDetails->getFirstMediaUrl('visitor'))
                            <img src="{{ $visitingDetails->getFirstMediaUrl('visitor') }}" alt="user" class="w-full h-full rounded-xl">
                            @elseif ($visitingDetails['photo'])
                            <img src="{{ $visitingDetails['photo'] }}" alt="user" class="w-full h-full rounded-xl">
                            @elseif (\App\Enums\Status::MALE== $visitingDetails->visitor->gender)
                            <img src="{{ asset('/frontend/images/avatars/avatar5.png)}}" alt="user" class="w-full rounded-xl">
                            @else
                            <img src="{{ asset('/frontend/images/avatars/avatar4.png)}}" alt="user" class="w-full rounded-xl">
                            @endif
                        </div>
                        <div class="user-details">
                            <div>
                                <p class="text-base md:text-[20px] font-semibold text-primary leading-none user-name">{{$visitingDetails->visitor->name}}</p>
                                <p class="text-[14px] font-normal mt-1">ID#{{$visitingDetails->reg_no}}</p>
                            </div>
                            <div class="mt-3 mb-3 phone">
                                <h2 class="text-[14px] font-semibold text-primary">{{ __('frontend.phone') }}</h2>
                                <p class="text-[14px] font-normal">+{{$visitingDetails->visitor->country_code}}{{$visitingDetails->visitor->phone}}</p>
                            </div>
                            <div>
                                <h2 class="text-[14px] font-semibold text-primary">{{ __('frontend.host') }}</h2>
                                <p class="text-[14px] font-normal">{{$visitingDetails->employee->name}}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
        <div class="flex flex-wrap gap-y-2 sm:gap-y-0 justify-between items-center max-w-[571px] w-full mx-auto mt-7">
            <a href="{{ route('check-in') }}"><button type="reset" class="flex justify-start  bg-danger text-white px-6 py-3 rounded-3xl shadow-btnDanger text-lg font-bold leading-none">{{ __('frontend.back') }}</button></a>
            <div class="flex flex-wrap justify-end gap-4">
                @if($visitingDetails->visitor)
                <a href="#" id="print"><button class="bg-success text-white px-6 py-3 rounded-3xl shadow-btnSuccess text-lg font-bold leading-none">{{ __('frontend.print_id') }}</button></a>
                @endif
                <a href="{{ route('check-in') }}"><button type="submit" class="bg-primary text-lg font-bold text-white px-6 py-3 rounded-3xl shadow-btnNext leading-none">{{ __('frontend.home') }}</button></a>
            </div>
        </div>
    </div>
</section>
@endsection
@section('scripts')
<script>
    function printData(data) {
        var frame1 = $('<iframe />');
        var css = "{{ asset('css/id-card-print-frontend.css') }}";
        frame1[0].name = "frame1";
        frame1.css({
            "position": "absolute",
            "top": "-1000000px"
        });
        $("body").append(frame1);
        var frameDoc = frame1[0].contentWindow ? frame1[0].contentWindow : frame1[0].contentDocument.document ? frame1[0].contentDocument.document : frame1[0].contentDocument;
        frameDoc.document.open();
        //Create a new HTML document.
        frameDoc.document.write('<html><head><title>visitor ID Card</title>');
        frameDoc.document.write('<link href="' + css + '" rel="stylesheet" type="text/css" />');
        frameDoc.document.write('</head><body>');
        //Append the external CSS file.
        //Append the DIV contents.
        frameDoc.document.write(data);
        frameDoc.document.write('</body></html>');
        frameDoc.document.close();
        setTimeout(function() {
            window.frames["frame1"].focus();
            window.frames["frame1"].print();
            frame1.remove();
        }, 500);
    }

    $('#print').on('click', function(e) {
        e.preventDefault();
        var data = $("#printidcard").html();
        printData(data);
    });
</script>

@endsection
