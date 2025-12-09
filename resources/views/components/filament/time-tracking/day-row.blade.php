<tr class="{{ $getRowClasses() }}">
    <td class="{{ $getDayCellClasses() }}">
        {{ $getDayLabel() }}
    </td>
    {{ $slot }}
</tr>