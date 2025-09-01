<x-mail::message>
# You have received an invoice.

**Billed To**: {{ $invoice->client->name }}<br>
**Due Date**: {{ $invoice->due_date->format('F j, Y') }}<br>
**Total**: {{ $invoice->formattedTotal() }}<br>

<x-mail::table >
    | Description | Rate | Total |
    | :-----------| :---- | ----: |
    @foreach ($invoice->invoiceLines as $line)
        @if($line->hours > 1)
        | {{ $line->description }} |  {{ $line->formattedSubTotal() }} x {{ $line->hours ? number_format($line->hours, fmod($line->hours, 1) ? 2 : 0) : '' }} | {{ $line->formattedSubTotal() }} |
        @else
            | {{ $line->description }} |  {{ $line->formattedSubTotal() }} | {{ $line->formattedSubTotal() }} |
        @endif
    @endforeach
</x-mail::table>

@if ($invoice->note)
<strong>Note:</strong><br>
{{ $invoice->note }}
@endif

Thanks,<br>
Andrew Krzynowek
</x-mail::message>
