<?php
/**
 * @see https://github.com/artesaos/seotools
 */

return [
    'meta' => [
        /*
         * The default configurations to be used by the meta generator.
         */
        'defaults'       => [
            'title'        => "BERKELEY SCHOOL OF BUSINESS, ARTS & SCIENCES", // set false to total remove
            'titleBefore'  => true, // Put defaults.title before page title, like 'It's Over 9000! - Dashboard'
            'description'  => 'Experience the transformative power of education at our center, where we empower minds to create a better future. Immerse yourself in the academic excellence at Berkeley Hills, located in the heart of California. Our center offers a dynamic learning environment, fostering innovation and critical thinking. Join us and be part of a community dedicated to making a difference in the world. Your journey to a brighter future starts here.', // set false to total remove
            'separator'    => ' - ',
            'keywords'     => ["BERKELEY SCHOOL OF BUSINESS, ARTS & SCIENCES", "professional certification"],
            'canonical'    => false, // Set to null or 'full' to use Url::full(), set to 'current' to use Url::current(), set false to total remove
            'robots'       => false, // Set to 'all', 'none' or any combination of index/noindex and follow/nofollow
        ],
        /*
         * Webmaster tags are always added.
         */
        'webmaster_tags' => [
            'google'    => null,
            'bing'      => null,
            'alexa'     => null,
            'pinterest' => null,
            'yandex'    => null,
            'norton'    => null,
        ],

        'add_notranslate_class' => false,
    ],
    'opengraph' => [
        /*
         * The default configurations to be used by the opengraph generator.
         */
        'defaults' => [
            'title'       => 'BERKELEY SCHOOL OF BUSINESS, ARTS & SCIENCES', // set false to total remove
            'description' => 'Experience the transformative power of education at our center, where we empower minds to create a better future. Immerse yourself in the academic excellence at Berkeley Hills, located in the heart of California. Our center offers a dynamic learning environment, fostering innovation and critical thinking. Join us and be part of a community dedicated to making a difference in the world. Your journey to a brighter future starts here.', // set false to total remove
            'url'         => false, // Set null for using Url::current(), set false to total remove
            'type'        => false,
            'site_name'   => false,
            'images'      => [],
        ],
    ],
    'twitter' => [
        /*
         * The default values to be used by the twitter cards generator.
         */
        'defaults' => [
            'card'        => 'summary_large_image',
            'site'        => env('TWITTER_SITE_HANDLE', ''),
        ],
    ],
    'json-ld' => [
        /*
         * The default configurations to be used by the json-ld generator.
         */
        'defaults' => [
            'title'       => 'BERKELEY SCHOOL OF BUSINESS, ARTS & SCIENCES', // set false to total remove
            'description' => 'Experience the transformative power of education at our center, where we empower minds to create a better future. Immerse yourself in the academic excellence at Berkeley Hills, located in the heart of California. Our center offers a dynamic learning environment, fostering innovation and critical thinking. Join us and be part of a community dedicated to making a difference in the world. Your journey to a brighter future starts here.', // set false to total remove
            'url'         => false, // Set to null or 'full' to use Url::full(), set to 'current' to use Url::current(), set false to total remove
            'type'        => 'WebPage',
            'images'      => [],
        ],
    ],
];
