@php
    $women = global_women_card();
@endphp
<x-media-section
    :id="'global-women'"
    :backgroundColor="$women['background_color'] ?? 'transparent'"
    :contentBackground="$women['content_background'] ?? 'transparent'"
    :color="$women['color'] ?? '#000000'"
    :layout="$women['layout'] ?? 'layout-1'"
    :direction="$women['image_placement'] ?? 'left'"
    :image="media_url($women['image'] ?? null)"
    :icon="$women['icon'] ?? null"
    :title="$women['title'] ?? 'BERKELEY & MS VOICE GLOBAL WOMEN'"
    :description="$women['description'] ?? ''"
    :altText="'Berkeley and MS Voice Global Women'"
    :link="$women['url'] ?? 'https://women.berkeleyme.com/'"
    :buttonText="$women['url_text'] ?? 'Learn More'"
    :urlTarget="$women['url_target'] ?? '0'"
/>
