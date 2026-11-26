@extends('layouts.app')

@section('title', 'Academic Journal Dashboard')

@section('content')
<div class="container mx-auto px-4 pt-[100px] pb-16 max-w-5xl">
    <h1 class="text-3xl font-bold text-[#000435] mb-2">Academic Journal dashboard</h1>
    <p class="text-gray-600 mb-8">
        Signed in as {{ $user->name ?? 'User' }}
        @if(!empty($roleNames))
            <span class="text-sm">({{ implode(', ', $roleNames) }})</span>
        @endif
    </p>

    @if(session('status'))
        <div class="mb-6 p-3 bg-green-50 border border-green-200 text-green-800 rounded">{{ session('status') }}</div>
    @endif

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
        <div class="border border-gray-200 p-4">
            <div class="text-2xl font-semibold">{{ $stats['my_manuscripts'] }}</div>
            <div class="text-sm text-gray-600">My manuscripts</div>
        </div>
        <div class="border border-gray-200 p-4">
            <div class="text-2xl font-semibold">{{ $stats['pending_reviews'] }}</div>
            <div class="text-sm text-gray-600">Pending reviews</div>
        </div>
        <div class="border border-gray-200 p-4">
            <div class="text-2xl font-semibold">{{ $stats['assigned_editorial'] }}</div>
            <div class="text-sm text-gray-600">Editorial queue</div>
        </div>
        <div class="border border-gray-200 p-4">
            <div class="text-2xl font-semibold">{{ $stats['published'] }}</div>
            <div class="text-sm text-gray-600">Published (yours)</div>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        @foreach($cards as $card)
            <div class="border border-gray-200 p-6 flex flex-col">
                <h2 class="text-xl font-semibold text-[#000435] mb-2">{{ $card['title'] }}</h2>
                <p class="text-gray-600 flex-1 mb-4">{{ $card['body'] }}</p>
                <a href="{{ $card['href'] }}" class="inline-block bg-[#000435] text-white px-4 py-2 text-center no-underline hover:opacity-90">
                    {{ $card['cta'] }}
                </a>
            </div>
        @endforeach
    </div>

    <p class="mt-10 text-sm text-gray-500">
        No indexing claims, ISSNs, or citation metrics are shown until officially registered.
        <a href="{{ url('/academic-journal') }}" class="underline">Public journal home</a>
    </p>
</div>
@endsection
