@props([
    'id' => null,
    'title' => '',
    'background' => 'transparent',
    'color' => '#000000',
    'width' => '30',
    'placeholder' => 'Search...',
    'searchDesign' => '0',
    'alignment' => 'center',
    'buttonBackground' => '#000000',
    'buttonColor' => '#ffffff',
    'buttonText' => 'Search',
])

@php
    // Border radius design
    switch ($searchDesign) {
        case '1':
            $radiusClass = 'search-radius-md';
            break;
        case '2':
            $radiusClass = 'search-radius-full';
            break;
        default:
            $radiusClass = 'search-radius-none';
    }

    // Alignment class mapping
    $alignmentClass = match($alignment) {
        'left' => 'align-left',
        'right' => 'align-right',
        default => 'align-center',
    };
@endphp

<section 
    id="section-{{ $id }}" 
    class="search-section {{ $alignmentClass }}" 
    style="background-color: {{ section_bg_color($background) }};"
>
    <div class="custom-container">
        <div class="custom-row">
            <div class="custom-col" style="max-width: {{ $width }}%;">
                @if(!empty($title))
                    <h2 class="search-title" style="color: {{ $color }};">{{ $title }}</h2>
                @endif

                <form action="{{ route('search') }}" method="GET" class="search-form {{ $radiusClass }}">
                    <input 
                        type="text" 
                        name="q" 
                        placeholder="{{ $placeholder }}" 
                        class="search-input" 
                        style="color: {{ $color }};"
                    >
                    <button 
                        type="submit" 
                        class="search-button"
                        style="background-color: {{ $buttonBackground }}; color: {{ $buttonColor }};"
                    >
                        {{ $buttonText }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<style>
/* =====================================
   Bootstrap-like Container Grid System
   ===================================== */

.custom-container {
    width: 100%;
    margin-right: auto;
    margin-left: auto;
    padding-right: 15px;
    padding-left: 15px;
}

/* Bootstrap-like max-width breakpoints */
@media (min-width: 576px) {
    .custom-container { max-width: 540px; }
}
@media (min-width: 768px) {
    .custom-container { max-width: 720px; }
}
@media (min-width: 992px) {
    .custom-container { max-width: 960px; }
}
@media (min-width: 1200px) {
    .custom-container { max-width: 1140px; }
}
@media (min-width: 1400px) {
    .custom-container { max-width: 1320px; }
}

/* Flex row and column mimic */
.custom-row {
    display: flex;
    flex-wrap: wrap;
}
.custom-col {
    width: 100%;
}

/* =====================================
   Search Section Styles
   ===================================== */

.search-section {
    width: 100%;
    padding: 60px 0;
}

/* ===== Title ===== */
.search-title {
    font-size: clamp(1.5rem, 2vw + 1rem, 2.5rem);
    font-weight: 600;
    margin-bottom: 20px;
    text-transform: capitalize;
}

/* ===== Search Form ===== */
.search-form {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    max-width: 100%;
    transition: all 0.3s ease;
}

/* Alignment behavior for the form */
.align-left .custom-row {
    justify-content: flex-start;
}
.align-center .custom-row {
    justify-content: center;
}
.align-right .custom-row {
    justify-content: flex-end;
}

/* ===== Input Styles ===== */
.search-input {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid #ccc;
    font-size: 16px;
    outline: none;
    transition: all 0.3s ease;
}

.search-input:focus {
    border-color: #007bff;
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
}

/* ===== Button Styles ===== */
.search-button {
    padding: 12px 24px;
    font-size: 16px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: capitalize;
}

.search-button:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

/* ===== Border Radius Variants ===== */
.search-radius-md .search-input,
.search-radius-md .search-button {
    border-radius: 6px;
}

.search-radius-full .search-input,
.search-radius-full .search-button {
    border-radius: 9999px;
}

.search-radius-none .search-input,
.search-radius-none .search-button {
    border-radius: 0;
}

/* ===== Responsive ===== */
@media (max-width: 768px) {
    .search-form {
        flex-direction: column;
    }
    .search-input,
    .search-button {
        width: 100%;
    }
}
</style>