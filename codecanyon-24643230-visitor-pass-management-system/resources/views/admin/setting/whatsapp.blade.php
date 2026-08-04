@extends('admin.setting.index')

@section('admin.setting.breadcrumbs')
    {{ Breadcrumbs::render('whatsApp-setting') }}
@endsection

@section('admin.setting.layout')
    <div class="col-md-9">
        <div class="card">
            <div class="card-body">
                <form class="form-horizontal" role="form" method="POST" action="{{ route('admin.setting.whatsapp-message-update') }}">
                     @csrf
                     <fieldset class="setting-fieldset">
                         <div class="row">
                             <div class="col-md-6">
                                 <div class="form-group" id="">
                                     <label class="control-label" for="defaultUnchecked">{{__('setting_menu.whats_app_msg')}}</label>
                                     <div class="form-check">
                                         <label class="form-check-label">
                                             <input type="radio" class="form-check-input" name="whatsapp_message" {{ setting('whatsapp_message') == true ? "checked":"" }}  value="1">{{__('Enable')}}
                                         </label>
                                     </div>
                                     <div class="form-check">
                                         <label class="form-check-label">
                                             <input type="radio" class="form-check-input" name="whatsapp_message" {{ setting('whatsapp_message') == false ? "checked":"" }} value="0">{{__('Disable')}}
                                         </label>
                                     </div>
                                 </div>
                             </div>
                         </div>
                    </fieldset>
                    <fieldset class="setting-fieldset">
                        <legend class="setting-legend">{{ __('setting_menu.accept_message') }}</legend>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <textarea class="summernote" name="whatsapp_accept_message" id="whatsapp_accept_message">{{setting('whatsapp_accept_message')}}</textarea>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                    <fieldset class="setting-fieldset">
                        <legend class="setting-legend">{{ __('setting_menu.decline_message') }}</legend>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <textarea class="summernote" name="whatsapp_decline_message" id="whatsapp_decline_message">{{setting('whatsapp_decline_message')}}</textarea>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                     <div class="row">
                        <div class="form-group col-md-6">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <span>{{ __('setting_menu.update_whatsapp_setting') }}</span>
                            </button>
                        </div>
                     </div>
                 </form>
            </div>
        </div>
    </div>
@endsection
