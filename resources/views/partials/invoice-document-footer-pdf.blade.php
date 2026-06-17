@php($footer = invoice_footer_settings())
<div class="footer">
    <div>{!! $footer['usa'] !!}</div>
    <div>{!! $footer['uk'] !!}</div>
    <div>{!! $footer['middle_east'] !!}</div>
    <div>
        <strong>E:</strong> {{ $footer['email'] }} &nbsp; | &nbsp;
        <strong>W:</strong> {{ $footer['website'] }}
    </div>
    <div><strong>Presence:</strong> {!! $footer['presence'] !!}</div>
</div>
