@php
    $fieldKey = $field['key'];
    $fieldType = $field['type'] ?? 'text';
    $fieldId = 'settings-'.$activeTab.'-'.$fieldKey;
    $fieldValue = old('settings.'.$fieldKey, $settings[$fieldKey] ?? null);
@endphp

<div class="settings-field" @if (! empty($field['depends_on'])) data-depends-on="{{ $field['depends_on'] }}" @endif>
    <label for="{{ $fieldId }}">{{ $field['label'] ?? $fieldKey }}</label>
    @if ($fieldType === 'boolean')
        <label class="settings-switch">
            <input id="{{ $fieldId }}" type="checkbox" name="settings[{{ $fieldKey }}]" value="1" @checked((bool) $fieldValue)>
            <span class="settings-switch__slider"></span>
            <span class="settings-switch__text">{{ (bool) $fieldValue ? 'Enabled' : 'Disabled' }}</span>
        </label>
    @elseif ($fieldType === 'password')
        <input id="{{ $fieldId }}" type="password" name="settings[{{ $fieldKey }}]" value="{{ $fieldValue }}" autocomplete="off">
    @elseif ($fieldType === 'select')
        <select id="{{ $fieldId }}" name="settings[{{ $fieldKey }}]">
            @foreach (($field['options'] ?? []) as $option)
                <option value="{{ $option }}" @selected((string) $fieldValue === (string) $option)>{{ $option }}</option>
            @endforeach
        </select>
    @elseif ($fieldType === 'number')
        <input id="{{ $fieldId }}" type="number" name="settings[{{ $fieldKey }}]" value="{{ $fieldValue }}" @if (isset($field['min'])) min="{{ $field['min'] }}" @endif @if (isset($field['max'])) max="{{ $field['max'] }}" @endif>
    @else
        <input id="{{ $fieldId }}" type="text" name="settings[{{ $fieldKey }}]" value="{{ $fieldValue }}">
    @endif
</div>
