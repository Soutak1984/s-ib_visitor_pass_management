@extends('admin.layouts.master')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/id-card-print-frontend.css') }}?v=3">
    <link rel="stylesheet" href="{{ asset('assets/modules/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/modules/datatables.net-select-bs4/css/select.bootstrap4.min.css') }}">
@endsection

@section('main-content')
	<section class="section">
        <div class="section-header">
            <h1>{{ __('visitor.visitors') }}</h1>
            {{ Breadcrumbs::render('visitors/show') }}
        </div>
        <div class="section-body">
        	<div class="row">
	   			<div class="col-12 col-md-5 col-lg-5">
			    	<div class="card">
                        <div class="card-header">
                            <a href="#" id="print" class="btn btn-icon icon-left btn-primary">
                                <i class="fas fa-print"></i> {{ __('visitor.print_id_card') }}
                            </a>
                        </div>
					    <div class="card-body">
                            <div class="admin-idcard-wrap" id="printidcard">
                                @include('partials.visitor-id-card', ['visitingDetails' => $visitingDetails])
                            </div>
                        </div>
					</div>
				</div>

	   			<div class="col-12 col-md-7 col-lg-7">
			    	<div class="card">
			    		<div class="card-body">
			    			<div class="profile-desc">
			    				<div class="single-profile">
			    					<p><b>{{ __('visitor.first_name') }}: </b> {{ $visitingDetails->visitor->first_name}}</p>
			    				</div>
			    				<div class="single-profile">
			    					<p><b>{{ __('visitor.last_name') }}: </b> {{ $visitingDetails->visitor->last_name}}</p>
			    				</div>
			    				<div class="single-profile">
			    					<p><b>{{ __('visitor.email') }}: </b> {{ $visitingDetails->visitor->email}}</p>
			    				</div>
			    				<div class="single-profile">
			    					<p><b>{{ __('visitor.phone') }}: </b> {{ $visitingDetails->visitor->phone}}</p>
			    				</div>
                                <div class="single-profile">
			    					<p><b>{{ __('visitor.employee') }}: </b> {{ optional(optional($visitingDetails->employee)->user)->name }}</p>
			    				</div>
                                <div class="single-profile">
                                    <p><b>{{ __('visitor.purpose') }}: </b> {{ $visitingDetails->purpose}}</p>
                                </div>
                                <div class="single-profile">
                                    <p><b>{{ __('visitor.entry_gate_number') }}: </b> {{ $visitingDetails->entry_gate_number ?? '—'}}</p>
                                </div>
                                <div class="single-profile">
                                    <p><b>{{ __('visitor.company_name') }}: </b> {{ $visitingDetails->company_name}}</p>
                                </div>
                                <div class="single-profile">
                                    <p><b>{{ __('visitor.national_identification_no') }}: </b> {{ $visitingDetails->visitor->national_identification_no}}</p>
                                </div>
			    				<div class="single-profile">
			    					<p><b>{{ __('visitor.date') }}: </b> {{date('d-m-Y', strtotime($visitingDetails->created_at))}}</p>
			    				</div>
                                <div class="single-profile">
			    					<p><b>{{ __('visitor.checkin') }}: </b> {{ $visitingDetails->checkin_at ? date('d-m-Y h:i A', strtotime($visitingDetails->checkin_at)) : 'N/A' }}</p>
			    				</div>
                                @if($visitingDetails->checkout_at)
                                <div class="single-profile">
			    					<p><b>{{ __('visitor.checkout') }}: </b> {{date('d-m-Y h:i A', strtotime($visitingDetails->checkout_at))}}</p>
			    				</div>
                                @endif
                                <div class="single-profile">
                                    <p><b>{{ __('visitor.address') }}: </b> {{ $visitingDetails->visitor->address}}</p>
                                </div>
                                @if($visitingDetails->vehicle_number)
                                <div class="single-profile">
                                    <p><b>Vehicle Number: </b> {{ $visitingDetails->vehicle_number }}</p>
                                </div>
                                @endif
                                @if($visitingDetails->vehicle_compliance)
                                <div class="single-profile">
                                    <p><b>Vehicle Compliance: </b> {{ $visitingDetails->vehicle_compliance }}</p>
                                </div>
                                @endif
                                @if($visitingDetails->vehicle_remarks)
                                <div class="single-profile">
                                    <p><b>Vehicle Remarks: </b> {{ $visitingDetails->vehicle_remarks }}</p>
                                </div>
                                @endif
                                <div class="single-profile">
                                    <p><b>{{ __('levels.status') }}: </b> {{ $visitingDetails->status== App\Enums\VisitorStatus::PENDDING ||  $visitingDetails->status== App\Enums\VisitorStatus::ACCEPT || $visitingDetails->status== App\Enums\VisitorStatus::REJECT ? trans('visitor_statuses.' . $visitingDetails->status) : ''}}</p>
                                </div>
                                @if($visitingDetails->disable)
                                <div class="single-profile">
			    					<p><b>{{ __('levels.disabled') }}: </b><span class="badge badge-danger">Visitor Blocked</span></p>
			    				</div>
                                @endif
			    			</div>

                            @if (setting('whatsapp_message'))
                                <div class="float-right">
                                    <a id=waButton href="https://wa.me/{{$visitingDetails->visitor->phone}}?text={!! strip_tags(setting('whatsapp_decline_message')) !!}" target="_blank" class="btn btn-danger">{{ __('levels.reject_whatsApp') }}</a>
                                </div>
								<div class="float-right">
									<a id=waButton href="https://wa.me/{{$visitingDetails->visitor->phone}}?text={!! strip_tags(setting('whatsapp_accept_message')) !!} {{ route('qrcode',$visitingDetails->visitor->phone) }}" target="_blank" class="btn btn-success mr-1">{{ __('levels.send_whatsApp') }}</a>
								</div>
							@endif
			    		</div>
			    	</div>
				</div>
        	</div>
        </div>
    </section>
@endsection

@section('scripts')
    <script src="{{ asset('assets/modules/datatables/media/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/modules/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/modules/datatables.net-select-bs4/js/select.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('js/visitor/view.js') }}?v=2"></script>
@endsection
