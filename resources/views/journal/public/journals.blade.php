@extends('layouts.app')
@section('title', 'Journals')
@section('content')
<div class="container mx-auto px-4 pt-[100px] pb-16 max-w-5xl">
    <h1 class="text-3xl font-bold mb-3 text-[#000435]">Journals</h1>
    <p class="text-gray-600 mb-8">Browse subjects and published articles. PDF download requires sign in.</p>

    <h2 class="text-xl font-bold mb-3">Research journals</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-2 mb-10">
        @forelse($subjects as $subject)
            <a href="{{ route('journal.articles.search', ['subject_id' => $subject->id]) }}" class="text-[#1c5fb8] underline py-1">{{ $subject->name }}</a>
        @empty
            <a href="{{ route('journal.articles.search') }}" class="text-[#1c5fb8] underline">All articles</a>
        @endforelse
    </div>

    <h2 class="text-xl font-bold mb-4">Most recent</h2>
    @forelse($recent as $article)
        <a href="{{ route('journal.articles.show', $article->slug) }}" class="block border-b border-gray-200 py-3 no-underline">
            <span class="text-lg text-[#1c5fb8] font-semibold">{{ $article->title }}</span>
        </a>
    @empty
        <p class="text-gray-500">Published articles will appear here.</p>
    @endforelse
</div>
@endsection
