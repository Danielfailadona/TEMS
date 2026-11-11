@props([
    'record',
    'routeName',
    'label' => 'this record',
    'class' => 'btn btn-sm btn-outline-danger',
])

@can('delete', $record)
    <form method="POST" action="{{ route($routeName, $record) }}" class="d-inline js-confirm-delete"
          data-confirm-title="Delete {{ $label }}?"
          data-confirm="<strong>{{ $label }}</strong> will be archived before deletion. This action cannot be undone.">
        @csrf
        @method('DELETE')
        <button type="submit" class="{{ $class }}" title="Delete" aria-label="Delete {{ $label }}">
            @if (trim((string) $slot))
                {{ $slot }}
            @else
                <i class="bi bi-trash"></i>
            @endif
        </button>
    </form>
@endcan
