@extends('layouts.app')
@section('title', 'Policies')
@section('content')
<div class="container mx-auto px-4 pt-[100px] pb-16 max-w-3xl">
    <h1 class="text-3xl font-bold mb-6 text-[#000435]">Journal policies</h1>
    @forelse($rows as $row)
        <section class="mb-10">
            <h2 class="text-2xl font-bold mb-3">{{ $row->title }}</h2>
            <div class="prose max-w-none">{!! $row->body !!}</div>
        </section>
    @empty
        <p class="text-gray-500">No policies published yet.</p>
    @endforelse
</div>
@endsection
