<tr>
    <td style="vertical-align: top; padding: 10px 0; {{ $isLast ? '' : 'border-bottom: 1px solid #f0f0f0;' }} font-size: 13px; color: #4a5568; line-height: 1.5;">{{ $line->description }}</td>
    <td style="vertical-align: top; text-align: right; padding: 10px 16px 10px 8px; {{ $isLast ? '' : 'border-bottom: 1px solid #f0f0f0;' }} white-space: nowrap;">
        @if($line->hourly_rate && $line->hours)
            <span style="font-size: 13px; font-weight: 500; color: #4a5568;">{{ $line->formattedHourlyRate() }} / hr</span><br>
            <span style="font-size: 11px; color: #a0aec0; line-height: 1.2;">{{ $line->formattedHours() }}</span>
        @else
            <span style="font-size: 13px; font-weight: 500; color: #4a5568;">{{ $line->formattedSubTotal() }}</span>
        @endif
    </td>
    <td style="vertical-align: top; text-align: right; padding: 10px 0; {{ $isLast ? '' : 'border-bottom: 1px solid #f0f0f0;' }} font-size: 13px; font-weight: 600; color: #2d3748;">{{ $line->formattedSubTotal() }}</td>
</tr>
