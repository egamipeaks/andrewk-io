<x-mail::message>
<div style="margin-bottom: 8px;">
    <span style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 1.5px; color: #a0aec0;">Hours Update</span>
</div>

<div style="margin-bottom: 24px;">
    <span style="font-size: 18px; font-weight: 700; color: #2d3748;">{{ $client->name }}</span><br>
    <span style="font-size: 12px; color: #718096;">{{ $asOf->copy()->startOfMonth()->format('F j') }} to {{ $asOf->format('F j, Y') }}</span>
</div>

<div style="margin-bottom: 28px; padding: 14px 16px; background: #f7fafc; border-left: 3px solid #a0aec0; font-size: 13px; color: #4a5568;">
    <strong>This is not a bill.</strong> Here are the hours worked this month that haven't been invoiced yet. Hours already on an invoice are not included.
</div>

<table class="table" style="width: 100%; border-collapse: collapse; background: transparent;">
    <thead>
        <tr>
            <th style="text-align: left; padding: 10px 0; border-bottom: 2px solid #e2e8f0; width: 55%; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #8795a1;">Project</th>
            <th style="text-align: right; padding: 10px 16px 10px 8px; border-bottom: 2px solid #e2e8f0; width: 20%; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #8795a1;">Hours</th>
            <th style="text-align: right; padding: 10px 0; border-bottom: 2px solid #e2e8f0; width: 25%; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #8795a1;">Amount</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($groups as $group)
        <tr>
            <td style="padding: 10px 0; border-bottom: 1px solid #edf2f7; font-size: 13px; color: #2d3748;">{{ $group['name'] }}</td>
            <td style="text-align: right; padding: 10px 16px 10px 8px; border-bottom: 1px solid #edf2f7; font-size: 13px; color: #4a5568;">{{ \App\Models\InvoiceLine::formatHours($group['hours']) }}</td>
            <td style="text-align: right; padding: 10px 0; border-bottom: 1px solid #edf2f7; font-size: 13px; color: #2d3748;">{{ $client->currency->format($group['amount']) }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="3" style="padding: 10px 0; border-bottom: 1px solid #edf2f7; font-size: 13px; color: #8795a1;">No un-invoiced hours this month.</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div style="text-align: right; margin-top: 28px; padding-top: 20px; border-top: 2px solid #e2e8f0;">
    <span style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #8795a1;">Not yet invoiced (estimate) · {{ \App\Models\InvoiceLine::formatHours($groups->sum('hours')) }}</span><br>
    <span style="font-size: 28px; font-weight: 700; color: #2d3748; letter-spacing: -0.5px;">{{ $client->currency->format($groups->sum('amount')) }}</span>
</div>

<br>

No payment is needed right now. These hours will appear on your next invoice.

Thanks,<br>
Andrew Krzynowek<br>
<span style="font-size: 12px; color: #8795a1;">Founder, Sugardev, LLC</span>
</x-mail::message>
