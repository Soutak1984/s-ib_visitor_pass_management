<?php

namespace App\Http\Controllers\Admin;

use Setting;
use App\Enums\Status;
use App\Models\Language;
use App\Libraries\MyString;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\BackendController;

class SettingController extends BackendController
{

    public function __construct()
    {
        parent::__construct();

        $this->data['sitetitle'] = 'Settings';

        $this->middleware(['permission:setting']);
    }

    // Site Setting
    public function index()
    {
        $this->data['language'] = Language::where('status', Status::ACTIVE)->get();
        return view('admin.setting.site', $this->data);
    }

    public function siteSettingUpdate(Request $request)
    {


        $niceNames = [];
        $settingArray = $this->validate($request, $this->siteValidateArray(), [], $niceNames);

        if ($request->hasFile('site_logo')) {
            $site_logo = request('site_logo');
            $settingArray['site_logo'] = $site_logo->getClientOriginalName();
            $request->site_logo->move(public_path('images'), $settingArray['site_logo']);
        } else {
            unset($settingArray['site_logo']);
        }

        if (isset($settingArray['timezone'])) {
            MyString::setEnv('APP_TIMEZONE', $settingArray['timezone']);
            Artisan::call('optimize:clear');
        }

        Setting::set($settingArray);
        Setting::save();

        return redirect(route('admin.setting.index'))->withSuccess('The Site setting updated successfully');
    }

    // SMS Setting
    public function smsSetting()
    {
        return view('admin.setting.sms');
    }

    public function smsSettingUpdate(Request $request)
    {
        if ($request->settingsms == 'twilio') {
            $niceNames = [];
            $settingArray = $this->validate($request, $this->smsValidateArray(), [], $niceNames);

            Setting::set($settingArray);
            Setting::save();
        }

        return redirect(route('admin.setting.sms'))->withSuccess('The SMS setting updated successfully.');
    }
    // FCM Setting
    public function fcmSetting()
    {
        return view('admin.setting.fcm-notification');
    }

    public function fcmSettingUpdate(Request $request)
    {
        $niceNames = [];
        $rules = $this->fcmNotificationValidateArray();

        $validator = Validator::make($request->all(), $rules, [], $niceNames);
        $this->withValidator($validator);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $notificationSettingArray = $validator->validated();

        if ($request->hasFile('private_key')) {
            $previous_file = setting('private_key');
            if (File::exists(storage_path('app/firebase/' . $previous_file))) {
                File::delete(storage_path('app/firebase/' . $previous_file));
            }
            $request_file = $request->file('private_key');
            $extension = $request_file->getClientOriginalExtension();
            $notificationSettingArray['private_key'] = 'service-account-file' . '.' . $extension;
            $uniqueFile = $notificationSettingArray['private_key'];
            $request->file('private_key')->move(storage_path('app/firebase/'), $uniqueFile);
        }

        Setting::set($notificationSettingArray);
        Setting::save();
        
        return redirect(route('admin.setting.fcm'))->withSuccess('The Firebase Notification setting updated successfully.');
    }

    // email template Setting
    public function emailTemplateSetting()
    {
        return view('admin.setting.email-template');
    }

    public function mailTemplateSettingUpdate(Request $request)
    {
        $niceNames = [];
        $settingArray = $this->validate($request, $this->emailTemplateValidateArray(), [], $niceNames);

        Setting::set($settingArray);
        Setting::save();
        return redirect(route('admin.setting.email-template'))->withSuccess('The Email & Sms template setting updated successfully.');
    }

    // EMail Setting
    public function emailSetting()
    {
        return view('admin.setting.email');
    }


    public function emailSettingUpdate(Request $request)
    {
        $niceNames = [];
        $emailSettingArray = $this->validate($request, $this->emailValidateArray(), [], $niceNames);
        if (isset($emailSettingArray['mail_host'])) {
            MyString::setEnv('MAIL_HOST', $emailSettingArray['mail_host']);
        }

        if (isset($emailSettingArray['mail_port'])) {
            MyString::setEnv('MAIL_PORT', $emailSettingArray['mail_port']);
        }

        if (isset($emailSettingArray['mail_username'])) {
            MyString::setEnv('MAIL_USERNAME', $emailSettingArray['mail_username']);
        }

        if (isset($emailSettingArray['mail_password'])) {
            MyString::setEnv('MAIL_PASSWORD', $emailSettingArray['mail_password']);
        }

        if (isset($emailSettingArray['mail_encryption'])) {
            MyString::setEnv('MAIL_ENCRYPTION', $emailSettingArray['mail_encryption']);
        }

        if (isset($emailSettingArray['mail_from_address'])) {
            $address = '"' . $emailSettingArray['mail_from_address'] . '"';
            MyString::setEnv('MAIL_FROM_ADDRESS', $address);
        }

        if (isset($emailSettingArray['mail_from_name'])) {
            $name = '"' . $emailSettingArray['mail_from_name'] . '"';
            MyString::setEnv('MAIL_FROM_NAME', $name);
        }
        Artisan::call('optimize:clear');

        Setting::set($emailSettingArray);
        Setting::save();

        return redirect(route('admin.setting.email'))->withSuccess('The Email setting updated successfully');
    }
    // Notification Setting
    public function notificationSetting()
    {
        return view('admin.setting.notification');
    }

    public function notificationSettingUpdate(Request $request)
    {

        $niceNames = [];
        $notificationSettingArray = $this->validate($request, $this->notificationValidateArray(), [], $niceNames);

        Setting::set($notificationSettingArray);
        Setting::save();

        return redirect(route('admin.setting.notification'))->withSuccess('The Notification setting updated successfully.');
    }

    // Homepage Setting
    public function homepageSetting()
    {
        return view('admin.setting.homepage');
    }

    public function homepageSettingUpdate(Request $request)
    {
        $niceNames = [];
        $settingArray = $this->validate($request, $this->frontendValidateArray(), [], $niceNames);
        Setting::set($settingArray);
        Setting::save();

        return redirect(route('admin.setting.homepage'))->withSuccess('The Home page setting updated successfully');
    }



    // Site Setting validation
    private function siteValidateArray()
    {
        return [
            'site_name' => 'required|string|max:100',
            'site_email' => 'required|string|max:100',
            'site_phone_number' => 'required',
            'max:60',
            'site_footer' => 'required|string|max:200',
            'timezone' => 'required|string',
            'site_logo' => 'nullable|mimes:jpeg,jpg,png,gif|max:3096',
            'site_description' => 'required|string|max:500',
            'site_address' => 'required|string|max:500',
            'locale' => 'nullable|string',
        ];
    }

    // SMS Setting validation
    private function smsValidateArray()
    {
        return [
            'twilio_auth_token' => 'required|string|max:200',
            'twilio_account_sid' => 'required|string|max:200',
            'twilio_from' => 'required|string|max:20',
            'twilio_disabled' => 'numeric',
        ];
    }


    // EMAIL Setting validation
    private function emailValidateArray()
    {
        return [
            'mail_host' => 'required|string|max:100',
            'mail_port' => 'required|string|max:100',
            'mail_username' => 'required|string|max:100',
            'mail_password' => 'required|string|max:100',
            'mail_from_name' => 'required|string|max:100',
            'mail_from_address' => 'required|string|max:200',
            'mail_disabled' => 'numeric',
        ];
    }

    // Notification Setting validation
    private function notificationValidateArray()
    {
        return [
            'notifications_email' => 'nullable|string|max:100',
            'notifications_sms' => 'nullable|string|max:100',
        ];
    }

    // FCM Notification Setting validation
    private function fcmNotificationValidateArray()
    {
        return [
            'apiKey'            => 'required|string|max:255',
            'authDomain'        => 'required|string|max:255',
            'projectId'         => 'required|string|max:255',
            'storageBucket'     => 'required|string|max:255',
            'messagingSenderId' => 'required|string|max:255',
            'appId'             => 'required|string|max:255',
            'measurementId'     => 'required|string|max:255',
            'private_key'       => ['nullable', 'file', 'mimes:json', 'max:2048']
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (empty(request('private_key'))) {
                $notification = setting('private_key');
                if (empty($notification)) {
                    $validator->errors()->add('private_key', 'The file field is required');
                }
            }
        });
    }

    // Notification Setting validation
    private function emailTemplateValidateArray()
    {
        return [
            'notify_templates' => 'nullable|string|max:150',
            'invite_templates' => 'nullable|string|max:150',
        ];
    }

    // Homepage Setting validation
    private function frontendValidateArray()
    {
        return [
            'front_end_enable_disable' => 'required|string|max:100',
            'photo_capture_enable' => 'required|string|max:100',
            'terms_visibility_status' => 'required|string|max:100',
            'welcome_screen' => 'nullable|string|max:255',
            'terms_condition' => 'nullable|string',
        ];
    }

    // Notification Setting validation
    private function whatsappValidateArray()
    {
        return [
            'whatsapp_message' => 'nullable|string|max:100',
            'whatsapp_accept_message' => 'nullable|string|max:100',
            'whatsapp_decline_message' => 'nullable|string|max:100',
        ];
    }

    public function whatsappSetting()
    {
        return view('admin.setting.whatsapp');
    }

    public function whatsappSettingupdate(Request $request)
    {
        $niceNames = [];
        $whatsappSettingArray = $this->validate($request, $this->whatsappValidateArray(), [], $niceNames);

        Setting::set($whatsappSettingArray);
        Setting::save();

        return redirect(route('admin.setting.whatsapp-message'))->withSuccess('The Notification setting updated successfully.');
    }

}
