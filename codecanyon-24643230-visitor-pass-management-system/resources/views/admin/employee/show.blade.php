@extends('admin.layouts.master')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/id-card-print.css') }}">
@endsection
@section('main-content')

    <section class="section">
        <div class="section-header">
            <h1>{{ __('employee.employees') }}</h1>
            {{ Breadcrumbs::render('employees/show') }}
        </div>

        <div class="section-body">
            <div class="row mt-sm-4">
                <div class="col-12 col-md-12 col-lg-4">
                    <div class="card">
                        <div class="profile-dashboard bg-maroon-light">
                            <img src="{{ $employee->user->images }}" alt="">
                            <h1>{{ $employee->user->name }}</h1>
                            <p>
                                {{ $employee->user->getrole->name ?? '' }}
                            </p>
                        </div>
                        <div class="profile-widget-description profile-widget-employee">
                            <dl class="row">
                                <dt class="col-sm-4">{{ __('employee.name') }}</dt>
                                <dd class="col-sm-8">{{ $employee->user->name }}</dd>
                                <dt class="col-sm-4">{{ __('employee.phone') }}</dt>
                                <dd class="col-sm-8">{{ $employee->user->country_code }}{{ $employee->user->phone }}</dd>
                                <dt class="col-sm-4">{{ __('employee.email') }}</dt>
                                <dd class="col-sm-8">{{ $employee->user->email }}</dd>
                                <dt class="col-sm-4">{{ __('employee.joining_date') }}</dt>
                                <dd class="col-sm-8">{{ $employee->date_of_joining }}</dd>
                                <dt class="col-sm-4">{{ __('employee.gender') }}</dt>
                                <dd class="col-sm-8">{{ $employee->mygender }}</dd>
                                <dt class="col-sm-4">{{ __('employee.department') }}</dt>
                                <dd class="col-sm-8">{{ isset($employee->department) ? $employee->department->name : '' }}</dd>
                                <dt class="col-sm-4">{{ __('employee.designation') }}</dt>
                                <dd class="col-sm-8">{{ isset($employee->designation) ? $employee->designation->name : ''}}</dd>
                                <dt class="col-sm-4">{{ __('employee.status') }}</dt>
                                <dd class="col-sm-8">{{ $employee->mystatus }}</dd>
                            </dl>

                            <img src="{{ asset('qrcode/'.$employee->barcode) }}" alt="" width="150">
                            <hr>
                            <div class="btn-group my-2">
                                <a href="{{ asset('qrcode/'.$employee->barcode) }}" class="btn btn-sm btn-outline-secondary" id="download" download="">
                                    {{ __("employee.download") }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-12 col-lg-8">
                    <nav>
                        <div class="nav nav-tabs" id="nav-tab" role="tablist">
                            <a class="nav-item nav-link active" id="nav-visitor-tab" data-toggle="tab"
                               href="#nav-visitor" role="tab" aria-controls="nav-visitor"
                               aria-selected="true">{{ __('employee.visitors') }}</a>
                            <a class="nav-item nav-link" id="nav-register-tab" data-toggle="tab" href="#nav-register"
                               role="tab" aria-controls="nav-register" aria-selected="false">{{ __('employee.pre_registers') }}</a>
                        </div>
                    </nav>
                    <div class="tab-content" id="nav-tabContent">
                        <div class="tab-pane fade show active" id="nav-visitor" role="tabpanel"
                             aria-labelledby="nav-visitor-tab">
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="visitortable"
                                               data-url="{{ route('admin.employees.get-visitors',$employee) }}"
                                               data-status="{{ \App\Enums\Status::ACTIVE }}"
                                               data-hidecolumn="{{ auth()->user()->can('visitors_show') || auth()->user()->can('visitors_edit') || auth()->user()->can('visitors_delete') }}">
                                            <thead>
                                            <tr>
                                                <th>{{ __('levels.id') }}</th>
                                                <th>{{ __('levels.image') }}</th>
                                                <th>{{ __('levels.name') }}</th>
                                                <th>{{ __('levels.email') }}</th>
                                                <th>{{ __('employee.checkin') }}</th>
                                                <th>{{ __('levels.actions') }}</th>
                                            </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="nav-register" role="tabpanel" aria-labelledby="nav-register-tab">
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="preregistertable"
                                               data-url="{{ route('admin.employees.get-pre-registers',$employee) }}"
                                               data-status="{{ \App\Enums\Status::ACTIVE }}"
                                               data-hidecolumn="{{ auth()->user()->can('pre-registers_show') || auth()->user()->can('pre-registers_edit') || auth()->user()->can('pre-registers_delete') }}">
                                            <thead>
                                            <tr>
                                                <th scope="col">{{ __('levels.id') }}</th>
                                                <th scope="col">{{ __('levels.name') }}</th>
                                                <th scope="col">{{ __('levels.email') }}</th>
                                                <th scope="col">{{ __('levels.phone') }}</th>
                                                <th scope="col">{{ __('employee.expected_date') }}</th>
                                                <th scope="col">{{ __('employee.expected_time') }}</th>
                                                <th scope="col">{{ __('levels.actions') }}</th>
                                            </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-4 col-md-4 col-lg-4">
			    	<div class="card">
                        <div class="card-header">
                            <a href="#" id="print" class="btn btn-icon icon-left btn-primary"><i class="fas fa-print"></i> {{ __('visitor.print_id_card') }}</a>
                        </div>
					    <div class="card-body ">
                            <div class="img-cards" id="printidcard">
                                <div class="id-card-holder">
                                    <div class="id-card">
                                        <div class="id-card-photo">
                                            @if($employee->getFirstMediaUrl('employee'))
                                                <img src="{{ asset($employee->getFirstMediaUrl('employee')) }}" alt="">
                                            @else
                                                <img src="{{ asset('images/'.setting('site_logo')) }}" alt="">
                                            @endif
                                        </div>

                                        <h3>{{$employee->user->name}}</h3>
                                        <h3>{{$employee->user->phone}}</h3>
                                        <h3>{{$employee->user->email}}</h3>
                                        <h3>{{$employee->date_of_joining}}</h3>
                                        <h3>{{$employee->mygender}}</h3>

                                        <img src="{{ asset('qrcode/'.$employee->barcode) }}" alt="" width="100">
                                        <hr>
                                        <span><strong>{{ setting('site_name') }} </strong></span><br>
                                        <span class="text-small"><strong>{{ setting('site_address') }} </strong></span><br>
                                        <span class="text-small">{{__('visitor.ph')}}: {{ setting('site_phone') }} | {{__('visitor.email')}}: {{ setting('site_email') }} </span><br>
                                    </div>
                                </div>
                            </div>
                        </div>
					    <!-- /.box-body -->
					</div>
				</div>
            </div>
        </div>
    </section>

@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/modules/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/modules/datatables.net-select-bs4/css/select.bootstrap4.min.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('assets/modules/datatables/media/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/modules/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/modules/datatables.net-select-bs4/js/select.bootstrap4.min.js') }}"></script>

    <script>
        var idCardCss = "{{ asset('css/id-card-print.css') }}";
    </script>

    <script src="{{ asset('js/employee/view.js') }}"></script>
@endsection
