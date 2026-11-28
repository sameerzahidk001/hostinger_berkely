@extends('layouts.app')
@section('title', 'Editorial queue')
@section('content')
@php use App\Services\JournalWorkflow; @endphp
<div class="container mx-auto px-4 pt-[100px] pb-16 max-w-5xl">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <h1 class="text-2xl font-bold mb-0">Editorial queue</h1>
        <a href="{{ route('journal.dashboard') }}" class="text-sm text-[#1c5fb8] underline">← Dashboard</a>
    </div>
    <div class="flex flex-wrap gap-2 mb-6 text-sm">
        <a href="{{ route('journal.editorial.index') }}" class="px-3 py-1 border {{ $status === '' ? 'bg-[#000435] text-white' : '' }}">All active</a>
        @foreach(['submitted','under_review','revision','accepted'] as $st)
            <a href="{{ route('journal.editorial.index', ['status' => $st]) }}" class="px-3 py-1 border {{ $status === $st ? 'bg-[#000435] text-white' : '' }}">{{ JournalWorkflow::statusLabel($st) }}</a>
        @endforeach
    </div>
    <table class="w-full border text-sm">
        <thead>
            <tr class="bg-gray-50">
                <th class="border p-2 text-left">Title</th>
                <th class="border p-2">Author</th>
                <th class="border p-2">Editor</th>
                <th class="border p-2">Stage</th>
                <th class="border p-2"></th>
            </tr>
        </thead>
        <tbody>
        @forelse($rows as $row)
            <tr>
                <td class="border p-2">{{ $row->title }}</td>
                <td class="border p-2 text-center">{{ optional($row->author)->name }}</td>
                <td class="border p-2 text-center">{{ optional($row->editor)->name ?: '—' }}</td>
                <td class="border p-2 text-center">{{ JournalWorkflow::statusLabel($row->status) }}</td>
                <td class="border p-2 text-center"><a href="{{ route('journal.editorial.show', $row->id) }}" class="text-[#1c5fb8] underline">Manage</a></td>
            </tr>
        @empty
            <tr><td colspan="5" class="border p-4 text-center text-gray-500">Queue empty for this filter.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
