@props(['id', 'ajaxUrl'])

{{--
    Reusable Yajra/DataTables.net table shell. The page's own JS (see
    resources/js/data-table.js) initializes DataTables on #{{ $id }} against
    a server-side Yajra endpoint; this component only renders the markup
    shell so every DataTable-backed page shares the same look as the rest
    of the app's tables.
--}}
<div class="flex flex-col">
    <div class="overflow-x-auto">
        <div class="min-w-full inline-block align-middle">
            <div class="overflow-hidden">
                <table class="min-w-full divide-y divide-default-200" data-ajax-url="{{ $ajaxUrl }}" id="{{ $id }}">
                    <thead class="bg-default-150">
                    <tr class="text-sm font-normal text-default-700 whitespace-nowrap">
                        {{ $head }}
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
