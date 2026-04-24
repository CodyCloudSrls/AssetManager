@props([
    'route' => route('api.tickets.index'),
    'name' => 'tickets',
    'presenter' => \App\Presenters\TicketPresenter::dataTableLayout(),
    'fixed_right_number' => 1,
    'table_header' => trans('general.tickets'),
])

@can('view', \App\Models\Ticket::class)
    <x-slot:table_header>
        {{ $table_header }}
    </x-slot:table_header>

    <x-table
        :$name
        :$presenter
        :$fixed_right_number
        show_column_search="true"
        show_advanced_search="true"
        buttons="ticketButtons"
        api_url="{{ $route }}"
        export_filename="export-{{ str_slug($name) }}-tickets-{{ date('Y-m-d') }}"
    />
@endcan
