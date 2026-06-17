@php($footer = invoice_footer_settings())
<div class="invoice-document-footer">
    <p style="margin-bottom: 0;">{!! $footer['usa'] !!}</p>
    <p style="margin-bottom: 0;">{!! $footer['uk'] !!}</p>
    <p style="margin-bottom: 0;">{!! $footer['middle_east'] !!}</p>
    <p style="margin-bottom: 0;">
        <strong>E:</strong> {{ $footer['email'] }} &nbsp; | &nbsp;
        <strong>W:</strong> {{ $footer['website'] }}
    </p>
    <p style="margin-bottom: 0;"><strong>Presence:</strong> {!! $footer['presence'] !!}</p>
</div>
