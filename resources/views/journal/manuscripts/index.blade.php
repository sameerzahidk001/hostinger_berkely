@extends('layouts.app')
@section('title', 'My manuscripts')
@section('content')
<div class="container mx-auto px-4 pt-[100px] pb-16 max-w-4xl">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">My manuscripts</h1>
        <a href="{{ route('journal.manuscripts.create') }}" class="bg-[#000435] text-white px-4 py-2 no-underline">Submit new</a>
    </div>
    @if(session('status'))<div class="mb-4 p-3 bg-green-50 border border-green-200">{{ session('status') }}</div>@endif
    <table class="w-full border-collapse border border-gray-200 text-sm">
        <thead><tr class="bg-gray-50"><th class="border p-2 text-left">Title</th><th class="border p-2">Status</th><th class="border p-2">Submitted</th><th class="border p-2"></th></tr></thead>
        <tbody>
        @forelse($rows as $row)
            <tr>
                <td class="border p-2">{{ $row->title }}</td>
                <td class="border p-2 text-center">{{ $row->status }}</td>
                <td class="border p-2 text-center">{{ optional($row->submitted_at)->format('Y-m-d') ?: '—' }}</td>
                <td class="border p-2 text-center"><a href="{{ route('journal.manuscripts.show', $row->id) }}">View</a></td>
            </tr>
        @empty
            <tr><td colspan="4" class="border p-4 text-center text-gray-500">No manuscripts yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    <p class="mt-4"><a href="{{ route('journal.dashboard') }}">← Dashboard</a></p>
</div>
@endsection
