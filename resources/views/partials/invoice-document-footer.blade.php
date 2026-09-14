@php($footer = invoice_footer_settings())
<div class="invoice-document-footer">
    <p style="margin: 0 0 2px;">{!! $footer['usa'] !!}</p>
    <p style="margin: 0 0 2px;">{!! $footer['uk'] !!}</p>
    <p style="margin: 0 0 2px;">{!! $footer['middle_east'] !!}</p>
    <p style="margin: 0 0 2px;">
        <strong>E:</strong> {{ $footer['email'] }} &nbsp; | &nbsp;
        <strong>W:</strong> {{ $footer['website'] }}
    </p>
    <p style="margin: 0;"><strong>Presence:</strong> {!! $footer['presence'] !!}</p>
</div>
