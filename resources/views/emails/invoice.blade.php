<x-mail::message>
<div style="margin-bottom: 8px;">
    <span style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 1.5px; color: #a0aec0;">Invoice</span>
</div>

<div style="margin-bottom: 28px;">
    <span style="font-size: 18px; font-weight: 700; color: #2d3748;">Sugardev, LLC</span><br>
    <span style="font-size: 11px; color: #a0aec0; letter-spacing: 0.3px;">Software Development & Consulting</span><br>
    <span style="font-size: 12px; color: #718096;">Andrew Krzynowek</span><br>
    <span style="font-size: 12px; color: #718096;">Sugar Land, TX</span>
</div>

<hr style="border: none; border-top: 1px solid #e8e5ef; margin: 0 0 28px 0;">

<table style="width: 100%; margin-bottom: 32px; border-collapse: collapse;">
    <tr>
        <td style="width: 50%; vertical-align: top; padding: 0;">
            <span style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #8795a1;">Billed To</span><br>
            <span style="font-size: 14px; font-weight: 600; color: #2d3748;">{{ $invoice->client->name }}</span>
        </td>
        <td style="width: 50%; vertical-align: top; padding: 0; text-align: right;">
            <table style="margin-left: auto; border-collapse: collapse;">
                <tr>
                    <td style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #8795a1; padding: 2px 12px 2px 0; text-align: right;">Invoice #</td>
                    <td style="font-size: 12px; color: #2d3748; padding: 2px 0; text-align: right;">{{ $invoice->id }}</td>
                </tr>
                <tr>
                    <td style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #8795a1; padding: 2px 12px 2px 0; text-align: right;">Date</td>
                    <td style="font-size: 12px; color: #2d3748; padding: 2px 0; text-align: right;">{{ $invoice->created_at->format('F j, Y') }}</td>
                </tr>
                <tr>
                    <td style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #8795a1; padding: 2px 12px 2px 0; text-align: right;">Due</td>
                    <td style="font-size: 12px; color: #2d3748; padding: 2px 0; text-align: right;">{{ $invoice->due_date->format('F j, Y') }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table class="table" style="width: 100%; border-collapse: collapse; background: transparent;">
    <thead>
        <tr>
            <th style="text-align: left; padding: 10px 0; border-bottom: 2px solid #e2e8f0; width: 55%; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #8795a1;">Description</th>
            <th style="text-align: right; padding: 10px 16px 10px 8px; border-bottom: 2px solid #e2e8f0; width: 20%; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #8795a1;">Rate</th>
            <th style="text-align: right; padding: 10px 0; border-bottom: 2px solid #e2e8f0; width: 25%; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #8795a1;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($invoice->invoiceLines as $line)
        @php $isLast = $loop->last; @endphp
        <tr>
            <td style="vertical-align: top; padding: 10px 0; {{ $isLast ? '' : 'border-bottom: 1px solid #f0f0f0;' }} font-size: 13px; color: #4a5568; line-height: 1.5;">{{ $line->description }}</td>
            <td style="vertical-align: top; text-align: right; padding: 10px 16px 10px 8px; {{ $isLast ? '' : 'border-bottom: 1px solid #f0f0f0;' }} white-space: nowrap;">
                @if($line->hourly_rate && $line->hours)
                    <span style="font-size: 13px; font-weight: 500; color: #4a5568;">{{ $line->formattedHourlyRate() }} / hr</span><br>
                    <span style="font-size: 11px; color: #a0aec0; line-height: 1.2;">
                        @if($line->hours < 1)
                            {{ round($line->hours * 60) }} min
                        @elseif($line->hours == 1)
                            1 hr
                        @else
                            {{ number_format($line->hours, fmod($line->hours, 1) ? 2 : 0) }} hrs
                        @endif
                    </span>
                @else
                    <span style="font-size: 13px; font-weight: 500; color: #4a5568;">{{ $line->formattedSubTotal() }}</span>
                @endif
            </td>
            <td style="vertical-align: top; text-align: right; padding: 10px 0; {{ $isLast ? '' : 'border-bottom: 1px solid #f0f0f0;' }} font-size: 13px; font-weight: 600; color: #2d3748;">{{ $line->formattedSubTotal() }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div style="text-align: right; margin-top: 28px; padding-top: 20px; border-top: 2px solid #e2e8f0;">
    <span style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #8795a1;">Amount Due</span><br>
    <span style="font-size: 28px; font-weight: 700; color: #2d3748; letter-spacing: -0.5px;">{{ $invoice->formattedTotal() }}</span>
</div>

<br>

@if ($invoice->note)
<strong>Note:</strong><br>
{{ $invoice->note }}
@endif

Thanks,<br>
Andrew Krzynowek<br>
<span style="font-size: 12px; color: #8795a1;">Founder, Sugardev, LLC</span>
</x-mail::message>
