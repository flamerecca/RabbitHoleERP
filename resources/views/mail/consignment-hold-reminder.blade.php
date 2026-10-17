<x-mail::message>
# {{ __('Consignment holds waiting for pickup') }}

{{ __(':warehouse holds the goods of the consignment holds below for customers. They have been kept for :days days or more and are about to expire.', ['warehouse' => $warehouse->name, 'days' => \App\Console\Commands\SendConsignmentHoldReminders::REMINDER_AFTER_DAYS]) }}

<x-mail::table>
| {{ __('Consignment hold') }} | {{ __('Customer') }} | {{ __('Shipment') }} | {{ __('Held until') }} | {{ __('Days left') }} |
| :--- | :--- | :--- | :--- | ---: |
@foreach ($holds as $hold)
| {{ $hold->hold_no }} | {{ $hold->shipment->salesOrder->customer->name ?? '' }} | {{ $hold->shipment->shipment_no }} | {{ $hold->held_until->toDateString() }} | {{ (int) today()->diffInDays($hold->held_until, false) }} |
@endforeach
</x-mail::table>

{{ __('Please contact the customers to pick up the goods. Holds that are not picked up by the last day become overdue.') }}
</x-mail::message>
