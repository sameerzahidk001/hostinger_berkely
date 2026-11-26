@extends('layouts.app')
@section('title', $row->exists ? 'Edit manuscript' : 'Submit manuscript')
@section('content')
<div class="container mx-auto px-4 pt-[100px] pb-16 max-w-3xl">
    <h1 class="text-2xl font-bold mb-6">{{ $row->exists ? 'Edit manuscript' : 'Submit manuscript' }}</h1>
    <form method="POST" enctype="multipart/form-data"
          action="{{ $row->exists ? route('journal.manuscripts.update', $row->id) : route('journal.manuscripts.store') }}"
          class="space-y-4">
        @csrf
        @if($row->exists) @method('PUT') @endif
        <div>
            <label class="block text-sm font-medium mb-1">Title</label>
            <input name="title" class="w-full border border-gray-300 p-2" value="{{ old('title', $row->title) }}" required>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Subject</label>
            <select name="subject_id" class="w-full border border-gray-300 p-2">
                <option value="">—</option>
                @foreach($subjects as $s)
                    <option value="{{ $s->id }}" {{ (string) old('subject_id', $row->subject_id) === (string) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Abstract</label>
            <textarea name="abstract" rows="5" class="w-full border border-gray-300 p-2">{{ old('abstract', $row->abstract) }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Keywords</label>
            <input name="keywords" class="w-full border border-gray-300 p-2" value="{{ old('keywords', $row->keywords) }}">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">ORCID (optional)</label>
            <input name="orcid" class="w-full border border-gray-300 p-2" value="{{ old('orcid', $row->orcid) }}" placeholder="0000-0000-0000-0000">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Manuscript body (HTML text)</label>
            <textarea name="html_body" rows="8" class="w-full border border-gray-300 p-2">{{ old('html_body', $row->html_body) }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">References</label>
            <textarea name="references_text" rows="4" class="w-full border border-gray-300 p-2">{{ old('references_text', $row->references_text) }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">PDF upload</label>
            <input type="file" name="pdf_file" accept="application/pdf">
            @if($row->pdf_path)<div class="text-sm mt-1"><a href="{{ asset($row->pdf_path) }}" target="_blank">Current PDF</a></div>@endif
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">AI disclosure</label>
            <textarea name="ai_disclosure" rows="2" class="w-full border border-gray-300 p-2">{{ old('ai_disclosure', $row->ai_disclosure) }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Conflict of interest</label>
            <textarea name="conflict_of_interest" rows="2" class="w-full border border-gray-300 p-2">{{ old('conflict_of_interest', $row->conflict_of_interest) }}</textarea>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="ethics_confirmed" value="1" {{ old('ethics_confirmed', $row->ethics_confirmed) ? 'checked' : '' }}>
            I confirm this work complies with the journal ethics policy
        </label>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-gray-200 px-4 py-2">Save draft</button>
            <button type="submit" name="submit_now" value="1" class="bg-[#000435] text-white px-4 py-2">Submit for review</button>
        </div>
    </form>
</div>
@endsection
