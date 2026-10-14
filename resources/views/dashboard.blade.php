<x-layouts::app :title="__('Dashboard')">
    <livewire:pages::teams.pending-invitations-modal />

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:pages::dashboard.stock-overview />

        <livewire:pages::dashboard.replenishment-suggestions />
        <livewire:pages::dashboard.recent-stock-movements />

        <livewire:pages::dashboard.open-orders />
    </div>
</x-layouts::app>
