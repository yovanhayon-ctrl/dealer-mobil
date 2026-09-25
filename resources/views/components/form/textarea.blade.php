@props([
    'name',
    'label',
    'value' => null,
    'rows' => 4,
    'required' => false,
    'help' => null,
])

@php
    $id = $attributes->get('id', $name);
@endphp

<div class="mb-3">
    <label for="{{ $id }}" class="form-label">
        {{ $label }}@if ($required)<span class="text-danger ms-1" aria-hidden="true">*</span>@endif
    </label>
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @required($required)
              {{ $attributes->except('id')->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>{{ old($name, $value) }}</textarea>
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @else
        @if ($help)
            <div class="form-text">{{ $help }}</div>
        @endif
    @enderror
</div>
