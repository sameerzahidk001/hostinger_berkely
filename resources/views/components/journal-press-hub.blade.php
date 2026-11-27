@php
    use App\Models\JournalManuscript;
    use App\Models\JournalSubject;

    $recent = collect();
    $subjects = collect();
    try {
        if (class_exists(JournalManuscript::class)) {
            $recent = JournalManuscript::query()
                ->with('subject')
                ->where('status', 'published')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(6)
                ->get();
        }
        if (class_exists(JournalSubject::class)) {
            $subjects = JournalSubject::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(40)
                ->get();
        }
    } catch (\Throwable $e) {
        $recent = collect();
        $subjects = collect();
    }

    $aboutCards = [
        [
            'title' => 'Science that inspires',
            'body' => 'We publish research across disciplines to advance discovery, drive innovation, and help solve our greatest challenges.',
            'href' => url('/journal/about'),
        ],
        [
            'title' => 'The right home for your research',
            'body' => 'We support authors and streamline publication so you can find the right home for your work.',
            'href' => url('/journal/for-authors'),
        ],
        [
            'title' => 'Real-world impact',
            'body' => 'Meeting the world’s challenges means science that builds confidence, shares ideas, and gives researchers a voice.',
            'href' => url('/journal/about'),
        ],
    ];

    $journalNav = [
        ['label' => 'Journal Home', 'href' => url('/journal')],
        ['label' => 'Table of Contents', 'href' => route('journal.articles.search')],
        ['label' => 'Online Now', 'href' => route('journal.articles.search', ['q' => ''])],
        ['label' => 'Archive', 'href' => route('journal.articles.search')],
        ['label' => 'Submit', 'href' => auth()->check() ? route('journal.manuscripts.create') : path_register_url('journal')],
    ];
@endphp

<section class="journal-press-hub bg-[#f5f5f5] px-4 md:px-8 min-[1200px]:px-[72px] py-10 md:py-14">
    <div class="max-w-6xl mx-auto">
        <div class="bg-white border border-gray-200 rounded-sm p-4 md:p-6 mb-8 flex flex-col sm:flex-row gap-4 sm:gap-6">
            <div class="w-full sm:w-36 h-36 bg-[#000435] text-white flex items-center justify-center shrink-0 rounded-sm">
                <span class="font-bold text-center px-2 leading-tight">Berkeley<br>Press</span>
            </div>
            <div class="min-w-0">
                <h2 class="text-xl md:text-2xl font-bold text-[#000435] mb-3">Current journal</h2>
                <ul class="m-0 p-0 list-none flex flex-col gap-2">
                    @foreach($journalNav as $item)
                        <li>
                            <a href="{{ $item['href'] }}" class="text-[#1c5fb8] font-medium hover:underline">{{ $item['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
                <p class="text-xs text-gray-500 mt-3 mb-0">Browse freely. PDF downloads require sign in / register.</p>
            </div>
        </div>

        <h2 class="text-2xl md:text-3xl font-bold text-[#222] mb-5">Most recent</h2>
        <div class="flex flex-col gap-4 mb-12">
            @forelse($recent as $article)
                <a href="{{ route('journal.articles.show', $article->slug) }}"
                   class="bg-white border border-gray-200 rounded-sm p-4 md:p-5 flex gap-4 no-underline hover:shadow-md transition-shadow">
                    <div class="min-w-0 flex-1">
                        <h3 class="text-lg md:text-xl font-semibold text-[#1c5fb8] mb-1 leading-snug">{{ $article->title }}</h3>
                        <p class="text-sm text-gray-500 italic mb-0">{{ optional($article->subject)->name ?: 'Berkeley Press' }}</p>
                    </div>
                    <div class="hidden sm:flex w-24 h-24 bg-[#eef2f7] text-[#000435] items-center justify-center shrink-0 text-xs font-bold text-center px-2">
                        Article
                    </div>
                </a>
            @empty
                <div class="bg-white border border-gray-200 rounded-sm p-5 text-gray-600">
                    Published articles will appear here. Until then, explore About and For Authors, or
                    <a href="{{ route('journal.articles.search') }}" class="text-[#1c5fb8] underline">search journals</a>.
                </div>
            @endforelse
        </div>

        <div class="grid md:grid-cols-3 gap-6 mb-12">
            @foreach($aboutCards as $card)
                <div class="bg-white border border-gray-200 rounded-sm p-5 md:p-6">
                    <h3 class="text-xl font-bold text-[#222] mb-3">{{ $card['title'] }}</h3>
                    <p class="text-gray-600 text-[15px] leading-relaxed mb-4">{{ $card['body'] }}</p>
                    <a href="{{ $card['href'] }}" class="inline-flex items-center gap-2 text-[#1c5fb8] font-semibold no-underline">
                        <span class="inline-flex w-6 h-6 items-center justify-center bg-[#1c5fb8] text-white text-sm">›</span>
                        Learn more
                    </a>
                </div>
            @endforeach
        </div>

        <div class="bg-white border border-gray-200 rounded-sm p-5 md:p-8">
            <h2 class="text-2xl md:text-3xl font-bold text-[#222] mb-6">Explore journals</h2>
            <div class="text-center mb-6">
                <div class="inline-flex w-12 h-14 border-2 border-gray-400 rounded-sm items-center justify-center text-gray-500 mb-2">☰</div>
                <div class="font-bold text-[#333]">Research journals</div>
            </div>
            @if($subjects->isNotEmpty())
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-2">
                    @foreach($subjects as $subject)
                        <a href="{{ route('journal.articles.search', ['subject_id' => $subject->id]) }}"
                           class="text-[#1c5fb8] underline py-1">{{ $subject->name }}</a>
                    @endforeach
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-2">
                    @foreach(['Life Sciences', 'Physical Sciences', 'Medical Research', 'Environmental Science', 'Engineering', 'Social Sciences'] as $label)
                        <a href="{{ route('journal.articles.search', ['q' => $label]) }}" class="text-[#1c5fb8] underline py-1">{{ $label }}</a>
                    @endforeach
                </div>
            @endif
            <p class="text-sm text-gray-500 mt-6 mb-0">
                All journals are visible. To download full PDFs,
                <a href="{{ path_register_url('journal') }}" class="text-[#1c5fb8] underline">create an account</a>
                or <a href="{{ path_login_url('journal') }}" class="text-[#1c5fb8] underline">sign in</a>.
            </p>
        </div>
    </div>
</section>
