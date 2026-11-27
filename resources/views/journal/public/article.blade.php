@extends('layouts.app')
@section('title', $row->title)
@section('content')
<div class="container mx-auto px-4 pt-[100px] pb-16 max-w-3xl">
    <h1 class="text-3xl font-bold mb-3">{{ $row->title }}</h1>
    <p class="text-sm text-gray-600 mb-6">
        {{ $row->corresponding_author_name ?: optional($row->author)->name }}
        @if($row->orcid) · ORCID {{ $row->orcid }} @endif
        @if($row->doi) · DOI {{ $row->doi }} @endif
        · {{ optional($row->published_at)->format('Y-m-d') }}
        · {{ $row->view_count }} views
    </p>
    <h2 class="text-lg font-semibold mb-2">Abstract</h2>
    <p class="mb-6 whitespace-pre-wrap">{{ $row->abstract }}</p>
    @if($row->html_body)
        <div class="prose max-w-none mb-6">{!! nl2br(e($row->html_body)) !!}</div>
    @endif
    @if($row->references_text)
        <h2 class="text-lg font-semibold mb-2">References</h2>
        <pre class="whitespace-pre-wrap text-sm bg-gray-50 p-4 border mb-6">{{ $row->references_text }}</pre>
    @endif
    @if($row->pdf_path)
        @auth
            <a href="{{ route('journal.articles.pdf', $row->slug) }}"
               class="inline-block bg-[#000435] text-white px-4 py-2 no-underline">Download PDF</a>
        @else
            <div class="rounded border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
                <p class="mb-3 font-medium">This journal is visible to everyone. PDF download requires sign up.</p>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ path_login_url('journal') }}" class="inline-block bg-[#000435] text-white px-4 py-2 no-underline">Sign in to download</a>
                    <a href="{{ path_register_url('journal') }}" class="inline-block border border-[#000435] text-[#000435] px-4 py-2 no-underline">Register</a>
                </div>
            </div>
        @endauth
    @endif
    <p class="mt-8 text-sm text-gray-500"><a href="{{ route('journal.articles.search') }}">← Search</a></p>
</div>
@endsection
