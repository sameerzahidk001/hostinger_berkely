@extends('layouts.app')
@section('title', $row->title)
@section('content')
<div class="container mx-auto px-4 pt-[100px] pb-16 max-w-3xl">
    @if(session('status'))<div class="mb-4 p-3 bg-green-50 border border-green-200">{{ session('status') }}</div>@endif
    <h1 class="text-2xl font-bold mb-2">{{ $row->title }}</h1>
    <p class="text-gray-600 mb-4">Status: <strong>{{ $row->status }}</strong>
        @if($row->doi) · DOI: {{ $row->doi }} @endif
    </p>
    <p class="mb-4 whitespace-pre-wrap">{{ $row->abstract }}</p>
    @if(in_array($row->status, ['draft', 'revision'], true))
        <a href="{{ route('journal.manuscripts.edit', $row->id) }}" class="inline-block bg-[#000435] text-white px-4 py-2 no-underline mb-4">Edit</a>
    @endif
    <h2 class="text-lg font-semibold mt-6 mb-2">Peer reviews</h2>
    <ul class="list-disc pl-5 text-sm text-gray-700">
        @forelse($row->reviews as $rev)
            <li>{{ $rev->status }}@if($rev->recommendation) — {{ $rev->recommendation }}@endif</li>
        @empty
            <li>No reviews yet.</li>
        @endforelse
    </ul>
    <p class="mt-6"><a href="{{ route('journal.manuscripts.index') }}">← My manuscripts</a></p>
</div>
@endsection
