<x-mail::message>
# You have received an invoice.

**Billed To**: {{ $invoice->client->name }}<br>
**Due Date**: {{ $invoice->due_date->format('F j, Y') }}<br>
**Total**: {{ $invoice->formattedTotal() }}<br>

<table class="table" style="width: 100%; border-collapse: collapse; background: transparent;">
    <thead>
        <tr>
            <th style="text-align: left; padding: 8px 0; border-bottom: 1px solid #ddd; width: 60%;">Description</th>
            <th style="text-align: left; padding: 8px 0; border-bottom: 1px solid #ddd; width: 20%;">Rate</th>
            <th style="text-align: right; padding: 8px 0; border-bottom: 1px solid #ddd; width: 20%;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($invoice->invoiceLines as $line)
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #f0f0f0;">{{ $line->description }}</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                @if($line->hours > 1)
                    {{ $line->formattedHourlyRate() }}<br>x {{ $line->hours ? number_format($line->hours, fmod($line->hours, 1) ? 2 : 0) : '' }}
                @else
                    {{ $line->formattedSubTotal() }}
                @endif
            </td>
            <td style="text-align: right; padding: 8px 0; border-bottom: 1px solid #f0f0f0;">{{ $line->formattedSubTotal() }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

@if ($invoice->note)
<strong>Note:</strong><br>
{{ $invoice->note }}
@endif

Thanks,<br>
Andrew Krzynowek
</x-mail::message>
