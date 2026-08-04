@extends('frontend.layouts.frontend')

@section('content')

    <section id="pm-banner-1" class="pm-banner-section-1 position-relative custom-css">
        <div class="container text-center">
            <img src="{{ asset('qrcode/'.$visitor->barcode) }}" alt="">
            <p class="mt-3">Scan This QR Code For Getting Access To Visit The Employee.</p>
        </div>
    </section>
@endsection

