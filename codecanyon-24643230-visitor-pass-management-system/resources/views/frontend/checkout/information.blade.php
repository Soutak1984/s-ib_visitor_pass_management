@extends('frontend.layouts.frontend')

@section('content')
<!-- Default Page -->
<section class="h-screen">
    <div class="container">
        <div class="pb-12 pt-12 lg:pt-14">
            <div class="row ">
                <div class="lg:col-6 md:col-6 col-12 md:pr-0 lg:mt-[90px] mb-20">
                    <h1 class="text-2xl sm:text-[32px] font-extrabold text-primary mb-6 leading-none">Check Out Visitor</h1>
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
                <div class="lg:col-6 md:col-6 col-12 w-full flex justify-center md:justify-end lg:justify-end">
                    <div class="lg:max-w-[490px] md:max-w-[400px] sm:max-w-350px max-w-[490px] w-full p-6 rounded-2xl backdrop-blur-md bg-cardBg flex flex-col items-center">
                        <h2 class="text-2xl font-extrabold text-primary">Visitor Information & ID</h2>
                        <div class="bg-white rounded-lg mt-6 w-full">
                            <div class="bg-gradient-to-r from-[#496FD7] to-[#46A5ED] rounded-t-lg  lg p-4">
                                <div class="flex justify-between items-center">
                                    <div class="flex justify-center gap-[6px]">
                                        <div class="w-8 h-8 bg-white flex items-center">
                                            <img class="w-full" src="{{ asset('images/'.setting('site_logo')) }}" alt="logo">
                                        </div>
                                        <div class="text-[10px] font-bold text-white ">
                                            <p>{{ setting('site_name') }} </p>
                                        </div>
                                    </div>
                                    <div class="flex flex-col text-[10px] font-base text-white leading-none">
                                        <p>{{ setting('site_address') }}</p>
                                        <p class="my-2">{{__('E-mail:')}} {{ setting('site_email') }}</p>
                                        @if(setting('site_phone'))
                                        <p>{{__('Phone:')}} {{ setting('site_phone') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="p-6 flex justify-start gap-x-[18px]">
                                <div class="w-36 h-36">
                                    @if($visitingDetails->getFirstMediaUrl('visitor'))
                                    <img src="{{ asset($visitingDetails->getFirstMediaUrl('visitor')) }}" alt="user" class="w-full">
                                    @elseif ($visitingDetails['photo'])
                                    <img src="{{ $visitingDetails['photo'] }}" alt="user" class="w-full">
                                    @elseif (\App\Enums\Status::MALE== $visitingDetails->visitor->gender)
                                    <img src="{{ asset('/frontend/images/avatars/avatar5.png)}}" alt="user" class="w-full">
                                    @else
                                    <img src="{{ asset('/frontend/images/avatars/avatar4.png)}}" alt="user" class="w-full">
                                    @endif
                                </div>
                                <div>
                                    <div>
                                        <p class="text-base md:text-[20px] font-semibold text-primary leading-none">{{$visitingDetails->visitor->name}}</p>
                                        <p class="text-[14px] font-normal mt-1">ID#{{$visitingDetails->reg_no}}</p>
                                    </div>
                                    <div class="mt-3 mb-3">
                                        <h2 class="text-[14px] font-semibold text-primary">Phone</h2>
                                        <p class="text-[14px] font-normal">+{{$visitingDetails->visitor->country_code}}{{$visitingDetails->visitor->phone}}</p>
                                    </div>
                                    <div>
                                        <h2 class="text-[14px] font-semibold text-primary">{{__('Host:')}}</h2>
                                        <p class="text-[14px] font-normal">{{$visitingDetails->employee->name}}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if(!$visitingDetails->checkout_at)
                        <a href="{{ route('checkout.update', $visitingDetails) }}" class="mt-[32px] bg-danger text-white px-6 py-3 rounded-3xl shadow-btnDanger text-lg font-bold leading-snug">
                            Check Out
                        </a>
                        @else
                        <p class="mt-2 text-danger text-sm">{{ __('frontend.you_have_already_checked_out_successfully') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection