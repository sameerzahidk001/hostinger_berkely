@extends('layouts.app')

@section('content')

    @foreach ($page->sections as $section)
        @if ($section->section_type === 'hero-banner')
            <x-hero-banner background="{{ asset('images/library/' . $section->data['image']) }}"
                :altText="image_alt($section->data['image_alt'] ?? null, $section->data['title'] ?? 'Hero banner')"
                title="{{ $section->data['title'] ?? 'Default Title' }}"
                subtitle="{{ $section->data['subtitle'] ?? 'Default Subtitle' }}" video="{{$section->data['video'] ?? '' }}" />

        @elseif ($section->section_type === 'banner')
            <x-banner 
                :breadcrumb="$section->data['breadcrumbs'] ?? false" 
                :title="$section->data['title'] ?? ''" 
                :subtitle="$section->data['subtitle'] ?? ''"
                :description="$section->data['description'] ?? ''"
                :backgroundColor="$section->data['background_color'] ?? ''"
                :image="!empty($section->data['image']) ? asset('images/library/' . $section->data['image']) : ''"
                :altText="image_alt($section->data['image_alt'] ?? null, $section->data['title'] ?? 'Banner')"
                :color="$section->data['color'] ?? ''"
                :solidButtonUrl="$section->data['solid_button_url'] ?? ''"
                :solidButtonText="$section->data['solid_button_text'] ?? ''"
                :outlineButtonUrl="$section->data['outline_button_url'] ?? ''"
                :outlineButtonText="$section->data['outline_button_text'] ?? ''"
            />
        @elseif($section->section_type === 'separator')
            <x-separator :color="$section->data['color']" :height="$section->data['height']" />

        @elseif ($section->section_type === 'schools')

            @php
                $locations = $section->schoolDetails->map(function ($school) {
                    return [
                        'image' => 'images/library/' . $school->image,
                        'icon' => 'images/library/' . $school->icon,
                        'image_alt' => image_alt($school->image_alt, $school->name),
                        'icon_alt' => image_alt($school->icon_alt, $school->name . ' icon'),
                        'title' => $school->name,
                        'description' => $school->short_description,
                        'url' => url('school/' . $school->slug) ?? '#',
                        'urltext' => $school->url_text ?? 'Learn More',
                    ];
                })->toArray();
            @endphp
            <x-schools title="{{ $section->data['title'] }}" description="{{ $section->data['description'] }}"
                url="{{ $section->data['url'] ?? '#' }}" urltext="{{ $section->data['url_text'] }}" :locations="$locations" />
        @elseif($section->section_type === 'categories')
            @php
                $categories = $section->categoryDetails->map(function ($category) {
                    return [
                        'name' => $category->name,
                        'url' => url('category/' . $category->slug),
                    ];
                })->toArray();
            @endphp

            <x-categories :background="$section->data['background'] ?? 'none'" :color="$section->data['color'] ?? 'inherit'" :title="$section->data['title']"
                :description="$section->data['description']" :categories="$categories" />
        @elseif ($section->section_type === 'grid-cards')
            @php
                $gridCards = collect($section->data['cards'] ?? [])->map(function ($card) {
                    return [
                        'image' => !empty($card['image']) ? asset('images/library/' . $card['image']) : null,
                        'title' => $card['title'] ?? '',
                        'description' => $card['description'] ?? '',
                        'url' => $card['url'] ?? null,
                        'url_text' => $card['url_text'] ?? null,
                        'url_target' => $card['url_target'] ?? null,
                        'background' => $card['background'] ?? null,
                        'color' => $card['color'] ?? null,
                        'image_alt' => image_alt($card['image_alt'] ?? null, $card['title'] ?? 'Card image'),
                    ];
                })->toArray();
            @endphp
            <x-grid-cards 
                :title="$section->data['title'] ?? ''" 
                :subtitle="$section->data['subtitle'] ?? ''"
                :description="$section->data['description'] ?? ''"
                :backgroundColor="$section->data['background'] ?? ''"
                :backgroundImage="!empty($section->data['image']) ? asset('images/library/' . $section->data['image']) : ''"
                :backgroundImageAlt="image_alt($section->data['image_alt'] ?? null, $section->data['title'] ?? 'Section background')"
                :color="$section->data['color'] ?? ''"
                :cardColor="$section->data['card_color'] ?? ''"
                :layout="$section->data['layout'] ?? ''"
                :columns="$section->data['columns'] ?? 3"
                :cards="$gridCards"
            />
        @elseif ($section->section_type === 'title-section')
            <x-title-section 
                :background="$section->data['background'] ?? 'transparent'" 
                :color="$section->data['color'] ?? '#000000'" 
                :alignment="$section->data['alignment'] ?? 'left'" 
                :title="$section->data['title'] ?? ''" 
                :description="$section->data['description'] ?? ''"
                :link="$section->data['url'] ?? '#'" 
                :buttonText="$section->data['url_text'] ?? 'Learn More'" 
                :newTab="false" 
            />
        @elseif ($section->section_type === 'media-section')
            <x-media-section :alignment="$section->data['alignment'] ?? 'left'" :direction="$section->data['image_placement']" :image="asset('images/library/' . $section->data['image'])" :icon="$section->data['icon'] ?? null" :title="$section->data['title']" :description="$section->data['description']"
                :altText="image_alt($section->data['image_alt'] ?? null, $section->data['title'] ?? 'Media image')"
                :link="$section->data['url']" :buttonText="$section->data['url_text']" />
        @elseif ($section->section_type === 'overlay-cards')
            @php
                $cards = collect($section->data['cards'] ?? [])->map(function ($card) {
                    return [
                        'image' => (!empty($card['image']) && is_string($card['image'])) ? asset('images/library/' . $card['image']) : null,
                        'title' => $card['title'] ?? '',
                        'description' => $card['description'] ?? '',
                        'url' => $card['url'] ?? '#',
                        'buttonText' => $card['url_text'] ?? 'Learn More',
                        'image_alt' => image_alt($card['image_alt'] ?? null, $card['title'] ?? 'Card image'),
                    ];
                })->toArray();
            @endphp
            <x-overlay-cards :columns="$section->data['columns'] ?? '3'" :background="$section->data['background'] ?? 'transparent'"
                :color="$section->data['color'] ?? '#000000'" :cards="$cards" />
        @elseif ($section->section_type === 'cards')
            @php
                $cards = collect($section->data['cards'] ?? [])->map(function ($card) {
                    return [
                        'image' => !empty($card['image']) ? asset('images/library/' . $card['image']) : '',
                        'icon' => !empty($card['icon']) ? asset('images/library/' . $card['icon']) : '',
                        'title' => $card['title'] ?? '',
                        'description' => $card['description'] ?? '',
                        'url' => $card['url'] ?? null,
                        'buttonText' => $card['url_text'] ?? null,
                        'url_target' => $card['url_target'] ?? null,
                        'image_alt' => image_alt($card['image_alt'] ?? null, $card['title'] ?? 'Card image'),
                        'icon_alt' => image_alt($card['icon_alt'] ?? null, ($card['title'] ?? 'Card') . ' icon'),
                    ];
                })->toArray();
            @endphp
            <x-cards :columns="$section->data['columns'] ?? '3'" :layout="$section->data['layout'] ?? 'layout-1'" :backgroundColor="$section->data['background'] ?? 'transparent'" :backgroundImage="!empty($section->data['image']) ? asset('images/library/' . $section->data['image']) : 'none'"
                :backgroundImageAlt="image_alt($section->data['image_alt'] ?? null, $section->data['title'] ?? 'Section background')"
                :color="$section->data['color'] ?? '#000000'" :alignment="$section->data['alignment'] ?? 'left'" :cards="$cards" />
        @elseif ($section->section_type === 'clients')
            @php
                $client = $section->clientDetails->map(function ($client) {
                    return [
                        'image' => (!empty($client->image) && is_string($client->image)) ? asset('images/clients/' . $client->image) : null,
                        'url' => $client->url ?? '#',
                        'target' => $client->open_new_tab ?? '',
                        'no_follow' => $client->no_follow ?? '',
                        'alt' => image_alt($client->image_alt, $client->title ?? 'Client logo'),
                    ];
                })->toArray();
            @endphp
            <x-clients :id="1":background="'#eeeeee'" :title="'Most of the Students are from'" :description="'Our students excel, securing positions at top companies, driving innovation, and achieving remarkable success.'" :logos="$client" />
        @elseif($section->section_type === 'list')
            @php
                $lists = collect($section->data['list_items'] ?? [])->map(function ($card) {
                    return [
                        'url' => $card['url'] ?? '#',
                        'name' => $card['url_text'] ?? 'Learn More'
                    ];
                })->toArray();

            @endphp
            <x-categories :background="$section->data['background'] ?? 'none'" :color="$section->data['color'] ?? 'inherit'" :title="$section->data['title'] ?? ''"
                :description="$section->data['description'] ?? ''" :categories="$lists" />
        @endif
    @endforeach

@endsection
