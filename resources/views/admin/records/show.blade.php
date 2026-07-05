@extends('layouts/layout')

@section('content')

<main class="admin-workspace">
    <section class="card admin-record-card admin-show-card">
        @include('admin.records._header')

        <div class="admin-detail-list">
            <div class="admin-detail-item"><span>ID</span><strong>{{ $record->id }}</strong></div>
            @foreach ($config['fields'] as $field)
                @continue($field['virtual'] ?? false)
                @php
                    $value = data_get($record, $field['name']);
                    $choices = $field['choices'] ?? ($options[$field['name']] ?? []);
                    if (is_scalar($value) && array_key_exists($value, $choices)) {
                        $value = $choices[$value];
                    } elseif ($value instanceof \Carbon\CarbonInterface) {
                        $value = $value->format('d.m.Y H:i');
                    }
                @endphp
                <div class="admin-detail-item">
                    <span>{{ $field['label'] }}</span>
                    <strong>{{ filled($value) ? $value : '—' }}</strong>
                </div>
            @endforeach
            <div class="admin-detail-item"><span>Создано</span><strong>{{ $record->created_at?->format('d.m.Y H:i') ?? '—' }}</strong></div>
            <div class="admin-detail-item"><span>Обновлено</span><strong>{{ $record->updated_at?->format('d.m.Y H:i') ?? '—' }}</strong></div>
        </div>

        <div class="admin-form-actions">
            <a href="{{ route('admin.records.edit', [$resource, $record]) }}" class="btn"><i class="fa-solid fa-pen"></i> Изменить</a>
            <a href="{{ route('admin.records.index', $resource) }}" class="btn btn-secondary">К списку</a>
        </div>
    </section>
</main>

@endsection
