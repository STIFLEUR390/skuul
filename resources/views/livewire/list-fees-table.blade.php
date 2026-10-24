<april:card><slot:title>{{ __('Fees') }}</slot:title><slot:description>{{ __('Define the charges used to build invoices.') }}</slot:description><slot:content><div wire:key="{{ $id }}-{{ $this->tableRevision }}"><april:data-table id="{{ $id }}" :data="$data" :columns="$columns" :pagination="$pagination" :per-page-options="$perPageOptions" row-key="{{ $rowKey }}" :searchable="$searchable" @query-change="$wire.updateTable($event.detail)"><slot:empty><div class="space-y-1"><p class="font-medium text-foreground">{{ __('No fees yet') }}</p><p>{{ __('Add a fee after setting up its category.') }}</p></div></slot:empty><slot:actions>
    <x-table-actions :items="array_filter([
        $canEditFees ? ['label' => 'Edit fee', 'icon' => 'settings', 'url' => 'edit_url'] : null,
        $canDeleteFees ? ['label' => 'Delete fee', 'icon' => 'trash-2', 'method' => 'deleteFee', 'type' => 'action', 'confirm' => 'Delete :name? A fee that is on an invoice cannot be deleted.', 'names' => 'row.name', 'when' => 'row.fee_invoice_records_count === 0'] : null,
    ])" />
</slot:actions></april:data-table></div></slot:content></april:card>
