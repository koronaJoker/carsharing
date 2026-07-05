@extends('layouts/layout')

@section('content')

@php
    $isEdit = $record !== null;
@endphp

<main class="admin-workspace">
    <section class="card admin-record-card admin-form-card">
        @include('admin.records._header')

        <form method="POST" action="{{ $isEdit ? route('admin.records.update', [$resource, $record]) : route('admin.records.store', $resource) }}" class="admin-record-form">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="admin-form-grid">
                @foreach ($config['fields'] as $field)
                    @php
                        $fieldName = $field['name'];
                        $fieldType = $field['type'];
                        $recordValue = $isEdit && ! ($field['virtual'] ?? false) ? data_get($record, $fieldName) : null;
                        if ($recordValue instanceof \Carbon\CarbonInterface) {
                            $recordValue = $recordValue->format('Y-m-d\TH:i');
                        }
                        $value = $fieldType === 'password' ? '' : old($fieldName, $recordValue);
                        $choices = $field['choices'] ?? ($options[$fieldName] ?? []);
                        $optionalFields = ['email_verified_at', 'end_time', 'paid_at', 'driver_license', 'image_url'];
                        $isRequired = ! in_array($fieldName, $optionalFields, true) && ! ($fieldType === 'password' && $isEdit);
                    @endphp

                    <div class="form-group {{ $fieldType === 'textarea' ? 'admin-form-wide' : '' }}">
                        <label for="{{ $fieldName }}">{{ $field['label'] }}</label>

                        @if ($fieldType === 'select')
                            <select id="{{ $fieldName }}" name="{{ $fieldName }}" class="btn-min" @required($isRequired)>
                                <option value="">Выберите значение</option>
                                @foreach ($choices as $choiceValue => $choiceLabel)
                                    <option value="{{ $choiceValue }}" {{ (string) $value === (string) $choiceValue ? 'selected' : '' }}>{{ $choiceLabel }}</option>
                                @endforeach
                            </select>
                        @elseif ($fieldType === 'textarea')
                            <textarea id="{{ $fieldName }}" name="{{ $fieldName }}" class="btn-min" rows="5" @required($isRequired)>{{ $value }}</textarea>
                        @else
                            <input
                                id="{{ $fieldName }}"
                                name="{{ $fieldName }}"
                                type="{{ $fieldType }}"
                                class="btn-min"
                                value="{{ $value }}"
                                @if (isset($field['step'])) step="{{ $field['step'] }}" @endif
                                @required($isRequired)
                            >
                        @endif

                        @if ($fieldType === 'password' && $isEdit)
                            <small>Оставьте пустым, чтобы сохранить текущий пароль.</small>
                        @endif

                        @error($fieldName)
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>
                @endforeach
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="btn"><i class="fa-solid fa-floppy-disk"></i> {{ $isEdit ? 'Сохранить' : 'Создать' }}</button>
                <a href="{{ route('admin.records.index', $resource) }}" class="btn btn-secondary">Отмена</a>
            </div>
        </form>
    </section>
</main>

@endsection
