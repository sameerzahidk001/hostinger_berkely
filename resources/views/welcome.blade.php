@extends('layouts.app')

@section('content')
    <div class="h-[72px]"></div>
    @foreach ($page->sections as $section)
        @if ($section->section_type === 'hero-banner')
            {{-- @dd($section->data) --}}
            <x-hero-banner 
                :id="$section->order + 1" 
                :background="media_url($section->data['image'] ?? null)"
                :title="$section->data['title'] ?? 'Default Title'"
                :subtitle="$section->data['subtitle'] ?? 'Default Subtitle'"
                :description="$section->data['description'] ?? ''" 
                :video="$section->data['video'] ?? ''" 
            />
        @elseif ($section->section_type === 'banner')
            {{-- @dd($section->data) --}}
            <x-banner 
                :id="$section->order+1"
                :breadcrumb="isset($section->data['breadcrumbs']) ? $breadcrumb : []"
                :title="$section->data['title'] ?? ''" 
                :subtitle="$section->data['subtitle'] ?? ''"
                :description="$section->data['description'] ?? ''"
                :backgroundColor="$section->data['background_color'] ?? ''"
                :image="media_url($section->data['image'] ?? null)"
                :color="$section->data['color'] ?? ''"
                :solidButtonUrl="$section->data['solid_button_url'] ?? ''"
                :solidUrlTarget="$section->data['solid_url_target'] ?? '1'"
                :solidButtonText="$section->data['solid_button_text'] ?? ''"
                :outlineButtonUrl="$section->data['outline_button_url'] ?? ''"
                :outlineUrlTarget="$section->data['outline_button_url'] ?? '1'"
                :outlineButtonText="$section->data['outline_button_text'] ?? ''"
            />
        @elseif($section->section_type === 'separator')
            <x-separator 
                :id="$section->order+1" 
                :color="$section->data['color']" 
                :height="$section->data['height']" />

        @elseif ($section->section_type === 'category')
            @php
                 $courses = $section->categoryDetails->courses->map(function ($course) {
                    return [
                        'title' => $course->title,
                        'slug' => $course->slug,
                        'thumbnail' => $course->thumbnail,
                    ];
                })->toArray();
            @endphp
            <x-schools 
                :id="$section->order+1"
                :background="$section->data['background_color'] ?? 'transparent'"
                :color="$section->data['color']"
                :columns="$section->data['columns'] ?? '3'"
                :title="$section->categoryDetails->name ?? 'Default Title'"
                :courses="$courses" />
        @elseif ($section->section_type === 'school-category')
            @php
                $schoolId = $section->data['school'];
                $categoryId = $section->data['category'];
                $category = App\Models\Category::find($categoryId);

                $courses = App\Models\Course::whereHas('categories', function($q) use ($categoryId) {
                        $q->where('categories.id', $categoryId);
                    })
                    ->whereHas('schools', function($q) use ($schoolId) {
                        $q->where('schools.id', $schoolId);
                    })
                    ->select('id', 'title', 'slug', 'thumbnail')
                    ->get()
                    ->toArray();
            @endphp
            <x-schools 
                :id="$section->order+1"
                :background="$section->data['background_color'] ?? 'transparent'"
                :color="$section->data['color']"
                :columns="$section->data['columns'] ?? '3'"
                :title="$category->name ?? 'Default Title'"
                :courses="$courses" />
        @elseif ($section->section_type === 'grid-cards')
            {{-- @dd($section->toArray()) --}}
            @php
                $cards = collect($section->data['cards'] ?? [])->map(function ($card) {
                    return [
                        'image' => media_url($card['image'] ?? null),
                        'title' => $card['title'] ?? '',
                        'description' => demote_page_headings($card['description'] ?? ''),
                        'url' => $card['url'] ?? null,
                        'url_text' => $card['url_text'] ?? null,
                        'url_target' => $card['url_target'] ?? '1',
                        'background' => $card['background'] ?? null,
                        'color' => $card['color'] ?? null,
                        'image_alt' => image_alt($card['image_alt'] ?? null, $card['title'] ?? 'Card image'),
                    ];
                })->toArray();
            @endphp
            <x-grid-cards 
                :id="$section->order+1"
                :title="$section->data['title'] ?? ''" 
                :subtitle="$section->data['subtitle'] ?? ''"
                :description="$section->data['description'] ?? ''"
                :backgroundColor="$section->data['background'] ?? ''"
                :backgroundImage="media_url($section->data['image'] ?? null)"
                :color="$section->data['color'] ?? ''"
                :layout="$section->data['layout'] ?? ''"
                :columns="$section->data['columns'] ?? 3"
                :cards="$cards"
            />
        @elseif ($section->section_type === 'title-section')
            <x-title-section 
                :id="$section->order+1"
                :background="$section->data['background'] ?? 'transparent'" 
                :color="$section->data['color'] ?? '#000000'" 
                :alignment="$section->data['alignment'] ?? 'left'" 
                :title="$section->data['title'] ?? ''" 
                :description="$section->data['description'] ?? ''"
                :link="$section->data['url'] ?? '#'" 
                :buttonUrlTarget="$section->data['url_target'] ?? '1'"
                :buttonText="$section->data['url_text'] ?? 'Learn More'" 
            />
        @elseif ($section->section_type === 'media-section')
            <x-media-section 
                :id="$section->order+1" 
                :backgroundColor="$section->data['background_color'] ?? 'transparent'"
                :contentBackground="$section->data['content_background'] ?? 'transparent'"
                :color="$section->data['color'] ?? '#000000'"
                :layout="$section->data['layout'] ?? 'layout-1'" 
                :direction="$section->data['image_placement']" 
                :image="media_url($section->data['image'] ?? null)"
                :icon="$section->data['icon'] ?? null" 
                :title="$section->data['title']" 
                :description="$section->data['description']"
                :link="$section->data['url']" 
                :buttonText="$section->data['url_text']" 
                :urlTarget="$section->data['url_target'] ?? '1'" />
        @elseif ($section->section_type === 'overlay-cards')
            @php
                $cards = collect($section->data['cards'] ?? [])->map(function ($card) {
                    return [
                        'image' => media_url($card['image'] ?? null),
                        'title' => $card['title'] ?? '',
                        'description' => demote_page_headings($card['description'] ?? ''),
                        'url' => $card['url'] ?? '#',
                        'url_target' => $card['url_target'] ?? '1',
                        'buttonText' => $card['url_text'] ?? 'Learn More',
                        'image_alt' => image_alt($card['image_alt'] ?? null, $card['title'] ?? 'Card image'),
                    ];
                })->toArray();
            @endphp
            <x-overlay-cards :id="$section->order+1" :columns="$section->data['columns'] ?? '3'" :background="$section->data['background'] ?? 'transparent'"
                :color="$section->data['color'] ?? '#000000'" :cards="$cards" />
        @elseif ($section->section_type === 'cards')
            @php
                $cards = collect($section->data['cards'] ?? [])->map(function ($card) {
                    return [
                        'image' => media_url($card['image'] ?? null),
                        'icon' => media_url($card['icon'] ?? null),
                        'title' => $card['title'] ?? '',
                        'description' => $card['description'] ?? '',
                        'url' => $card['url'] ?? null,
                        'url_target' => $card['url_target'] ?? '1',
                        'buttonText' => $card['url_text'] ?? null,
                    ];
                })->toArray();
            @endphp
            
            <x-cards 
                :id="$section->order + 1" 
                :columns="$section->data['columns'] ?? '3'" 
                :layout="$section->data['layout'] ?? 'layout-1'" 
                :backgroundColor="$section->data['background'] ?? 'transparent'" 
                :backgroundImage="media_url($section->data['image'] ?? null)"
                :color="$section->data['color'] ?? '#000000'" 
                :alignment="$section->data['alignment'] ?? 'left'" 
                :cards="$cards"
            />
        @elseif ($section->section_type === 'clients')
            @php
                $client = $section->clientDetails->map(function ($client) {
                    return [
                        'image' => (!empty($client->image) && is_string($client->image)) ? media_url('images/clients/' . $client->image) : null,
                        'url' => $client->url ?? '#',
                        'target' => $client->open_new_tab ?? '',
                        'no_follow' => $client->no_follow ?? '',
                    ];
                })->toArray();
            @endphp
            <x-clients :id="$section->order+1" :background="'#eeeeee'" :title="'Most of the Students are from'" :description="'Our students excel, securing positions at top companies, driving innovation, and achieving remarkable success.'" :logos="$client" />
        @elseif($section->section_type === 'list')
            {{-- @dd($section->data) --}}
            @php
                $lists = collect($section->data['list_items'] ?? [])->map(function ($card) {
                    return [
                        'url' => $card['url'] ?? 'javascript:void(0)',
                        'url_target' => $card['url_target'] ?? '1',
                        'name' => $card['url_text'] ?? 'Learn More',
                        'icon' => media_url($card['icon'] ?? null),
                    ];
                })->toArray();

            @endphp
            <x-categories 
                :id="$section->order+1" 
                :background="$section->data['background'] ?? 'transparent'" 
                :color="$section->data['color'] ?? 'inherit'"
                :layout="$section->data['layout'] ?? ''"
                :columns="$section->data['columns'] ?? ''" 
                :seperator="$section->data['seperator'] ?? ''"
                :title="$section->data['title'] ?? ''"
                :description="$section->data['description'] ?? ''" 
                :categories="$lists" />
        @elseif($section->section_type === 'certificate')
            {{-- @dd($section->data['certificate_name']) --}}
            <x-certificates 
                :id="$section->order+1" 
                :color="$section->data['color'] ?? ''" 
                :background="$section->data['background'] ?? 'transparent'" 
                :borderColor="$section->data['border_color'] ?? 'transparent'" 
                :image="media_url($section->data['image'] ?? null)"
                :title="$section->data['title'] ?? ''" 
                :certificateName="$section->data['certificate_name'] ?? ''" 
                :subtitle="$section->data['subtitle'] ?? ''" 
            />
        @elseif($section->section_type === 'contactus')
            {{-- @dd($section->data) --}}
            <x-contactus 
                :id="$section->order + 1" 
                :title="$section->data['title'] ?? ''" 
                :iframe="$section->data['iframe']" 
                :background="$section->data['background'] ?? 'transparent'"
                :color="$section->data['color'] ?? '#000000'"
            />
        @elseif($section->section_type === 'programmes')
            <x-programes 
                :id="$section->order + 1"
                :columns="$section->data['columns'] ?? ''"
                :background="$section->data['background'] ?? 'transparent'"
                :color="$section->data['color'] ?? '#000000'"
                :borderColor="$section->data['border_color'] ?? 'transparent'"
                :cardBackground="$section->data['card_background'] ?? '#ffffff'"
                :orderby="$section->data['orderby'] ?? ''"
                :pagination="$section->data['pagination'] ?? 0"
                :url_target="$section->data['url_target'] ?? ''"
                :title="$section->data['title'] ?? ''"
                :description="$section->data['description'] ?? ''"
            />
        @elseif($section->section_type === 'filter-courses')
            {{-- @dd($section->toArray()) --}}
            <x-filter-courses 
                :id="$section->order + 1"
                :color="$section->data['color'] ?? '#000000'"
                :background="$section->data['background'] ?? 'transparent'"
                :contentColor="$section->data['content_color'] ?? ''"
                :contentBackground="$section->data['content_background'] ?? 'transparent'"
                :contentBorderColor="$section->data['content_border_color'] ?? ''"
                :tabColor="$section->data['tab_color'] ?? ''"
                :tabBackground="$section->data['tab_background'] ?? 'transparent'"
                :tabBorderColor="$section->data['tab_border_color'] ?? ''"
                :activeTabColor="$section->data['active_tab_color'] ?? ''"
                :activeTabBackground="$section->data['active_tab_background'] ?? 'transparent'"
                :activeTabBorderColor="$section->data['active_tab_border_color'] ?? ''"
                :filterColor="$section->data['filter_color'] ?? ''"
                :filterBackground="$section->data['filter_background'] ?? 'transparent'"
                :filterBorderColor="$section->data['filter_border_color']"
                :activeFilterColor="$section->data['active_filter_color'] ?? ''"
                :activeFilterBackground="$section->data['active_filter_background'] ?? 'transparent'"
                :activeFilterBorderColor="$section->data['active_filter_border_color'] ?? ''"
                :orderby="$section->data['orderby'] ?? ''"
                :url_target="$section->data['url_target'] ?? ''"
                :title="$section->data['title'] ?? ''"
                :description="$section->data['description'] ?? ''"
            />
         @elseif($section->section_type === 'course-agendas')
             {{-- @dd($section->toArray()) --}}
            <x-course-agenda-component 
                :id="$section->order + 1"
                :color="$section->data['color'] ?? '#000000'"
                :background="$section->data['background'] ?? 'transparent'"
                :cardColor="$section->data['card_color'] ?? ''"
                :cardBackground="$section->data['card_background'] ?? 'transparent'"
                :cardBorderColor="$section->data['card_border_color']"
                :filterColor="$section->data['filter_color'] ?? ''"
                :filterBackground="$section->data['filter_background'] ?? 'transparent'"
                :filterBorderColor="$section->data['filter_border_color']"
                :activeFilterColor="$section->data['active_filter_color'] ?? ''"
                :activeFilterBackground="$section->data['active_filter_background'] ?? 'transparent'"
                :activeFilterBorderColor="$section->data['active_filter_border_color']"
                :orderby="$section->data['orderby'] ?? ''"
                :url_target="$section->data['url_target'] ?? ''"
                :title="$section->data['title'] ?? ''"
                :description="$section->data['description'] ?? ''"
            /> 
        @elseif($section->section_type === 'testimonials')
            <x-testimonials 
                :id="$section->order + 1"
                :color="$section->data['color'] ?? '#000000'"
                :background="$section->data['background'] ?? 'transparent'"
                :cardColor="$section->data['card_color'] ?? ''"
                :cardBackground="$section->data['card_background'] ?? 'transparent'"
                :cardBorderColor="$section->data['card_border_color'] ?? ''"
                :sidebarColor="$section->data['sidebar_color'] ?? ''"
                :sidebarBackground="$section->data['sidebar_background'] ?? 'transparent'"
                :sidebarBorderColor="$section->data['sidebar_border_color'] ?? ''"
                :paginationNumber="$section->data['pagination_num'] ?? 6"
                :columns="$section->data['columns'] ?? '3'"
                :testimonialsOrderby="$section->data['testimonialsOrderby'] ?? 'priority,asc'"
                :categoryOrderby="$section->data['categoryOrderby'] ?? 'id,asc'"
                :urlTarget="$section->data['url_target'] ?? ''"
                :category="request('category')"
                :course="request('course')"
                :testimonialId="request('testimonial-id')"
                :search="request('search')"
                :sort="request('sort')"
            />
        @elseif ($section->section_type === 'content')
            <x-content 
                :id="$section->order + 1" 
                :background="$section->data['background'] ?? 'transparent'"
                :color="$section->data['color'] ?? '#000000'"
                :description="$section->data['description'] ?? ''"
            />
        @elseif($section->section_type === 'career')
            {{-- @dd($section->toArray()) --}}
            @php
                $cards = collect($section->data['cards'] ?? [])->map(function ($card) {
                    return [
                        'url' => $card['url'] ?? 'javascript:void(0)',
                        'urlTarget' => $card['url_target'] ?? '1',
                        'urlText' => $card['url_text'] ?? '',
                        'content' => $card['content'] ?? '',
                        'tags' => $card['tags'] ?? '',
                    ];
                })->toArray();
            @endphp
            <x-careers 
                :id="$section->order+1"
                :title="$section->data['title'] ?? ''"
                :description="$section->data['description'] ?? ''"
                :background="$section->data['background'] ?? 'transparent'"
                :color="$section->data['color'] ?? 'inherit'"
                :cardBackground="$section->data['card_background'] ?? 'transparent'"
                :cardColor="$section->data['card_color'] ?? 'inherit'"
                :cardBorderColor="$section->data['card_border_color'] ?? 'inherit'"
                :cardHoverBackground="$section->data['card_hover_background'] ?? 'transparent'"
                :cardHoverColor="$section->data['card_hover_color'] ?? 'inherit'"
                :cardHoverBorderColor="$section->data['card_hover_border_color'] ?? 'inherit'"
                :cards="$cards" />
        @elseif($section->section_type === 'search-bar')
            <x-search-bar 
                :id="$section->order + 1"
                :title="$section->data['title'] ?? ''"
                :background="$section->data['background'] ?? 'transparent'"
                :color="$section->data['color'] ?? '#000000'"
                :width="$section->data['width'] ?? '30'"
                :placeholder="$section->data['placeholder'] ?? 'Search...'"
                :searchDesign="$section->data['search_design'] ?? '0'"
                :alignment="$section->data['alignment'] ?? 'center'"
                :buttonBackground="$section->data['button_background'] ?? '#000435'"
                :buttonColor="$section->data['button_color'] ?? '#ffffff'"
                :buttonText="$section->data['button_text'] ?? 'Search'"
            />
        @elseif($section->section_type === 'search-section' || request()->routeIs('search'))
            <x-search-section 
                :id="$section->order + 1"
                :title="$section->data['title'] ?? 'Find Your Course'"
                :description="$section->data['description'] ?? ''"
                :background="$section->data['background'] ?? 'transparent'"
                :color="$section->data['color'] ?? '#000000'"
                :columns="$section->data['columns'] ?? '4'"
                :courses="$results" />
        @elseif($section->section_type === 'instructors')
            
            <x-instructors 
                :id="$section->order + 1"
                :background="$section->data['background'] ?? 'transparent'"
                :color="$section->data['color'] ?? '#000000'"
                :cardBackground="$section->data['card_background'] ?? '#ffffff'"
                :cardColor="$section->data['card_color'] ?? '#000000'"
                :orderby="$section->data['orderby'] ?? ''"
                :pagination="$section->data['pagination'] ?? 0"
                :title="$section->data['title'] ?? ''"
                :description="$section->data['description'] ?? ''"
            />
        @endif
    @endforeach

@endsection