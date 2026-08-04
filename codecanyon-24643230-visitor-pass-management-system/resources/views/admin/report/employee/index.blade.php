@extends('admin.layouts.master')

@section('main-content')

    <section class="section">
        <div class="section-header">
            <h1>{{ __('employee_report.employee_report') }}</h1>
            {{ Breadcrumbs::render('employee_report') }}
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-body">
                    <form action="<?= route('admin.employee-report.post') ?>" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="gender">{{ __('employee_report.department') }}</label>
                                    <select id="department" name="department"
                                        class="form-control @error('department') is-invalid @enderror">
                                        <option value=""></option>
                                        @foreach ($all_department as $department)
                                            <option value={{ $department->id }}
                                                {{ $department->id == $set_department ? 'selected' : '' }}>
                                                {{ $department->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('department')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="gender">{{ __('employee_report.designation') }}</label>
                                    <select id="designation" name="designation"
                                        class="form-control @error('designation') is-invalid @enderror">
                                        <option value=""></option>
                                        @foreach ($all_designation as $designation)
                                            <option value={{ $designation->id }}
                                                {{ $designation->id == $set_designation ? 'selected' : '' }}>
                                                {{ $designation->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('designation')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="gender">{{ __('employee_report.gender') }}</label>
                                    <select id="gender" name="gender"
                                        class="form-control @error('gender') is-invalid @enderror">
                                        <option value=""></option>
                                        @foreach (trans('genders') as $key => $gender)
                                            <option value="{{ $key }}"
                                                {{ $key == $set_gender ? 'selected' : '' }}>
                                                {{ $gender }}</option>
                                        @endforeach
                                    </select>
                                    @error('gender')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <label for="">&nbsp;</label>
                                <button class="btn btn-primary form-control"
                                    type="submit">{{ __('employee_report.get_report') }}</button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>

            @if ($showView)
                <div class="card">
                    <div class="card-header">
                        <h5>{{ __('employee_report.employee_report') }}</h5>
                        <button class="btn btn-success btn-sm report-print-button"
                            onclick="printDiv('printablediv')">{{ __('employee_report.print') }}</button>
                    </div>
                    <div class="card-body" id="printablediv">
                        @if (!blank($employees))
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>{{ __('levels.id') }}</th>
                                            <th>{{ __('levels.image') }}</th>
                                            <th>{{ __('employee_report.name') }}</th>
                                            <th>{{ __('employee_report.email') }}</th>
                                            <th>{{ __('employee_report.phone') }}</th>
                                            <th>{{ __('employee_report.joining_date') }}</th>
                                            <th>{{ __('employee_report.status') }}</th>
                                        </tr>
                                        @php $i =0;@endphp
                                        @foreach ($employees as $employee)
                                            <tr>
                                                <td>{{ $i += 1 }}</td>
                                                <td>
                                                    <figure class="avatar mr-2">
                                                        <img src="{{ $employee->user->images ? $employee->user->images : asset('assets/img/default/user.png') }}" alt="">
                                                    </figure>                                                    
                                                </td>
                                                <td>{{ Str::limit($employee->name, 50) }}</td>
                                                <td>{{ Str::limit(optional($employee->user)->email, 50) }}</td>
                                                <td>{{ $employee->country_code }}{{ $employee->phone }}</td>
                                                <td>{{ $employee->date_of_joining }}</td>
                                                <td>{{ $employee->status == 5 ? 'Active' : 'Inactive' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </thead>
                                </table>
                            </div>
                        @else
                            <h4 class="text-danger">{{ __('employee_report.data_not_found') }}</h4>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </section>

@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/modules/bootstrap-datepicker/css/bootstrap-datepicker.min.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('assets/modules/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('js/report/pre-registers/index.js') }}"></script>
@endsection
