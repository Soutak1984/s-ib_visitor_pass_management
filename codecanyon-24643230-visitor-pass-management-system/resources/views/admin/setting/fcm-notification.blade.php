@extends('admin.setting.index')

@section('admin.setting.breadcrumbs')
    {{ Breadcrumbs::render('fcm_settings') }}
@endsection

@section('admin.setting.layout')
    <div class="col-md-9">
        <div class="card">
            <div class="card-body">
                <form class="form-horizontal" role="form" method="POST" action="{{ route('admin.setting.fcm-update') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <fieldset class="setting-fieldset">
                        <legend class="setting-legend">{{ __('levels.fcm_notification_setting') }}</legend>
                        <div class="row">

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="apiKey">{{ __('levels.apiKey') }}</label>
                                    <span class="text-danger">*</span>
                                    <input name="apiKey" id="apiKey" type="text"
                                        class="form-control @error('apiKey') is-invalid @enderror"
                                        value="{{ old('apiKey', setting('apiKey')) }}">
                                    @error('apiKey')
                                        <div class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="authDomain">{{ __('levels.authDomain') }}</label>
                                    <span class="text-danger">*</span>
                                    <input name="authDomain" id="authDomain" type="text"
                                        class="form-control @error('authDomain') is-invalid @enderror"
                                        value="{{ old('authDomain', setting('authDomain')) }}">
                                    @error('authDomain')
                                        <div class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </div>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <div class="row">

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="projectId">{{ __('levels.projectId') }}</label>
                                    <span class="text-danger">*</span>
                                    <input name="projectId" id="projectId" type="text"
                                        class="form-control @error('projectId') is-invalid @enderror"
                                        value="{{ old('projectId', setting('projectId')) }}">
                                    @error('projectId')
                                        <div class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="storageBucket">{{ __('levels.storageBucket') }}</label>
                                    <span class="text-danger">*</span>
                                    <input name="storageBucket" id="storageBucket" type="text"
                                        class="form-control @error('storageBucket') is-invalid @enderror"
                                        value="{{ old('storageBucket', setting('storageBucket')) }}">
                                    @error('storageBucket')
                                        <div class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </div>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <div class="row">

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="messagingSenderId">{{ __('levels.messagingSenderId') }}</label>
                                    <span class="text-danger">*</span>
                                    <input name="messagingSenderId" id="messagingSenderId" type="text"
                                        class="form-control @error('messagingSenderId') is-invalid @enderror"
                                        value="{{ old('messagingSenderId', setting('messagingSenderId')) }}">
                                    @error('messagingSenderId')
                                        <div class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="appId">{{ __('levels.appId') }}</label>
                                    <span class="text-danger">*</span>
                                    <input name="appId" id="appId" type="text"
                                        class="form-control @error('appId') is-invalid @enderror"
                                        value="{{ old('appId', setting('appId')) }}">
                                    @error('appId')
                                        <div class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </div>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <div class="row">

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="measurementId">{{ __('levels.measurementId') }}</label>
                                    <span class="text-danger">*</span>
                                    <input name="measurementId" id="measurementId" type="text"
                                        class="form-control @error('measurementId') is-invalid @enderror"
                                        value="{{ old('measurementId', setting('measurementId')) }}">
                                    @error('measurementId')
                                        <div class="invalid-feedback">
                                            <strong>{{ $message }}</strong>
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="customFile">{{ __('levels.service_account_private_key_file') }}
                                        ({{ __('levels.json') }})</label>
                                    <span class="text-danger">*</span>
                                    <div class="custom-file">
                                        <input name="private_key" type="file"
                                            class="file-upload-input custom-file-input @error('private_key') is-invalid @enderror"
                                            id="customFile" accept=".json">
                                        <label class="custom-file-label"
                                            for="customFile">{{ __('site_setting.choose_file') }}</label>
                                       
                                    </div>
                                    @error('private_key')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                                </div>
                            </div>

                        </div>

                    </fieldset>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <span>{{ __('levels.update_fcm_notification_setting') }}</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
