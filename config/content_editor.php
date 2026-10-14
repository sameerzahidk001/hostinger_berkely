<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenAI-compatible AI writing
    |--------------------------------------------------------------------------
    */
    'ai' => [
        'enabled' => env('CONTENT_AI_ENABLED', true),
        'api_key' => env('OPENAI_API_KEY', ''),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'timeout' => (int) env('OPENAI_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default character limits (Admin can override in Settings → Content Editor)
    | Counts plain text (HTML tags stripped) for rich editors.
    |--------------------------------------------------------------------------
    */
    'limits' => [
        'pages' => [
            'page_name' => ['label' => 'Page name', 'max' => 120],
            'page_url' => ['label' => 'Page URL', 'max' => 180],
            'section_title' => ['label' => 'Section title / heading', 'max' => 180],
            'section_subtitle' => ['label' => 'Section subtitle', 'max' => 250],
            'section_description' => ['label' => 'Section description / body', 'max' => 5000],
            'card_title' => ['label' => 'Card title', 'max' => 120],
            'card_description' => ['label' => 'Card description', 'max' => 800],
            'list_item' => ['label' => 'List item text', 'max' => 400],
            'button_text' => ['label' => 'Button / CTA text', 'max' => 80],
            'default' => ['label' => 'Other page text fields', 'max' => 3000],
        ],
        'courses' => [
            'title' => ['label' => 'Course title', 'max' => 200],
            'short_description' => ['label' => 'Short description', 'max' => 500],
            'description' => ['label' => 'Detailed description', 'max' => 10000],
            'vision_and_mission' => ['label' => 'Vision and mission', 'max' => 5000],
            'eligibility' => ['label' => 'Eligibility', 'max' => 5000],
            'career_path' => ['label' => 'Career path', 'max' => 5000],
            'exam_dates' => ['label' => 'Exam dates', 'max' => 3000],
            'exam_reg_deadline' => ['label' => 'Exam registration deadline', 'max' => 3000],
            'exam_passing_criteria' => ['label' => 'Exam passing criteria', 'max' => 3000],
            'exam_location_paragraph' => ['label' => 'Exam location paragraph', 'max' => 3000],
            'exam_info_custom_01' => ['label' => 'Custom exam info', 'max' => 5000],
            'course_exam_format_duration_overview' => ['label' => 'Exam format / duration overview', 'max' => 5000],
            'performance_standard_description' => ['label' => 'Performance standard description', 'max' => 5000],
            'custom_videos_desc' => ['label' => 'Custom videos description', 'max' => 3000],
            'custom_section_01_description' => ['label' => 'Custom section description', 'max' => 5000],
            'alumni_benefits_description' => ['label' => 'Alumni benefits description', 'max' => 5000],
            'contact_us_text' => ['label' => 'Contact us text', 'max' => 3000],
            'offered_by' => ['label' => 'Offered by fields', 'max' => 3000],
            'benefits_description' => ['label' => 'Benefits description', 'max' => 3000],
            'label_text' => ['label' => 'Dynamic label text fields', 'max' => 4000],
            'default' => ['label' => 'Other course text fields', 'max' => 5000],
        ],
    ],
];
