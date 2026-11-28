@extends('layouts.app')
@section('title', 'Review')
@section('content')
<div class="container mx-auto px-4 pt-[100px] pb-16 max-w-3xl">
    @if(session('status'))<div class="mb-4 p-3 bg-green-50 border">{{ session('status') }}</div>@endif
    <p class="text-sm mb-2"><a href="{{ route('journal.reviews.index') }}" class="text-[#1c5fb8] underline">← Assignments</a></p>
    <h1 class="text-2xl font-bold mb-2">{{ optional($row->manuscript)->title }}</h1>
    <p class="text-gray-600 mb-4">Review status: <strong>{{ ucfirst(str_replace('_', ' ', $row->status)) }}</strong>
        @if(optional($row->manuscript)->subject) · {{ $row->manuscript->subject->name }} @endif
    </p>
    <h2 class="font-semibold mb-2">Abstract</h2>
    <p class="mb-6 whitespace-pre-wrap">{{ optional($row->manuscript)->abstract }}</p>

    @if($row->status === 'invited')
        <form method="POST" action="{{ route('journal.reviews.update', $row->id) }}" class="flex gap-3 mb-6">
            @csrf @method('PUT')
            <button name="action" value="accept" class="bg-[#000435] text-white px-4 py-2">Accept invitation</button>
            <button name="action" value="decline" class="bg-gray-200 px-4 py-2">Decline</button>
        </form>
    @endif

    @if(in_array($row->status, ['accepted', 'in_progress'], true))
        <h2 class="font-semibold mb-3">Submit peer-review report</h2>
        <form method="POST" action="{{ route('journal.reviews.update', $row->id) }}" class="space-y-3">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm mb-1">Recommendation</label>
                <select name="recommendation" class="w-full border p-2" required>
                    <option value="accept">Accept</option>
                    <option value="minor_revision">Minor revision</option>
                    <option value="major_revision">Major revision</option>
                    <option value="reject">Reject</option>
                </select>
            </div>
            <div>
                <label class="block text-sm mb-1">Comments to author</label>
                <textarea name="comments_to_author" rows="5" class="w-full border p-2" required></textarea>
            </div>
            <div>
                <label class="block text-sm mb-1">Confidential comments to editor</label>
                <textarea name="comments_to_editor" rows="3" class="w-full border p-2"></textarea>
            </div>
            <button class="bg-[#000435] text-white px-4 py-2">Submit review</button>
        </form>
    @endif

    @if($row->status === 'completed')
        <div class="border border-gray-200 p-4">
            <p class="mb-2">Recommendation: <strong>{{ $row->recommendation }}</strong></p>
            <p class="whitespace-pre-wrap text-sm">{{ $row->comments_to_author }}</p>
        </div>
    @endif

    @if($row->status === 'declined')
        <p class="text-gray-500">You declined this invitation.</p>
    @endif
</div>
@endsection
