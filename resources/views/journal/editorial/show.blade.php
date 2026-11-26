@extends('layouts.app')
@section('title', 'Editorial — ' . $row->title)
@section('content')
<div class="container mx-auto px-4 pt-[100px] pb-16 max-w-3xl">
    @if(session('status'))<div class="mb-4 p-3 bg-green-50 border">{{ session('status') }}</div>@endif
    <h1 class="text-2xl font-bold mb-2">{{ $row->title }}</h1>
    <p class="text-gray-600 mb-4">Status: {{ $row->status }} · Author: {{ optional($row->author)->name }}</p>
    <p class="mb-6 whitespace-pre-wrap">{{ $row->abstract }}</p>

    <form method="POST" action="{{ route('journal.editorial.update', $row->id) }}" class="mb-6">
        @csrf @method('PUT')
        <button name="action" value="assign_self" class="bg-gray-200 px-4 py-2">Assign me as editor</button>
    </form>

    <h2 class="font-semibold mb-2">Invite reviewer</h2>
    <form method="POST" action="{{ route('journal.editorial.update', $row->id) }}" class="flex gap-2 mb-8">
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

    <h2 class="font-semibold mb-2">Reviews</h2>
    <ul class="mb-8 list-disc pl-5 text-sm">
        @forelse($row->reviews as $rev)
            <li>{{ optional($rev->reviewer)->name }} — {{ $rev->status }}@if($rev->recommendation) ({{ $rev->recommendation }})@endif</li>
        @empty
            <li>None yet.</li>
        @endforelse
    </ul>

    <h2 class="font-semibold mb-2">Decision</h2>
    <form method="POST" action="{{ route('journal.editorial.update', $row->id) }}" class="space-y-3">
        @csrf @method('PUT')
        <input type="hidden" name="action" value="decide">
        <select name="status" class="w-full border p-2" required>
            <option value="revision">Request revision</option>
            <option value="accepted">Accept</option>
            <option value="rejected">Reject</option>
            <option value="published">Publish</option>
        </select>
        <input name="doi" class="w-full border p-2" placeholder="DOI (only if really registered)" value="{{ $row->doi }}">
        <textarea name="decision_notes" rows="3" class="w-full border p-2" placeholder="Decision notes">{{ $row->decision_notes }}</textarea>
        <button class="bg-[#000435] text-white px-4 py-2">Save decision</button>
    </form>
</div>
@endsection
