<?php

namespace Database\Seeders;

use App\Models\SiteSettings;
use App\Models\Widget;
use Illuminate\Database\Seeder;

class FooterSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = SiteSettings::first();
        if ($settings) {
            $settings->update([
                'footer_bg' => '#0e0e0e',
                'footer_title_bg' => '#ffffff',
                'footer_text_color' => '#8996a0',
                'footer_logo' => 'white_logo',
                'white_logo' => 'white_logo_1749981087.png',
                'copyright_message' => 'Copyright © 2026 Berkeley School of Business, Arts & Sciences | UKPRN: 10101119',
            ]);
        }

        $titles = [
            1 => 'Levels',
            2 => 'Subject Areas',
            3 => 'Subject Areas',
            4 => 'Policies',
        ];

        foreach ($titles as $id => $title) {
            Widget::where('id', $id)->update(['title' => $title]);
        }
    }
}
