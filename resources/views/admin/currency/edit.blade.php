@extends('admin.layout.app')

@section('title', 'Edit Country')

@section('content')
<div class="container">
    <h2>Edit Country</h2>
    
    <form action="{{ route('currencies.update', $country->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="name">Country Name</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ $country->name }}" disabled>
        </div>

        <div class="form-group">
            <label for="currency_id">Select Currency</label>
            <select name="currency_id" id="currency_id" class="form-control" required>
                @foreach ($currencies as $currency)
                    <option value="{{ $currency->id }}" 
                        {{ $country->currency_id == $currency->id ? 'selected' : '' }}>
                        {{ $currency->name }} ({{ $currency->code }})
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Update Country</button>
    </form>
</div>
@endsection
