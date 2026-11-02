@php
    $sharedHourlyRate = $invoice->formattedSharedHourlyRate();
    $billingPeriod = $invoice->formattedBillingPeriod();
    $hasProjectLines = $invoice->hasProjectLines();
    $projectGroups = $hasProjectLines ? $invoice->linesGroupedByProject() : collect();
    $labelStyle = 'font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: #8a91a6;';
    $metaValueStyle = 'font-size: 13px; color: #1d2233; line-height: 1.4;';
    $columnHeadStyle = 'padding: 8px 0; border-bottom: 2px solid #1d2233; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: #8a91a6;';
@endphp
<x-mail::message>
<div style="margin-bottom: 4px; {{ $labelStyle }} font-size: 11px; letter-spacing: 1.2px;">Invoice #{{ $invoice->id }} · {{ $invoice->client->name }}</div>
<div style="margin-bottom: 4px; font-size: 40px; font-weight: 700; letter-spacing: -1px; line-height: 1.15; color: #1d2233;">{{ $invoice->formattedTotal() }}</div>
<div style="margin-bottom: 22px; font-size: 14px; color: #4a5268;">Due <strong style="color: #1d2233;">{{ $invoice->due_date->format('F j, Y') }}</strong></div>

<table role="presentation" style="width: 100%; border-collapse: separate; background-color: #f5f7fb; border-radius: 6px; margin-bottom: 28px;">
    <tr>
        <td style="width: 33%; vertical-align: top; padding: 16px 10px 8px 18px;"><span style="{{ $labelStyle }}">Billed to</span><br><span style="{{ $metaValueStyle }}">{{ $invoice->client->name }}</span></td>
        <td style="width: 34%; vertical-align: top; padding: 16px 10px 8px 10px;"><span style="{{ $labelStyle }}">From</span><br><span style="{{ $metaValueStyle }}">Sugardev, LLC<br>Sugar Land, TX</span></td>
        <td style="width: 33%; vertical-align: top; padding: 16px 18px 8px 10px;"><span style="{{ $labelStyle }}">Invoice #</span><br><span style="{{ $metaValueStyle }}">{{ $invoice->id }}</span></td>
    </tr>
    <tr>
        <td style="vertical-align: top; padding: 8px 10px 16px 18px;"><span style="{{ $labelStyle }}">Issued</span><br><span style="{{ $metaValueStyle }}">{{ $invoice->created_at->format('M j, Y') }}</span></td>
        <td style="vertical-align: top; padding: 8px 10px 16px 10px;"><span style="{{ $labelStyle }}">Due</span><br><span style="{{ $metaValueStyle }}">{{ $invoice->due_date->format('M j, Y') }}</span></td>
        <td style="vertical-align: top; padding: 8px 18px 16px 10px;">
@if($billingPeriod)
            <span style="{{ $labelStyle }}">Period</span><br><span style="{{ $metaValueStyle }}">{{ $billingPeriod }}</span>
@endif
        </td>
    </tr>
</table>

@if($invoice->note)
<div style="margin-bottom: 28px; font-size: 15px; line-height: 1.6; color: #4a5268;">{!! nl2br(e($invoice->note)) !!}</div>
@endif

<table role="presentation" style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
    <tr>
        <td style="padding: 0; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #1d2233;">Work performed</td>
@if($sharedHourlyRate)
        <td style="padding: 0; text-align: right; font-size: 12px; color: #8a91a6;">Hourly at {{ $sharedHourlyRate }} / hr</td>
@endif
    </tr>
</table>

<table class="table" style="width: 100%; border-collapse: collapse; background: transparent;">
    <thead>
        <tr>
            <th style="{{ $columnHeadStyle }} text-align: left; width: 54px;">Date</th>
            <th style="{{ $columnHeadStyle }} text-align: left;">Description</th>
            <th style="{{ $columnHeadStyle }} text-align: right; width: 56px;">Hours</th>
            <th style="{{ $columnHeadStyle }} text-align: right; width: 86px;">Amount</th>
        </tr>
    </thead>
    <tbody>
@if($hasProjectLines)
@foreach ($projectGroups as $group)
        <tr>
            <td colspan="2" style="padding: {{ $loop->first ? '14px' : '24px' }} 0 8px 0; border-bottom: 1px solid #1d2233; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: #1d2233;">{{ $group['name'] }}</td>
            <td style="padding: {{ $loop->first ? '14px' : '24px' }} 0 8px 0; border-bottom: 1px solid #1d2233; text-align: right; font-size: 12px; font-weight: 600; color: #8a91a6; white-space: nowrap;">{{ $group['formattedHours'] }}</td>
            <td style="padding: {{ $loop->first ? '14px' : '24px' }} 0 8px 0; border-bottom: 1px solid #1d2233; text-align: right; font-size: 12px; font-weight: 700; color: #1d2233; white-space: nowrap;">{{ $group['formattedSubtotal'] }}</td>
        </tr>
@foreach ($group['lines'] as $line)
@include('emails.partials.invoice-line-row', ['line' => $line, 'showRate' => ! $sharedHourlyRate])
@endforeach
@endforeach
@else
@foreach ($invoice->invoiceLines as $line)
@include('emails.partials.invoice-line-row', ['line' => $line, 'showRate' => ! $sharedHourlyRate])
@endforeach
@endif
    </tbody>
</table>

<table role="presentation" style="width: 100%; border-collapse: collapse; margin-top: 14px;">
@if($hasProjectLines)
@foreach ($projectGroups as $group)
    <tr>
        <td style="padding: 3px 24px 3px 0; text-align: right; font-size: 13px; color: #8a91a6;">{{ $group['name'] }}{{ $group['formattedHours'] ? " · {$group['formattedHours']} hrs" : '' }}</td>
        <td style="padding: 3px 0; text-align: right; font-size: 13px; color: #1d2233; width: 86px; white-space: nowrap;">{{ $group['formattedSubtotal'] }}</td>
    </tr>
@endforeach
@elseif($invoice->total_hours > 0)
    <tr>
        <td style="padding: 3px 24px 3px 0; text-align: right; font-size: 13px; color: #8a91a6;">Total hours</td>
        <td style="padding: 3px 0; text-align: right; font-size: 13px; color: #1d2233; width: 86px; white-space: nowrap;">{{ \App\Models\InvoiceLine::formatHoursAsDecimal((float) $invoice->total_hours) }}</td>
    </tr>
@endif
    <tr>
        <td style="padding: 12px 24px 0 0; text-align: right;"><span style="display: inline-block; padding-top: 10px; border-top: 2px solid #1d2233; font-size: 16px; font-weight: 700; color: #1d2233;">Amount due</span></td>
        <td style="padding: 12px 0 0 0; text-align: right; white-space: nowrap;"><span style="display: block; padding-top: 10px; border-top: 2px solid #1d2233; font-size: 16px; font-weight: 700; color: #1d2233;">{{ $invoice->formattedTotal() }}</span></td>
    </tr>
</table>

<div style="margin-top: 32px; font-size: 14px; line-height: 1.6; color: #4a5268;">
Questions about any line? Just reply to this email.<br><br>
Thanks,<br>
<strong style="color: #1d2233;">Andrew Krzynowek</strong><br>
Founder, Sugardev, LLC
</div>
</x-mail::message>
