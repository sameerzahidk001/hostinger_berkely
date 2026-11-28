@extends('layouts.app')
@section('title', 'Editorial — ' . $row->title)
@section('content')
@php use App\Services\JournalWorkflow; @endphp
<div class="container mx-auto px-4 pt-[100px] pb-16 max-w-3xl">
    @if(session('status'))<div class="mb-4 p-3 bg-green-50 border">{{ session('status') }}</div>@endif
    @if(session('fail'))<div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-800">{{ session('fail') }}</div>@endif

    <p class="text-sm mb-2"><a href="{{ route('journal.editorial.index') }}" class="text-[#1c5fb8] underline">← Queue</a></p>
    <h1 class="text-2xl font-bold mb-2">{{ $row->title }}</h1>
    <p class="text-gray-600 mb-2">
        <strong>{{ JournalWorkflow::statusLabel($row->status) }}</strong>
        · Author: {{ optional($row->author)->name }}
        · Handling editor: {{ optional($row->editor)->name ?: 'Unassigned' }}
    </p>
    <p class="text-sm text-gray-500 mb-4">{{ JournalWorkflow::statusHelp($row->status) }}</p>

    <div class="flex flex-wrap gap-2 mb-6">
        @foreach($timeline as $step)
            <span class="text-xs px-2 py-1 border {{ $step['current'] ? 'bg-[#000435] text-white border-[#000435]' : ($step['done'] ? 'bg-green-50 border-green-200 text-green-800' : 'bg-gray-50 border-gray-200 text-gray-500') }}">
                {{ $step['label'] }}
            </span>
        @endforeach
    </div>

    <h2 class="font-semibold mb-2">Abstract</h2>
    <p class="mb-6 whitespace-pre-wrap">{{ $row->abstract }}</p>

    <div class="flex flex-wrap gap-3 mb-8">
        <form method="POST" action="{{ route('journal.editorial.update', $row->id) }}">
            @csrf @method('PUT')
            <button name="action" value="assign_self" class="bg-gray-200 px-4 py-2">Assign me as editor</button>
        </form>
        @if($row->status === 'submitted')
            <form method="POST" action="{{ route('journal.editorial.update', $row->id) }}" onsubmit="return confirm('Desk-reject this submission?');">
                @csrf @method('PUT')
                <input type="hidden" name="action" value="screen_reject">
                <input type="hidden" name="decision_notes" value="Desk rejected after editorial screening.">
                <button class="border border-red-300 text-red-700 px-4 py-2">Desk reject</button>
            </form>
        @endif
    </div>

    @if(! in_array($row->status, ['published', 'rejected', 'withdrawn'], true))
        <h2 class="font-semibold mb-2">1. Invite peer reviewers</h2>
        <form method="POST" action="{{ route('journal.editorial.update', $row->id) }}" class="flex flex-col sm:flex-row gap-2 mb-8">
            @csrf @method('PUT')
            <input type="hidden" name="action" value="invite_reviewer">
            <select name="reviewer_user_id" class="border p-2 flex-1" required>
                <option value="">Select reviewer</option>
                @foreach($reviewers as $r)
                    <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->email }})</option>
                @endforeach
            </select>
            <button class="bg-[#000435] text-white px-4 py-2">Invite</button>
        </form>
    @endif

    <h2 class="font-semibold mb-2">Reviews received</h2>
    <ul class="mb-8 list-none m-0 p-0 space-y-2 text-sm">
        @forelse($row->reviews as $rev)
            <li class="border border-gray-200 p-3">
                <div class="font-medium">{{ optional($rev->reviewer)->name }} — {{ $rev->status }}@if($rev->recommendation) ({{ $rev->recommendation }})@endif</div>
                @if($rev->comments_to_editor)
                    <p class="mt-2 mb-0 text-gray-600 whitespace-pre-wrap"><span class="font-medium">To editor:</span> {{ $rev->comments_to_editor }}</p>
                @endif
                @if($rev->comments_to_author)
                    <p class="mt-2 mb-0 text-gray-600 whitespace-pre-wrap"><span class="font-medium">To author:</span> {{ $rev->comments_to_author }}</p>
                @endif
            </li>
        @empty
            <li class="text-gray-500">None yet.</li>
        @endforelse
    </ul>

    @if(count($decisions))
        <h2 class="font-semibold mb-2">2. Editorial decision</h2>
        <form method="POST" action="{{ route('journal.editorial.update', $row->id) }}" class="space-y-3 border border-gray-200 p-4">
            @csrf @method('PUT')
            <input type="hidden" name="action" value="decide">
            <div>
                <label class="block text-sm mb-1">Decision</label>
                <select name="status" class="w-full border p-2" required>
                    @foreach($decisions as $d)
                        <option value="{{ $d }}">{{ JournalWorkflow::statusLabel($d === 'under_review' ? 'under_review' : $d) }}{{ $d === 'under_review' ? ' (send back to peer review)' : '' }}</option>
                    @endforeach
                </select>
            </div>
            @if(in_array('published', $decisions, true))
                <div>
                    <label class="block text-sm mb-1">Issue (optional)</label>
                    <select name="issue_id" class="w-full border p-2">
                        <option value="">— None —</option>
                        @foreach($issues as $issue)
                            <option value="{{ $issue->id }}" @selected((int)$row->issue_id === (int)$issue->id)>{{ $issue->title }} (Vol {{ $issue->volume }} / {{ $issue->number }})</option>
                        @endforeach
                    </select>
                </div>
                <input name="doi" class="w-full border p-2" placeholder="DOI (only if registered)" value="{{ $row->doi }}">
            @endif
            <textarea name="decision_notes" rows="3" class="w-full border p-2" placeholder="Decision notes (visible to author for revision/reject)">{{ $row->decision_notes }}</textarea>
            <button class="bg-[#000435] text-white px-4 py-2">Save decision</button>
        </form>
    @elseif($row->status === 'published')
        <p class="text-green-700 font-medium">This article is published.
            <a href="{{ route('journal.articles.show', $row->slug) }}" class="underline">View public page</a>
        </p>
    @endif
</div>
@endsection
