@extends('layouts.app')
@section('title', 'Search articles')
@section('content')
<div class="container mx-auto px-4 pt-[100px] pb-16 max-w-4xl">
    <h1 class="text-2xl font-bold mb-4">Search published articles</h1>
    <form method="GET" class="grid md:grid-cols-2 gap-3 mb-8">
        <input name="q" value="{{ $q }}" class="border p-2" placeholder="Keyword / author / title">
        <input name="doi" value="{{ $doi }}" class="border p-2" placeholder="DOI">
        <select name="subject_id" class="border p-2">
            <option value="">All subjects</option>
            @foreach($subjects as $s)
                <option value="{{ $s->id }}" {{ (string)$subjectId === (string)$s->id ? 'selected' : '' }}>{{ $s->name }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <input type="date" name="from" value="{{ $from }}" class="border p-2 flex-1">
            <input type="date" name="to" value="{{ $to }}" class="border p-2 flex-1">
        </div>
        <button class="bg-[#000435] text-white px-4 py-2 md:col-span-2">Search</button>
    </form>
    @forelse($rows as $row)
        <article class="border-b border-gray-200 py-4">
            <h2 class="text-lg font-semibold"><a href="{{ route('journal.articles.show', $row->slug) }}" class="text-[#000435]">{{ $row->title }}</a></h2>
            <p class="text-sm text-gray-600">
                {{ optional($row->published_at)->format('Y-m-d') }}
                @if($row->doi) · {{ $row->doi }} @endif
                · {{ $row->view_count }} views
            </p>
            <p class="text-sm mt-1">{{ \Illuminate\Support\Str::limit($row->abstract, 220) }}</p>
        </article>
    @empty
        <p class="text-gray-500">No published articles match. Nothing is faked here.</p>
    @endforelse
    <div class="mt-6">{{ $rows->links() }}</div>
</div>
@endsection
