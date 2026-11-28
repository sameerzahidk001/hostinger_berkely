@extends('layouts.app')
@section('title', 'Review assignments')
@section('content')
<div class="container mx-auto px-4 pt-[100px] pb-16 max-w-4xl">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold mb-0">Review assignments</h1>
        <a href="{{ route('journal.dashboard') }}" class="text-sm text-[#1c5fb8] underline">← Dashboard</a>
    </div>
    @if(session('status'))<div class="mb-4 p-3 bg-green-50 border">{{ session('status') }}</div>@endif
    <table class="w-full border border-gray-200 text-sm">
        <thead>
            <tr class="bg-gray-50">
                <th class="border p-2 text-left">Manuscript</th>
                <th class="border p-2">Status</th>
                <th class="border p-2"></th>
            </tr>
        </thead>
        <tbody>
        @forelse($rows as $row)
            <tr>
                <td class="border p-2">{{ optional($row->manuscript)->title }}</td>
                <td class="border p-2 text-center">{{ ucfirst(str_replace('_', ' ', $row->status)) }}</td>
                <td class="border p-2 text-center"><a href="{{ route('journal.reviews.show', $row->id) }}" class="text-[#1c5fb8] underline">Open</a></td>
            </tr>
        @empty
            <tr><td colspan="3" class="border p-4 text-center text-gray-500">No assignments.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
