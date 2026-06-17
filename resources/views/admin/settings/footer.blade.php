@extends('admin.layout.app')
@section('title', 'Footer Settings')
@push('style')
<style>
    .page-heading {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
    }
    .settings-card {
        background: #fff;
        border: 1px solid #e7eaec;
        border-radius: 4px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .settings-card h3 {
        margin-top: 0;
        margin-bottom: 16px;
        font-size: 18px;
        font-weight: 600;
    }
</style>
@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col">
        <h2>Footer Settings</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li class="active"><strong>Footer Settings</strong></li>
        </ol>
    </div>
</div>

<div class="wrapper wrapper-content animated fadeInRight">
    <form action="{{ route('footer.setting.update') }}" method="POST">
        @csrf

        <div class="settings-card">
            <h3>Website Footer</h3>
            <div class="form-group">
                <label for="footer">Website footer content</label>
                <textarea class="form-control" id="footer" name="footer" rows="4">{{ old('footer', $settings->footer_settings ?? '') }}</textarea>
            </div>
        </div>

        <div class="settings-card">
            <h3>Invoice &amp; Receipt Footer</h3>
            <p class="text-muted">Shown on student/admin invoice and receipt PDFs and print views.</p>

            <div class="form-group">
                <label for="invoice_footer_usa">USA &amp; Canada</label>
                <textarea class="form-control" id="invoice_footer_usa" name="invoice_footer_usa" rows="2" placeholder="USA & Canada address and phone">{{ old('invoice_footer_usa', $settings->invoice_footer_usa ?? '') }}</textarea>
            </div>
            <div class="form-group">
                <label for="invoice_footer_uk">UK &amp; Europe</label>
                <textarea class="form-control" id="invoice_footer_uk" name="invoice_footer_uk" rows="2">{{ old('invoice_footer_uk', $settings->invoice_footer_uk ?? '') }}</textarea>
            </div>
            <div class="form-group">
                <label for="invoice_footer_middle_east">Middle East</label>
                <textarea class="form-control" id="invoice_footer_middle_east" name="invoice_footer_middle_east" rows="2">{{ old('invoice_footer_middle_east', $settings->invoice_footer_middle_east ?? '') }}</textarea>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="invoice_footer_email">Finance email</label>
                        <input type="text" class="form-control" id="invoice_footer_email" name="invoice_footer_email" value="{{ old('invoice_footer_email', $settings->invoice_footer_email ?? 'Finance@berkeleyme.com') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="invoice_footer_website">Website</label>
                        <input type="text" class="form-control" id="invoice_footer_website" name="invoice_footer_website" value="{{ old('invoice_footer_website', $settings->invoice_footer_website ?? 'www.eduberkeley.com') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="invoice_footer_presence">Presence line</label>
                        <input type="text" class="form-control" id="invoice_footer_presence" name="invoice_footer_presence" value="{{ old('invoice_footer_presence', $settings->invoice_footer_presence ?? 'USA | Canada | UK | UAE | KSA | China | Africa') }}">
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Update Footer Settings</button>
    </form>
</div>
@endsection
