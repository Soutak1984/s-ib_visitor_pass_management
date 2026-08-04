@extends('frontend.layouts.frontend')

@section('css')
<link rel="stylesheet" href="{{ asset('css/id-card-print-frontend.css') }}?v=3">
@endsection

@section('content')
<section class="h-screen">
    <div class="container pb-8">
        @if(isset($visitingDetails))

        @php
            $visitingDetails = \App\Models\VisitingDetails::with('visitor', 'employee')
                                ->find($visitingDetails->id);
        @endphp

        <div class="mt-8 max-w-[571px] w-full mx-auto p-6 sm:px-16 sm:pb-16 pt-6 sm:pt-11 rounded-2xl backdrop-blur-lg bg-cardBg shadow-card flex flex-col items-center">
            <h1 class="text-2xl sm:text-[32px] font-extrabold text-primary leading-snug">
                {{ __('frontend.visitor_id_card') }}
            </h1>

            <div class="mt-6 sm:mt-11 w-full flex justify-center" id="printidcard">
                @include('partials.visitor-id-card', ['visitingDetails' => $visitingDetails])
            </div>
        </div>
        @endif

        <!-- Buttons -->
        <div class="flex flex-wrap gap-y-2 sm:gap-y-0 justify-between items-center max-w-[571px] w-full mx-auto mt-7">
            <a href="{{ route('check-in') }}">
                <button type="reset" class="flex justify-start bg-danger text-white px-6 py-3 rounded-3xl shadow-btnDanger text-lg font-bold leading-none">
                    {{ __('frontend.back') }}
                </button>
            </a>
            <div class="flex flex-wrap justify-end gap-4">
                @if($visitingDetails->visitor ?? false)
                <a href="#" id="print">
                    <button class="bg-success text-white px-6 py-3 rounded-3xl shadow-btnSuccess text-lg font-bold leading-none">
                        {{ __('frontend.print_id') }}
                    </button>
                </a>
                @endif
                <a href="{{ route('check-in') }}">
                    <button type="submit" class="bg-primary text-lg font-bold text-white px-6 py-3 rounded-3xl shadow-btnNext leading-none">
                        {{ __('frontend.home') }}
                    </button>
                </a>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    function printData(data) {
        var frame1 = $('<iframe />');
        frame1[0].name = "frame1";
        frame1.css({ "position": "absolute", "top": "-1000000px" });
        $("body").append(frame1);
        var frameDoc = frame1[0].contentWindow ? frame1[0].contentWindow : frame1[0].contentDocument.document ? frame1[0].contentDocument.document : frame1[0].contentDocument;
        frameDoc.document.open();
        // Styles are embedded inside the card HTML (partial), so print looks exact
        frameDoc.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Visitor ID Card</title>');
        frameDoc.document.write('<style>@page{margin:10mm}html,body{margin:0;padding:0;background:#fff}</style>');
        frameDoc.document.write('</head><body style="margin:0;padding:16px;background:#fff;display:flex;justify-content:center;">');
        frameDoc.document.write(data);
        frameDoc.document.write('</body></html>');
        frameDoc.document.close();
        setTimeout(function() {
            window.frames["frame1"].focus();
            window.frames["frame1"].print();
            frame1.remove();
        }, 600);
    }

    $('#print').on('click', function(e) {
        e.preventDefault();
        var data = $("#printidcard").html();
        printData(data);
    });
</script>
@endsection
