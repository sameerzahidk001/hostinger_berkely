@extends('layouts.app')
@section('title', 'Journal dashboard')
@section('content')
@php
    use App\Services\JournalWorkflow;
@endphp
<div class="container mx-auto px-4 pt-[100px] pb-16 max-w-6xl">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-[#000435] mb-1">Journal workspace</h1>
            <p class="text-gray-600 mb-0">
                {{ $user->name ?? 'User' }}
                @if(!empty($roleNames))
                    <span class="text-sm text-gray-500">· {{ collect($roleNames)->map(fn ($r) => role_display_name($r))->implode(', ') }}</span>
                @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($isAuthor)
                <a href="{{ route('journal.manuscripts.create') }}" class="bg-[#000435] text-white px-4 py-2 no-underline text-sm">Submit manuscript</a>
            @endif
            <a href="{{ route('journal.articles.search') }}" class="border border-[#000435] text-[#000435] px-4 py-2 no-underline text-sm">Browse articles</a>
            <a href="{{ url('/journal') }}" class="border border-gray-300 text-gray-700 px-4 py-2 no-underline text-sm">Public site</a>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 p-3 bg-green-50 border border-green-200 text-green-800">{{ session('status') }}</div>
    @endif

    <section class="mb-10">
        <h2 class="text-lg font-semibold text-[#000435] mb-3">Publication pipeline</h2>
        <p class="text-sm text-gray-500 mb-4">Standard journal flow: draft → submit → editor screen → peer review → revision / accept / reject → publish.</p>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            @foreach(\App\Services\JournalWorkflow::PIPELINE as $status)
                <div class="border border-gray-200 bg-white p-3">
                    <div class="text-2xl font-bold text-[#000435]">{{ $pipeline[$status] ?? 0 }}</div>
                    <div class="text-xs text-gray-600 leading-snug">{{ JournalWorkflow::statusLabel($status) }}</div>
                </div>
            @endforeach
        </div>
    </section>

    <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-4 mb-10">
        <div class="border border-gray-200 p-4 bg-white">
            <div class="text-2xl font-semibold">{{ $stats['my_manuscripts'] }}</div>
            <div class="text-sm text-gray-600">My submissions</div>
        </div>
        <div class="border border-gray-200 p-4 bg-white">
            <div class="text-2xl font-semibold">{{ $stats['pending_reviews'] }}</div>
            <div class="text-sm text-gray-600">Reviews to do</div>
        </div>
        <div class="border border-gray-200 p-4 bg-white">
            <div class="text-2xl font-semibold">{{ $stats['editorial_queue'] }}</div>
            <div class="text-sm text-gray-600">Editorial queue</div>
        </div>
        <div class="border border-gray-200 p-4 bg-white">
            <div class="text-2xl font-semibold">{{ $stats['published'] }}</div>
            <div class="text-sm text-gray-600">Published articles</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-8">
        @if($isAuthor)
        <section class="border border-gray-200 bg-white p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-[#000435] mb-0">Author — my manuscripts</h2>
                <a href="{{ route('journal.manuscripts.index') }}" class="text-sm text-[#1c5fb8] underline">All</a>
            </div>
            <ul class="m-0 p-0 list-none divide-y divide-gray-100">
                @forelse($myManuscripts as $ms)
                    <li class="py-3 flex gap-3 justify-between items-start">
                        <div class="min-w-0">
                            <a href="{{ route('journal.manuscripts.show', $ms->id) }}" class="font-medium text-[#000435] hover:underline">{{ $ms->title }}</a>
                            <div class="text-xs text-gray-500 mt-1">{{ JournalWorkflow::statusLabel($ms->status) }}</div>
                        </div>
                        @if(in_array($ms->status, ['draft', 'revision'], true))
                            <a href="{{ route('journal.manuscripts.edit', $ms->id) }}" class="text-sm text-[#1c5fb8] shrink-0">Edit</a>
                        @endif
                    </li>
                @empty
                    <li class="py-4 text-gray-500 text-sm">No submissions yet. Start with Submit manuscript.</li>
                @endforelse
            </ul>
        </section>
        @endif

        @if($isReviewer)
        <section class="border border-gray-200 bg-white p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-[#000435] mb-0">Reviewer — assignments</h2>
                <a href="{{ route('journal.reviews.index') }}" class="text-sm text-[#1c5fb8] underline">All</a>
            </div>
            <ul class="m-0 p-0 list-none divide-y divide-gray-100">
                @forelse($pendingReviews as $rev)
                    <li class="py-3 flex gap-3 justify-between items-start">
                        <div class="min-w-0">
                            <a href="{{ route('journal.reviews.show', $rev->id) }}" class="font-medium text-[#000435] hover:underline">{{ optional($rev->manuscript)->title ?: 'Manuscript' }}</a>
                            <div class="text-xs text-gray-500 mt-1">{{ ucfirst(str_replace('_', ' ', $rev->status)) }}</div>
                        </div>
                        <a href="{{ route('journal.reviews.show', $rev->id) }}" class="text-sm text-[#1c5fb8] shrink-0">Open</a>
                    </li>
                @empty
                    <li class="py-4 text-gray-500 text-sm">No pending review invitations.</li>
                @endforelse
            </ul>
        </section>
        @endif

        @if($isEditor || $isStaff)
        <section class="border border-gray-200 bg-white p-5 lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-[#000435] mb-0">Editorial — active queue</h2>
                <a href="{{ route('journal.editorial.index') }}" class="text-sm text-[#1c5fb8] underline">Full queue</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-2 pr-3 font-medium">Title</th>
                            <th class="py-2 pr-3 font-medium">Author</th>
                            <th class="py-2 pr-3 font-medium">Stage</th>
                            <th class="py-2 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($editorialQueue as $row)
                        <tr class="border-b border-gray-100">
                            <td class="py-2.5 pr-3">{{ $row->title }}</td>
                            <td class="py-2.5 pr-3">{{ optional($row->author)->name ?: '—' }}</td>
                            <td class="py-2.5 pr-3">{{ JournalWorkflow::statusLabel($row->status) }}</td>
                            <td class="py-2.5"><a href="{{ route('journal.editorial.show', $row->id) }}" class="text-[#1c5fb8] underline">Manage</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-gray-500">No manuscripts in the editorial queue.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        @endif

        <section class="border border-gray-200 bg-white p-5 {{ ($isEditor || $isStaff) ? '' : 'lg:col-span-2' }}">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-[#000435] mb-0">Published articles</h2>
                <a href="{{ route('journal.articles.search') }}" class="text-sm text-[#1c5fb8] underline">Search</a>
            </div>
            <ul class="m-0 p-0 list-none divide-y divide-gray-100">
                @forelse($recentPublished as $article)
                    <li class="py-3">
                        <a href="{{ route('journal.articles.show', $article->slug) }}" class="font-medium text-[#1c5fb8] hover:underline">{{ $article->title }}</a>
                        <div class="text-xs text-gray-500 mt-1">{{ optional($article->published_at)->format('Y-m-d') }}</div>
                    </li>
                @empty
                    <li class="py-4 text-gray-500 text-sm">No published articles yet.</li>
                @endforelse
            </ul>
            @if($isStaff)
                <div class="mt-4 pt-4 border-t border-gray-100 text-sm text-gray-600 flex flex-wrap gap-4">
                    <a href="{{ route('journal.policies') }}" class="text-[#1c5fb8] underline">Policies ({{ $stats['policies_published'] }})</a>
                    <a href="{{ route('journal.news.index') }}" class="text-[#1c5fb8] underline">News &amp; events ({{ $stats['news_published'] }})</a>
                    <a href="{{ route('journal.publish') }}" class="text-[#1c5fb8] underline">Publish page</a>
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
