<?php

namespace App\Http\Controllers\Admin;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use App\Models\SiteSettings;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Widget;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function smtpSettings(){

        $smtp = [
            'MAIL_HOST'       => env('MAIL_HOST'),
            'MAIL_PORT'       => env('MAIL_PORT'),
            'MAIL_USERNAME'   => env('MAIL_USERNAME'),
            'MAIL_PASSWORD'   => env('MAIL_PASSWORD'),
            'MAIL_ENCRYPTION' => env('MAIL_ENCRYPTION'),
            'MAIL_FROM_ADDRESS' => env('MAIL_FROM_ADDRESS'),
            'MAIL_FROM_NAME' => env('MAIL_FROM_NAME'),
        ];

        return view('admin.settings.smtp-settings', compact('smtp'));
    }

    public function smtpSettingsStore(Request $request){

        $validator = Validator::make($request->all(), [
            'MAIL_HOST'       => 'required|string',
            'MAIL_PORT'       => 'required|integer',
            'MAIL_USERNAME'   => 'required|string',
            'MAIL_PASSWORD'   => 'required|string',
            'MAIL_ENCRYPTION' => 'nullable|string',
            'MAIL_FROM_ADDRESS' => 'required|email',
            'MAIL_FROM_NAME' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $this->updateSmtpEnv([
            'MAIL_HOST'       => $request->MAIL_HOST,
            'MAIL_PORT'       => $request->MAIL_PORT,
            'MAIL_USERNAME'   => $request->MAIL_USERNAME,
            'MAIL_PASSWORD'   => $request->MAIL_PASSWORD,
            'MAIL_ENCRYPTION' => $request->MAIL_ENCRYPTION,
            'MAIL_FROM_ADDRESS' => $request->MAIL_FROM_ADDRESS,
            'MAIL_FROM_NAME' => $request->MAIL_FROM_NAME,
        ]);

        // Clear config cache
        Artisan::call('config:clear');

        return redirect()->back()->with('success', 'Credentials save successfully!');
    }

    protected function updateSmtpEnv(array $data) {
        
        $envPath = base_path('.env');

        if (File::exists($envPath)) {
            $env = File::get($envPath);

            foreach ($data as $key => $value) {
                $keyPattern = "/^{$key}=.*/m";
                $value = trim($value);

                // Remove quotes if present and format properly
                if (preg_match('/\s/', $value)) {
                    // If the value contains spaces, wrap it in double quotes
                    $replacement = "{$key}=\"{$value}\"";
                } else {
                    // Otherwise, keep it as is (no quotes)
                    $replacement = "{$key}={$value}";
                }

                $env = preg_replace($keyPattern, $replacement, $env);
            }

            File::put($envPath, $env);
        }
    }

    public function siteSettings()
    {
        $settings = SiteSettings::first();
        $menus = Menu::get();
        $pages = Page::get();
        return view('admin.settings.site', compact('settings','menus','pages'));
    }

    public function siteSettingsUpdate(Request $request)
    {
        // Create the validator instance
        $validator = Validator::make($request->all(), [
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'white_logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'favicon' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'header_logo' => 'nullable|string|in:logo,white_logo',
            'header_menu' => 'nullable|string',
            'header_button' => 'nullable|boolean',
            'header_button_text' => 'nullable|string|max:255',
            'header_button_url' => 'nullable|url|max:255',
            'header_search' => 'nullable|boolean',
            'header_search_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'login' => 'nullable|boolean',
            'login_text' => 'nullable|string|max:255',
            'register' => 'nullable|boolean',
            'register_text' => 'nullable|string|max:255',
            'footer_logo' => 'nullable|string|max:255',
            'footer_bg' => 'nullable|string|max:255',
            'footer_text_color' => 'nullable|string|max:255',
            'footer_title_bg' => 'nullable|string|max:255',
            'footer_columns' => 'nullable|string|max:255',
            'copyright_message' => 'nullable|string|max:255',
            'mobile_logo' => 'nullable|string|max:255',
            'mobile_menu_bg' => 'nullable|string|max:255',
            'mobile_menu_color' => 'nullable|string|max:255',
            'home' => 'nullable|string|max:255',
            'courses' => 'nullable|string|max:255',
            'categories' => 'nullable|string|max:255',
            'school' => 'nullable|string|max:255',
            'course_perma' => 'nullable|string|max:255',
            'category_perma' => 'nullable|string|max:255',
            'school_perma' => 'nullable|string|max:255',
        ]);

        // Add additional validation for social media fields
        $socialMedia = ['facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'tiktok', 'whatsapp'];
        foreach ($socialMedia as $platform) {
            $validator->addRules([
                "{$platform}_icon" => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
                "{$platform}_url" => 'nullable|url|max:255',
            ]);
        }

        // Check if the validation fails
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Proceed with updating the settings
        $settings = SiteSettings::firstOrNew([]);

        $logoFields = ['logo', 'white_logo', 'favicon', 'header_search_image'];
        foreach ($logoFields as $field) {
            if ($request->hasFile($field)) {
                $uploadedFile = $request->file($field);
                $filename = "{$field}_" . time() . '.' . $uploadedFile->getClientOriginalExtension();
                $uploadedFile->move(public_path('images'), $filename);

                if (!empty($settings->$field) && file_exists(public_path('images/' . $settings->$field))) {
                    unlink(public_path('images/' . $settings->$field));
                }

                $settings->$field = $filename;
            }
        }

        // Update text and boolean fields
        $fields = [
            'header_logo', 'header_menu', 'header_button', 'header_button_text', 'header_button_url',
            'header_search', 'login', 'login_text', 'register', 'register_text',
            'header_bg', 'header_menu_color', 'header_button_bg', 'header_button_color',
            'copyright_message','footer_bg', 'footer_text_color', 'footer_title_bg', 'footer_columns',
            'footer_logo','mobile_logo','mobile_menu_bg','mobile_menu_color','home','school','categories','courses','school_perma','category_perma','course_perma'
        ];
        foreach ($fields as $field) {
            $settings->$field = $request->input($field, $settings->$field);
        }

        // Handle social media fields
        foreach ($socialMedia as $platform) {
            $iconField = "{$platform}_icon";
            $urlField = "{$platform}_url";

            if ($request->hasFile($iconField)) {
                $uploadedFile = $request->file($iconField);
                $filename = "{$platform}_icon_" . time() . '.' . $uploadedFile->getClientOriginalExtension();
                $uploadedFile->move(public_path('images'), $filename);

                if (!empty($settings->$iconField) && file_exists(public_path('images/' . $settings->$iconField))) {
                    unlink(public_path('images/' . $settings->$iconField));
                }

                $settings->$iconField = $filename;
            }

            $settings->$urlField = $request->input($urlField, $settings->$urlField);
        }

        // Save settings
        $settings->save();

        return redirect()->back()->with([
            'success' => 'Settings updated successfully.',
            'active_tab' => $request->input('active_tab')
        ]);
    }
    public function getHeaderSettings()
    {
        $settings = SiteSettings::first();
        return $settings;
    }
    public function headerSetting()
    {
        $settings = DB::table('site_settings')->first(); // Get the only row


        return view('admin.settings.header', compact('settings'));
    }
    public function updateHeaderSetting(Request $request)
    {
        // Example validation based on expected columns
        $request->validate([
            'header' => 'nullable',
            // add other fields as needed
        ]);

        DB::table('site_settings')->update([
            'header_settings' => $request->header,
            // map other fields here
            'updated_at' => now()
        ]);

        return redirect()->back()->with('success', 'Settings updated successfully!');
    }
    public function footerSetting()
    {
        $settings = DB::table('site_settings')->first(); // Get the only row

        return view('admin.settings.footer', compact('settings'));
    }
    public function updateFooterSetting(Request $request)
    {
        $request->validate([
            'footer' => 'nullable|string',
            'invoice_footer_usa' => 'nullable|string',
            'invoice_footer_uk' => 'nullable|string',
            'invoice_footer_middle_east' => 'nullable|string',
            'invoice_footer_email' => 'nullable|string|max:255',
            'invoice_footer_website' => 'nullable|string|max:255',
            'invoice_footer_presence' => 'nullable|string|max:500',
        ]);

        $payload = [
            'footer_settings' => $request->footer,
            'updated_at' => now(),
        ];

        foreach ([
            'invoice_footer_usa',
            'invoice_footer_uk',
            'invoice_footer_middle_east',
            'invoice_footer_email',
            'invoice_footer_website',
            'invoice_footer_presence',
        ] as $field) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('site_settings', $field)) {
                $payload[$field] = $request->input($field);
            }
        }

        $row = DB::table('site_settings')->select('id')->first();
        if ($row && isset($row->id)) {
            DB::table('site_settings')->where('id', $row->id)->update($payload);
        } else {
            $payload['created_at'] = now();
            DB::table('site_settings')->insert($payload);
        }

        return redirect()->back()->with('success', 'Settings updated successfully!');
    }

    public function widget(){

        $menus = Menu::pluck('menu_group')->unique();
        $widgets = Widget::all();
        return view('admin.settings.widget', compact('widgets', 'menus'));
    }

    public function widgetStore(Request $request){

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'menu' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $widget = Widget::findOrFail($request->id);
        $widget->title = $request->title;
        $widget->description = $request->description;
        $widget->menu = $request->menu;
        $widget->save();

        return redirect()->back()->with('success', 'Widget settings save successfully!');
    }
}