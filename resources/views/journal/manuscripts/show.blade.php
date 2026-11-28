@extends('layouts.app')
@section('title', $row->title)
@section('content')
@php use App\Services\JournalWorkflow; @endphp
<div class="container mx-auto px-4 pt-[100px] pb-16 max-w-3xl">
    @if(session('status'))<div class="mb-4 p-3 bg-green-50 border border-green-200">{{ session('status') }}</div>@endif

    <p class="text-sm mb-2"><a href="{{ route('journal.dashboard') }}" class="text-[#1c5fb8] underline">Dashboard</a> · <a href="{{ route('journal.manuscripts.index') }}" class="text-[#1c5fb8] underline">My manuscripts</a></p>
    <h1 class="text-2xl font-bold mb-2">{{ $row->title }}</h1>
    <p class="text-gray-600 mb-2">
        <strong>{{ JournalWorkflow::statusLabel($row->status) }}</strong>
        @if($row->doi) · DOI {{ $row->doi }} @endif
        @if($row->published_at) · Published {{ $row->published_at->format('Y-m-d') }} @endif
    </p>
    <p class="text-sm text-gray-500 mb-6">{{ JournalWorkflow::statusHelp($row->status) }}</p>

    <div class="flex flex-wrap gap-2 mb-8">
        @foreach($timeline as $step)
            <span class="text-xs px-2 py-1 border {{ $step['current'] ? 'bg-[#000435] text-white border-[#000435]' : ($step['done'] ? 'bg-green-50 border-green-200 text-green-800' : 'bg-gray-50 border-gray-200 text-gray-500') }}">
                {{ $step['label'] }}
            </span>
        @endforeach
    </div>

    <h2 class="font-semibold mb-2">Abstract</h2>
    <p class="mb-6 whitespace-pre-wrap">{{ $row->abstract }}</p>

    @if($row->decision_notes && in_array($row->status, ['revision', 'rejected', 'accepted', 'published'], true))
        <div class="mb-6 p-4 border border-amber-200 bg-amber-50 text-sm">
            <strong>Editorial notes</strong>
            <p class="mt-2 mb-0 whitespace-pre-wrap">{{ $row->decision_notes }}</p>
        </div>
    @endif

    @if($isOwner && in_array($row->status, ['draft', 'revision'], true))
        <a href="{{ route('journal.manuscripts.edit', $row->id) }}" class="inline-block bg-[#000435] text-white px-4 py-2 no-underline mb-3">{{ $row->status === 'revision' ? 'Revise & resubmit' : 'Edit draft' }}</a>
    @endif

    @if($isOwner && in_array($row->status, ['draft', 'submitted', 'revision'], true))
        <form method="POST" action="{{ route('journal.manuscripts.withdraw', $row->id) }}" class="inline-block ml-2" onsubmit="return confirm('Withdraw this manuscript?');">
            @csrf
            <button class="border border-gray-300 px-4 py-2 text-sm">Withdraw</button>
        </form>
    @endif

    @if($row->status === 'published')
        <p class="mt-4"><a href="{{ route('journal.articles.show', $row->slug) }}" class="text-[#1c5fb8] underline">View published article</a></p>
    @endif

    <h2 class="text-lg font-semibold mt-8 mb-2">Peer-review feedback</h2>
    <ul class="list-none m-0 p-0 space-y-3">
        @forelse($row->reviews as $rev)
            <li class="border border-gray-200 p-3 text-sm">
                <div class="font-medium">{{ ucfirst(str_replace('_', ' ', (string) $rev->recommendation)) ?: 'Completed' }}</div>
                @if($rev->comments_to_author)
                    <p class="mt-2 mb-0 whitespace-pre-wrap text-gray-700">{{ $rev->comments_to_author }}</p>
                @endif
            </li>
        @empty
            <li class="text-sm text-gray-500">No completed reviews yet.</li>
        @endforelse
    </ul>
</div>
@endsection
