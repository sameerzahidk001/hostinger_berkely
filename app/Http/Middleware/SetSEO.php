<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Artesaos\SEOTools\Facades\SEOTools;
use App\Models\{Page,Course};
use Illuminate\Support\Str;

class SetSEO
{
    public function handle($request, Closure $next)
    {
        $currentUrl = $request->path();
        if (Str::startsWith($currentUrl, 'course/')) {
            $course_slug = Str::after($currentUrl, 'course/');
            $seoData = Course::where('slug', $course_slug)->with('seo')->first();
        }else{
            $seoData = Page::where('url', $currentUrl)->with('seo')->first();
        }

        if (!$seoData) {
            $defaultSlug = 'general'; // Set your default slug here
            $seoData = Page::where('url', $defaultSlug)->with('seo')->first();
        }

        if ($seoData && $seoData->seo) {
            // Set basic SEO meta tags
            SEOTools::setTitle($seoData->seo->title);
            SEOTools::setDescription($seoData->seo->meta_description);
            SEOTools::setCanonical(url()->current());
            SEOTools::metatags()->setKeywords($seoData->seo->keywords);  // Add keywords

            // Open Graph (Facebook, LinkedIn, etc.)
            SEOTools::opengraph()->setTitle($seoData->seo->title);
            SEOTools::opengraph()->setDescription($seoData->seo->meta_description);
            SEOTools::opengraph()->setUrl(url()->current());
            SEOTools::opengraph()->addImage(asset($seoData->seo->thumbnail)); // Full URL for the image
            SEOTools::opengraph()->addProperty('type', 'article');

            // Twitter Cards
            SEOTools::twitter()->setTitle($seoData->seo->title);
            SEOTools::twitter()->setDescription($seoData->seo->meta_description);
            SEOTools::twitter()->setUrl(url()->current());
            SEOTools::twitter()->setImage(asset($seoData->seo->thumbnail));   // Full URL for Twitter
            SEOTools::twitter()->setSite('@your_twitter_handle');  // Replace with your Twitter handle

            // Pinterest Rich Pins
            SEOTools::metatags()->addMeta('pinterest-rich-pin', 'true');

            // WhatsApp Share Link (WhatsApp does not use specific meta tags)
            $whatsappUrl = "https://wa.me/?text=" . urlencode($seoData->seo->title . " - " . url()->current());
            SEOTools::opengraph()->addProperty('whatsapp-share-url', $whatsappUrl);
        }

        return $next($request);
    }
}
