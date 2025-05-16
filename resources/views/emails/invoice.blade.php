<x-mail::message>
# You have received an invoice.

Invoice: #{{ $invoice->id }}<br>
Billed To: {{ $invoice->client->name }}<br>
Due Date: {{ $invoice->due_date->format('m/d/Y') }}<br>
Total: {{ $invoice->formattedTotal() }}<br>

<x-mail::table >
    | Description | Hours | Rate | Total |
    | ------------| ----- | ----- | ----- |
    @foreach ($invoice->invoiceLines as $line)
        | {{ $line->description }} | {{ $line->hours }} | {{ $line->hourly_rate }} | {{ $line->formattedSubTotal() }} |
    @endforeach
</x-mail::table>

@if ($invoice->note)
<strong>Note:</strong><br>
{{ $invoice->note }}
@endif

Thanks,<br>
Andrew Krzynowek
</x-mail::message>
