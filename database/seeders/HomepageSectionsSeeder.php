<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HomepageSectionsSeeder extends Seeder
{
    public function run()
    {
        $sections = [
            [
                'section_name' => 'hero_section',
                'title' => 'Hero Section',
                'subtitle' => 'The World\'s Best Education with the best-in-class ...',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent commodo cursus magna.',
                'image' => 'https://developers.elementor.com/docs/assets/img/elementor-placeholder-image.png',
                'link' => '#',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section_name' => 'about_us',
                'title' => 'About Us',
                'subtitle' => 'Subtitle',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam quis risus eget urna mollis ornare vel eu leo.',
                'image' => 'https://developers.elementor.com/docs/assets/img/elementor-placeholder-image.png',
                'link' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section_name' => 'courses',
                'title' => 'Explore Courses',
                'subtitle' => 'Find your next learning experience.',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum id ligula porta felis euismod semper.',
                'image' => 'https://developers.elementor.com/docs/assets/img/elementor-placeholder-image.png',
                'link' => '#',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section_name' => 'centre_for_education',
                'title' => 'Centre for Education',
                'subtitle' => null,
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh.',
                'image' => 'https://developers.elementor.com/docs/assets/img/elementor-placeholder-image.png',
                'link' => '#',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section_name' => 'diplomas',
                'title' => 'Diplomas',
                'subtitle' => null,
                'description' => null,
                'image' => 'https://developers.elementor.com/docs/assets/img/elementor-placeholder-image.png',
                'link' => '#',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section_name' => 'graduate_courses',
                'title' => 'Graduate Courses',
                'subtitle' => null,
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                'image' => 'https://developers.elementor.com/docs/assets/img/elementor-placeholder-image.png',
                'link' => '#',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section_name' => 'master_courses',
                'title' => 'Master Courses',
                'subtitle' => null,
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                'image' => 'https://developers.elementor.com/docs/assets/img/elementor-placeholder-image.png',
                'link' => '#',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section_name' => 'women_entrepreneurs',
                'title' => 'Women Entrepreneurs Support Centre',
                'subtitle' => null,
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                'image' => 'https://developers.elementor.com/docs/assets/img/elementor-placeholder-image.png',
                'link' => '#',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section_name' => 'cfo_academy',
                'title' => 'CFO Academy',
                'subtitle' => null,
                'description' => null,
                'image' => 'https://developers.elementor.com/docs/assets/img/elementor-placeholder-image.png',
                'link' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section_name' => 'ceo_academy',
                'title' => 'CEO Academy',
                'subtitle' => null,
                'description' => null,
                'image' => 'https://developers.elementor.com/docs/assets/img/elementor-placeholder-image.png',
                'link' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section_name' => 'entrepreneurs_academy',
                'title' => 'Entrepreneurs Academy',
                'subtitle' => null,
                'description' => null,
                'image' => 'https://developers.elementor.com/docs/assets/img/elementor-placeholder-image.png',
                'link' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'section_name' => 'success_stories',
                'title' => 'Success Stories',
                'subtitle' => null,
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                'image' => 'https://developers.elementor.com/docs/assets/img/elementor-placeholder-image.png',
                'link' => '#',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('homepage_sections')->insert($sections);
    }
}
