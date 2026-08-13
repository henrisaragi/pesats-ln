@props([
    'id',
    'title' => null,
    'message' => null,
    'confirmLabel' => null,
])

<x-dialog-modal :confirm-label="$confirmLabel" :id="$id" :message="$message" :title="$title" type="error">
    {{ $slot }}
</x-dialog-modal>
