{{--
    Accessible Form Input Component
    Usage:
    @include('components.form-input', [
        'name' => 'field_name',
        'label' => 'Field Label',
        'type' => 'text',
        'value' => old('field_name'),
        'required' => true,
        'pattern' => null,
        'min' => null,
        'max' => null,
        'placeholder' => null,
        'hint' => null,
        'error' => $errors->first('field_name'),
        'ariaDescribedBy' => null,
        'autocomplete' => null,
    ])
--}}
@php
    $id = $id ?? $name;
    $type = $type ?? 'text';
    $inputClass = 'form-input' . ($error ? ' is-invalid' : '');
    $describedBy = [];

    if ($hint ?? false) {
        $describedBy[] = $id . '-hint';
    }
    if ($error ?? false) {
        $describedBy[] = $id . '-error';
    }
    if ($ariaDescribedBy ?? false) {
        $describedBy[] = $ariaDescribedBy;
    }
@endphp

<div class="form-group {{ $fullWidth ?? false ? 'full-width' : '' }}">
    <label for="{{ $id }}">
        {{ $label }}
        @if($required ?? false)
            <span class="required" aria-label="required">*</span>
        @endif
    </label>

    @if(in_array($type, ['textarea']))
        <textarea
            id="{{ $id }}"
            name="{{ $name }}"
            class="{{ $inputClass }}"
            @if($required ?? false) required @endif
            @if($placeholder ?? false) placeholder="{{ $placeholder }}" @endif
            @if($rows ?? false) rows="{{ $rows }}" @else rows="3" @endif
            @if(count($describedBy)) aria-describedby="{{ implode(' ', $describedBy) }}" @endif
            @if($autocomplete ?? false) autocomplete="{{ $autocomplete }}" @endif
        >{{ $value ?? '' }}</textarea>
    @elseif(in_array($type, ['select']))
        <select
            id="{{ $id }}"
            name="{{ $name }}"
            class="{{ $inputClass }}"
            @if($required ?? false) required @endif
            @if(count($describedBy)) aria-describedby="{{ implode(' ', $describedBy) }}" @endif
        >
            {{ $slot }}
        </select>
    @else
        <input
            type="{{ $type }}"
            id="{{ $id }}"
            name="{{ $name }}"
            value="{{ $value ?? '' }}"
            class="{{ $inputClass }}"
            @if($required ?? false) required @endif
            @if($pattern ?? false) pattern="{{ $pattern }}" @endif
            @if($min ?? false) min="{{ $min }}" @endif
            @if($max ?? false) max="{{ $max }}" @endif
            @if($step ?? false) step="{{ $step }}" @endif
            @if($placeholder ?? false) placeholder="{{ $placeholder }}" @endif
            @if(count($describedBy)) aria-describedby="{{ implode(' ', $describedBy) }}" @endif
            @if($autocomplete ?? false) autocomplete="{{ $autocomplete }}" @endif
            @if($inputmode ?? false) inputmode="{{ $inputmode }}" @endif
        >
    @endif

    @if($hint ?? false)
        <div id="{{ $id }}-hint" class="hint">{{ $hint }}</div>
    @endif

    @if($error ?? false)
        <div id="{{ $id }}-error" class="error" role="alert">{{ $error }}</div>
    @endif
</div>
