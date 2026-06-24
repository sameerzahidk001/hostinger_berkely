<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class ImageAltHelpersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        require_once dirname(__DIR__, 2) . '/app/Helpers/helpers.php';
    }

    public function test_merge_image_alts_stores_and_updates_keys(): void
    {
        $merged = merge_image_alts(
            ['banner_image' => 'Old alt'],
            ['banner_image' => 'AWS training banner', 'overview_img' => 'Overview shot']
        );

        $this->assertSame('AWS training banner', $merged['banner_image']);
        $this->assertSame('Overview shot', $merged['overview_img']);
    }

    public function test_label_request_has_content_detects_nested_image_alts(): void
    {
        $this->assertTrue(label_request_has_content([
            'banner_title' => '',
            'image_alts' => ['banner_image' => 'Saved alt text'],
        ]));

        $this->assertFalse(label_request_has_content([
            'banner_title' => '',
            'image_alts' => ['banner_image' => ''],
        ]));
    }

    public function test_form_image_alt_value_reads_json_string_alts(): void
    {
        $model = new class {
            public $image_alts = '{"banner_image":"Stored banner alt"}';
        };

        $this->assertSame('Stored banner alt', form_image_alt_value($model, 'banner_image'));
    }

    public function test_request_image_alts_reads_nested_label_input(): void
    {
        $request = Request::create('/test', 'POST', [
            'label' => [
                'image_alts' => ['banner_image' => 'AWS banner alt'],
                'banner_title' => 'AWS',
            ],
        ]);

        $this->assertSame(
            ['banner_image' => 'AWS banner alt'],
            request_image_alts($request, 'label.image_alts')
        );
    }
}
