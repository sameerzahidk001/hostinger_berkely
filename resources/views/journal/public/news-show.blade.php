@extends('layouts.app')
@section('title', $row->title)
@section('content')
<div class="container mx-auto px-4 pt-[100px] pb-16 max-w-3xl">
    <p class="text-xs uppercase tracking-wide text-gray-500 mb-2">{{ $row->type }} · {{ optional($row->published_at)->format('Y-m-d') }}</p>
    <h1 class="text-3xl font-bold mb-6 text-[#000435]">{{ $row->title }}</h1>
    <div class="prose max-w-none">{!! $row->body !!}</div>
    <p class="mt-8 text-sm"><a href="{{ route('journal.news.index') }}" class="text-[#1c5fb8] underline">← News &amp; events</a></p>
</div>
@endsection
