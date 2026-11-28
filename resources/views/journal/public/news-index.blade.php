@extends('layouts.app')
@section('title', 'News & events')
@section('content')
<div class="container mx-auto px-4 pt-[100px] pb-16 max-w-4xl">
    <h1 class="text-3xl font-bold mb-6 text-[#000435]">News &amp; events</h1>
    @forelse($rows as $row)
        <a href="{{ route('journal.news.show', $row->slug) }}"
           class="block bg-white border border-gray-200 rounded-sm p-5 mb-4 no-underline hover:shadow-md">
            <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">{{ $row->type }}</div>
            <h2 class="text-xl font-semibold text-[#1c5fb8] mb-1">{{ $row->title }}</h2>
            <p class="text-sm text-gray-500 mb-0">{{ optional($row->published_at)->format('Y-m-d') }}</p>
        </a>
    @empty
        <p class="text-gray-500">No published news or events yet.</p>
    @endforelse
    <div class="mt-6">{{ $rows->links() }}</div>
</div>
@endsection
