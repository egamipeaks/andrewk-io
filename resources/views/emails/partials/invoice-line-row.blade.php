<tr>
    <td style="vertical-align: top; padding: 9px 8px 9px 0; border-bottom: 1px solid #e7e9f0; font-size: 13px; color: #8a91a6; line-height: 1.45; white-space: nowrap;">{{ $line->date?->format('M j') }}</td>
    <td style="vertical-align: top; padding: 9px 12px 9px 0; border-bottom: 1px solid #e7e9f0; font-size: 13px; color: #4a5268; line-height: 1.45;">{{ $line->description }}</td>
    <td style="vertical-align: top; padding: 9px 0; border-bottom: 1px solid #e7e9f0; text-align: right; font-size: 13px; color: #4a5268; line-height: 1.45; white-space: nowrap;">
@if($line->isHourly())
        {{ \App\Models\InvoiceLine::formatHoursAsDecimal((float) $line->hours) }}
@if($showRate)
        <br><span style="font-size: 11px; color: #8a91a6;">@ {{ $line->formattedHourlyRate() }}/hr</span>
@endif
@else
        <span style="color: #8a91a6;">Fixed</span>
@endif
    </td>
    <td style="vertical-align: top; padding: 9px 0; border-bottom: 1px solid #e7e9f0; text-align: right; font-size: 13px; font-weight: 600; color: #1d2233; line-height: 1.45; white-space: nowrap;">{{ $line->formattedSubTotal() }}</td>
</tr>
