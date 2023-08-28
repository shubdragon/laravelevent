<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\Setting;
use App\Models\Timezone;
use App\Http\Controllers\AppHelper;
use App\Models\ContactUs;
use App\Models\Language;
use App\Models\PaymentSetting;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;

use function GuzzleHttp\Promise\all;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $mode = false;
        if (App::isDownForMaintenance()) {
            $mode = true;
        } else {
            $mode = false;
        }
        $setting = Setting::find(1);
        $currencies = Currency::get();
        $timezone = Timezone::get();
        $payment = PaymentSetting::find(1);
        $languages = Language::whereStatus(1)->get();
        $aboutUs = ContactUs::find(1);
        return view('admin.setting', compact('mode', 'setting', 'languages', 'currencies', 'payment', 'timezone','aboutUs'));
    }


    public function store(Request $request)
    {
        $data = $request->all();
        $setting = Setting::find(1);


        if ($request->hasFile('logo')) {
            if ($setting->logo != null) {
                Storage::delete('/images/upload' . $setting->logo);
            }
            $logo = $request->file('logo');
            $name = uniqid() . '.' . $logo->getClientOriginalExtension();
            $destinationPath = public_path('/images/upload');
            $logo->move($destinationPath, $name);
            $data['logo'] = $name;
        }
        if ($request->hasFile('favicon')) {
            if ($setting->favicon != null) {
                Storage::delete('/images/upload' . $setting->favicon);
            }
            $favicon = $request->file('favicon');
            $faviconName = uniqid() . '.' . $favicon->getClientOriginalExtension();
            $faviconPath = public_path('/images/upload');
            $favicon->move($faviconPath, $faviconName);
            $data['favicon'] = $faviconName;
        }
        Setting::find(1)->update($data);
        return redirect('admin-setting')->withStatus(__('Setting saved successfully.'));
    }

    public function saveMailSetting(Request $request)
    {
        $request->validate([
            'mail_host' => 'bail|required_if:mail_notification,1',
            'mail_port' => 'bail|required_if:mail_notification,1',
            'mail_username' => 'bail|required_if:mail_notification,1',
            'mail_password' => 'bail|required_if:mail_notification,1',
            'sender_email' => 'bail|required_if:mail_notification,1',
        ]);
        $envdata['MAIL_MAILER'] = $request->mail_mailer;
        $envdata['MAIL_HOST'] = $request->mail_host;
        $envdata['MAIL_PORT'] = $request->mail_port;
        $envdata['MAIL_USERNAME'] = $request->mail_username;
        $envdata['MAIL_PASSWORD'] = $request->mail_password;
        $envdata['MAIL_ENCRYPTION'] = $request->mail_encryption;
        $envdata['MAIL_FROM_ADDRESS'] = $request->sender_email;
        
        $envFile = app()->environmentFilePath();
        if ($envFile) {
            $str = file_get_contents($envFile);
            if (count($envdata) > 0) {
                foreach ($envdata as $envKey => $envValue) {
                    $str .= "\n"; // In case the searched variable is in the last line without \n
                    $keyPosition = strpos($str, "{$envKey}=");
                    $endOfLinePosition = strpos($str, "\n", $keyPosition);
                    $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);
                    // If key does not exist, add it
                    if (!$keyPosition || !$endOfLinePosition || !$oldLine) {
                        $str .= "{$envKey}={$envValue}\n";
                    } else {
                        $str = str_replace($oldLine, "{$envKey}={$envValue}", $str);
                    }
                }
            }
            $str = substr($str, 0, -1);

            try {
                if (!file_put_contents($envFile, $str)) {
                    Artisan::call('config:clear');
                    Artisan::call('cache:clear');
                    return response()->json(['data' => null, 'success' => true], 200);
                }
            } catch (Exception $e) {
                return response()->json(['data' => $e, 'success' => false], 200);
            }
        }
        $data = $request->all();
        if (!isset($request->mail_notification)) {
            $data['mail_notification'] = 0;
        }
        Setting::find(1)->update($data);
        return redirect('admin-setting')->withStatus(__('Setting saved successfully.'));
    }

    public function saveVerificationSetting(Request $request)
    {
        $request->validate([
            'verify_by' => 'bail|required_if:user_verify,1',
        ]);
        $data = $request->all();
        if (!isset($request->user_verify)) {
            $data['user_verify'] = 0;
        }
        Setting::find(1)->update($data);
        return redirect('admin-setting')->withStatus(__('Setting saved successfully.'));
    }

    public function saveOrganizationSetting(Request $request)
    {
        $request->validate([
            'terms_use_organizer' => 'bail|required',
            'privacy_policy_organizer' => 'bail|required',
        ]);
        Setting::find(1)->update($request->all());
        return redirect('admin-setting')->withStatus(__('Setting saved successfully.'));
    }

    public function saveOnesignalSetting(Request $request)
    {
        $request->validate([
            'onesignal_app_id' => 'bail|required_if:push_notification,1',
            'onesignal_project_number' => 'bail|required_if:push_notification,1',
            'onesignal_api_key' => 'bail|required_if:push_notification,1',
            'onesignal_auth_key' => 'bail|required_if:push_notification,1',
            'or_onesignal_app_id' => 'bail|required_if:push_notification,1',
            'or_onesignal_project_number' => 'bail|required_if:push_notification,1',
            'or_onesignal_api_key' => 'bail|required_if:push_notification,1',
            'or_onesignal_auth_key' => 'bail|required_if:push_notification,1',
        ]);
        $data = $request->all();
        if (!isset($request->push_notification)) {
            $data['push_notification'] = 0;
        }
        Setting::find(1)->update($data);
        return redirect('admin-setting')->withStatus(__('Setting saved successfully.'));
    }


    public function saveSmsSetting(Request $request)
    {
        $request->validate([
            'twilio_account_id' => 'bail|required_if:sms_notification,1',
            'twilio_auth_token' => 'bail|required_if:sms_notification,1',
            'twilio_phone_number' => 'bail|required_if:sms_notification,1',
        ]);
        $data = $request->all();
        if (!isset($request->sms_notification)) {
            $data['sms_notification'] = 0;
        }
        Setting::find(1)->update($data);
        return redirect('admin-setting')->withStatus(__('Setting saved successfully.'));
    }

    public function additionalSetting(Request $request)
    {
        $data = $request->all();
        $currency = Currency::find($request->currency);
        $data['currency'] = $currency->code;
        $data['currency_sybmol'] = $currency->symbol;
        $data['currency_id'] = $currency->id;
        Setting::find(1)->update($data);
        $this->setLanguage();

        return redirect('admin-setting')->withStatus(__('Setting saved successfully.'));
    }

    public function setLanguage()
    {
        $name = Setting::first()->language;
        if (!$name) {
            $name = 'english';
        }
        App::setLocale($name);
        session()->put('locale', $name);
        $direction = Language::where('name', $name)->first()->direction;
        session()->put('direction', $direction);
        return true;
    }

    public function supportSetting(Request $request)
    {
        Setting::find(1)->update($request->all());
        return redirect('admin-setting')->withStatus(__('Setting saved successfully.'));
    }

    public function savePaymentSetting(Request $request)
    {
        $request->validate([
            'stripeSecretKey' => 'bail|required_if:stripe,1',
            'stripePublicKey' => 'bail|required_if:stripe,1',
            'paypalClientId' => 'bail|required_if:paypal,1',
            'paypalSecret' => 'bail|required_if:paypal,1',
            'razorPublishKey' => 'bail|required_if:razor,1',
            'razorSecretKey' => 'bail|required_if:razor,1',
            // 'flutterWavePublicKey' => 'bail|required_if:flutterwave,1',
            // 'flutterWaveSecretKey' => 'bail|required_if:flutterwave,1',
            'flutterDebugMode' => 'bail|required_if:flutterwave,1',
            'ravePublicKey' => 'bail|required_if:flutterwave,1',
            'raveSecretKey' => 'bail|required_if:flutterwave,1',
        ]);
        $data = $request->all();

        if (!isset($request->stripe)) {
            $data['stripe'] = 0;
        }
        if (!isset($request->paypal)) {
            $data['paypal'] = 0;
        }
        if (!isset($request->razor)) {
            $data['razor'] = 0;
        }
        if (!isset($request->flutterwave)) {
            $data['flutterwave'] = 0;
        }
        if (!isset($request->cod)) {
            $data['cod'] = 0;
        }

        PaymentSetting::find(1)->update($data);
        return redirect('admin-setting')->withStatus(__('Setting saved successfully.'));
    }
    public function socialmedialinks(Request $request)
    {
        Setting::find(1)->update($request->all());
        return redirect('admin-setting')->withStatus(__('Setting saved successfully.'));
    }
    public function maintain()
    {
        if (App::isDownForMaintenance()) {
            $setting = Setting::first();
            return view('admin.maintenance', compact('setting'));
        }
        return redirect('/');
    }
    public function maintenanceMode(Request $request)
    {
        $setting = Setting::first();
        if ($request->hasFile('maintenance_bgimg')) {
            if ($setting->maintenance_bgimg != null) {
                Storage::delete('/images/upload' . $setting->maintenance_bgimg);
            }
            $bg_img = $request->file('maintenance_bgimg');
            $name = uniqid() . '.' . $bg_img->getClientOriginalExtension();
            $destinationPath = public_path('/images/upload');
            $bg_img->move($destinationPath, $name);
            $setting['maintenance_bgimg'] = $name;
        }
        if (isset($request->maintenance_mode)) {

            Artisan::call('down');
        } else {
            Artisan::call('up');
        }
        $setting->maintenance_text = $request->maintenance_text;

        $setting->save();
        return redirect()->back()->with('message');
    }
    public function appuserPrivacyPolicy(Request $request)
    {
        Setting::find(1)->update($request->all());
        return redirect('admin-setting')->withStatus(__('Setting saved successfully.'));
    }

    public function aboutUs(Request $request)
    {
        $data = $request->all();
        if (ContactUs::find(1)) {
            ContactUs::find(1)->update($data);
        } else {
            ContactUs::create($data);
        }
        return redirect()->back()->withStatus(__('Setting saved successfully.'));
    }
}
