@props([
    'value' => '',
    'required' => '',
    'end_date' => null,
    'col_size_class' => null,
])

@php
    // Dates are shown and typed in the Italian format (gg/mm/aaaa); the value we receive from
    // the model/old() is ISO (Y-m-d), so convert it for display. The NormalizeLocalizedDates
    // middleware converts the submitted d/m/Y back to Y-m-d, so the backend/DB stay ISO.
    $ccDisplayValue = $value;
    if ($value && preg_match('/^\d{4}-\d{2}-\d{2}/', (string) $value)) {
        try { $ccDisplayValue = \Illuminate\Support\Carbon::parse($value)->format('d/m/Y'); } catch (\Throwable $e) { $ccDisplayValue = $value; }
    }
    $ccEndDate = $end_date;
    if ($end_date && preg_match('/^\d{4}-\d{2}-\d{2}/', (string) $end_date)) {
        try { $ccEndDate = \Illuminate\Support\Carbon::parse($end_date)->format('d/m/Y'); } catch (\Throwable $e) { $ccEndDate = $end_date; }
    }
@endphp

<!-- Datepicker -->
<div class="input-group date {{ $col_size_class }}"
     data-provide="datepicker" data-date-today-highlight="true" data-date-language="{{ auth()->user()->locale }}" data-date-locale="{{ auth()->user()->locale }}" data-date-format="dd/mm/yyyy" data-date-autoclose="true" data-date-clear-btn="true" data-date-today-btn="linked" {{ $ccEndDate ? ' data-date-end-date=' . $ccEndDate : '' }}>
    <input type="text" placeholder="{{ trans('general.select_date') }}" value="{{ $ccDisplayValue }}" maxlength="10" {{ $attributes->merge(['class' => 'form-control']) }} {{ $required=='1' ? 'required' : '' }}>
    <span class="input-group-addon"><x-icon type="calendar" /></span>

</div>