@extends('layouts.app')
@section('title', 'Editorial queue')
@section('content')
<div class="container mx-auto px-4 pt-[100px] pb-16 max-w-5xl">
    <h1 class="text-2xl font-bold mb-6">Editorial queue</h1>
    <table class="w-full border text-sm">
        <thead><tr class="bg-gray-50"><th class="border p-2 text-left">Title</th><th class="border p-2">Author</th><th class="border p-2">Status</th><th class="border p-2"></th></tr></thead>
        <tbody>
        @forelse($rows as $row)
            <tr>
                <td class="border p-2">{{ $row->title }}</td>
                <td class="border p-2">{{ optional($row->author)->name }}</td>
                <td class="border p-2 text-center">{{ $row->status }}</td>
                <td class="border p-2 text-center"><a href="{{ route('journal.editorial.show', $row->id) }}">Open</a></td>
            </tr>
        @empty
            <tr><td colspan="4" class="border p-4 text-center text-gray-500">Queue empty.</td></tr>
        @endforelse
        </tbody>
    </table>
    <p class="mt-4"><a href="{{ route('journal.dashboard') }}">← Dashboard</a></p>
</div>
@endsection
