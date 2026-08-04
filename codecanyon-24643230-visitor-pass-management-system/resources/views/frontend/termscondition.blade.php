@extends('frontend.layouts.frontend')

@section('content')
   <section class="h-screen">
        <div class="container">
            <div class="pb-12 mt-12 md:mt-14 lg:mt-20">
                <div class="row">
                    <h3 class="font-extrabold text-primary text-2xl block w-full text-center mb-6">{{ __('frontend.tearms_conditons') }}</h3>
                   <p>{!! setting('terms_condition') !!}</p>
                </div>
            </div>
        </div>
    </section>
@endsection
