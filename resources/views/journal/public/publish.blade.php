@extends('layouts.app')
@section('title', 'Publish')
@section('content')
<div class="container mx-auto px-4 pt-[100px] pb-16 max-w-4xl">
    <h1 class="text-3xl font-bold mb-3 text-[#000435]">Publish</h1>
    <p class="text-gray-600 mb-6">
        Submit a manuscript for peer review. Published articles are listed below and on
        <a href="{{ route('journal.articles.search') }}" class="text-[#1c5fb8] underline">Journals</a>.
    </p>
    <div class="mb-10">
        <a href="{{ $submitUrl }}" class="inline-block bg-[#000435] text-white px-5 py-3 no-underline font-semibold">
            {{ $canSubmit ? 'Submit manuscript' : 'Register / sign in to submit' }}
        </a>
        @if($canSubmit)
            <a href="{{ route('journal.manuscripts.index') }}" class="ml-3 text-[#1c5fb8] underline">My manuscripts</a>
        @endif
    </div>

    <h2 class="text-2xl font-bold mb-4">Published articles</h2>
    @forelse($published as $row)
        <article class="border-b border-gray-200 py-4">
            <h3 class="text-lg font-semibold">
                <a href="{{ route('journal.articles.show', $row->slug) }}" class="text-[#1c5fb8]">{{ $row->title }}</a>
            </h3>
            <p class="text-sm text-gray-500 mb-0">{{ optional($row->published_at)->format('Y-m-d') }}</p>
        </article>
    @empty
        <p class="text-gray-500">No published articles yet. Accepted manuscripts appear here after editorial publish.</p>
    @endforelse
    <div class="mt-6">{{ $published->links() }}</div>
</div>
@endsection
