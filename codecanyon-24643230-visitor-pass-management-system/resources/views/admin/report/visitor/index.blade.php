@extends('admin.layouts.master')

@section('main-content')
    <section class="section">
        <div class="section-header">
            <h1>{{ __('visitor_report.visitor_report') }}</h1>
            {{ Breadcrumbs::render('visitors') }}
        </div>

        <div class="section-body">
            <!-- Date Filter -->
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.admin-visitor-report.post') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>{{ __('visitor_report.from_date') }}</label>
                                    <input type="text" name="from_date" class="form-control @error('from_date') is-invalid @enderror datepicker" value="{{ old('from_date', $set_from_date) }}">
                                    @error('from_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>{{ __('visitor_report.to_date') }}</label>
                                    <input type="text" name="to_date" class="form-control @error('to_date') is-invalid @enderror datepicker" value="{{ old('to_date', $set_to_date) }}">
                                    @error('to_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <label>&nbsp;</label>
                                <button class="btn btn-primary form-control" type="submit">{{ __('visitor_report.get_report') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            @if($showView)
                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-danger">
                                <i class="far fa-user"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>{{ __('visitor_report.total_visitor') }}</h4>
                                </div>
                                <div class="card-body">{{$totalVisitor}}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-primary">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>{{ __('visitor_report.checkin_visitor') }}</h4>
                                </div>
                                <div class="card-body">{{$checkinVisitor}}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-warning">
                                <i class="fas fa-user-secret"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>{{ __('visitor_report.checkout_visitor') }}</h4>
                                </div>
                                <div class="card-body">{{$checkoutVisitor}}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Table with Better Print Support -->
                <div class="card">
                    <div class="card-header">
                        <h5>{{ __('visitor_report.visitor_report') }}</h5>
                        <button class="btn btn-success btn-sm report-print-button" onclick="printDiv('printablediv')">
                            {{ __('visitor_report.print') }}
                        </button>
                    </div>

                    <div class="card-body" id="printablediv">
                        @if(!blank($visitors))
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" style="width:100%; font-size:13px;">
                                    <thead>
                                        <tr>
                                            <th style="width:4%">ID</th>
                                            <th style="width:8%">Image</th>
                                            <th style="width:10%">VisitorID</th>
                                            <th style="width:15%">Name</th>
                                            <th style="width:15%">Email</th>
                                            <th style="width:12%">Phone</th>
                                            <th style="width:12%">Employee</th>
                                            <th style="width:12%">Purpose</th>
                                            <th style="width:12%">Checkin</th>
                                            <th style="width:12%">Check Out</th>
                                            <th style="width:10%">Entry Gate</th>
                                            <th style="width:12%">Vehicle No</th>
                                            <th style="width:8%">Compliance</th>
                                            <th style="width:15%">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $i = 0; @endphp
                                        @foreach($visitors as $visitor)
                                        <tr>
                                            <td>{{ ++$i }}</td>
                                            <td>
                                                <img src="{{ $visitor->images ?? asset('assets/img/default/user.png') }}" 
                                                     alt="" style="width:50px; height:50px; object-fit:cover; border-radius:4px;">
                                            </td>
                                            <td>{{ $visitor->reg_no }}</td>
                                            <td>{{ Str::limit(optional($visitor->visitor)->name, 30) }}</td>
                                            <td>{{ Str::limit(optional($visitor->visitor)->email, 30) }}</td>
                                            <td>{{ optional($visitor->visitor)->country_code ?? '' }}{{ optional($visitor->visitor)->phone }}</td>
                                            <td>{{ optional($visitor->employee)->user->name ?? '' }}</td>
                                            <td>{{ $visitor->purpose ?? '' }}</td>
                                            <td>{{ $visitor->checkin_at ? date('d-m-Y h:i A', strtotime($visitor->checkin_at)) : 'N/A' }}</td>
                                            <td>{{ $visitor->checkout_at ? date('d-m-Y h:i A', strtotime($visitor->checkout_at)) : 'N/A' }}</td>
                                            <td>{{ $visitor->entry_gate_number ?? '—' }}</td>
                                            <td>{{ $visitor->vehicle_number ?? '—' }}</td>
                                            <td>
                                                @if($visitor->vehicle_compliance)
                                                    <span class="{{ $visitor->vehicle_compliance == 'Ok' ? 'text-success' : 'text-danger' }}">
                                                        {{ $visitor->vehicle_compliance }}
                                                    </span>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>{{ $visitor->vehicle_remarks ?? '—' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <h4 class="text-danger text-center">{{ __('visitor_report.data_not_found') }}</h4>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/modules/bootstrap-datepicker/css/bootstrap-datepicker.min.css') }}">
    <style>
        @media print {
            .table th, .table td {
                font-size: 11px !important;
                padding: 6px 4px !important;
                white-space: nowrap;
            }
            .table {
                width: 100% !important;
            }
            #printablediv {
                padding: 10px;
            }
            .card-header button {
                display: none !important;
            }
        }
    </style>
@endsection

@section('scripts')
    <script src="{{ asset('assets/modules/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('js/report/visitor/index.js') }}"></script>

    <script>
        function printDiv(divName) {
            var printContents = document.getElementById(divName).innerHTML;
            var originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
            window.location.reload();
        }
    </script>
@endsection