@extends('layouts/layout')

@section('content')

<main class="admin-workspace">
    <section class="card admin-record-card">
        @include('admin.records._header')

        <div class="admin-table-wrap">
            <table class="admin-table admin-crud-table">
                <thead>
                    <tr>
                        @foreach ($config['columns'] as $column)
                            <th>{{ $column['label'] }}</th>
                        @endforeach
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            @foreach ($config['columns'] as $column)
                                @php
                                    $value = data_get($record, $column['key']);
                                    $format = $column['format'] ?? null;
                                @endphp
                                <td>
                                    @if ($format === 'money')
                                        {{ number_format((float) $value, 2, '.', '') }} MDL
                                    @elseif ($format === 'datetime' && $value)
                                        {{ $value->format('d.m.Y H:i') }}
                                    @else
                                        {{ filled($value) ? $value : '—' }}
                                    @endif
                                </td>
                            @endforeach
                            <td>
                                <div class="admin-row-actions">
                                    <a href="{{ route('admin.records.show', [$resource, $record]) }}" title="Просмотреть"><i class="fa-solid fa-eye"></i></a>
                                    <a href="{{ route('admin.records.edit', [$resource, $record]) }}" title="Изменить"><i class="fa-solid fa-pen"></i></a>
                                    <form method="POST" action="{{ route('admin.records.destroy', [$resource, $record]) }}" onsubmit="return confirm('Удалить эту запись? Связанные данные также могут быть удалены.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Удалить"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($config['columns']) + 1 }}">Записей пока нет.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-pagination">{{ $records->links() }}</div>
    </section>
</main>

@endsection
