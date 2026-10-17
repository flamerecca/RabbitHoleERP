<?php

namespace App\Mail;

use App\Models\ConsignmentHold;
use App\Models\Warehouse;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

class ConsignmentHoldReminder extends Mailable
{
    /**
     * Create a reminder of the consignment holds of a warehouse that are waiting to be picked up.
     *
     * @param  Collection<int, ConsignmentHold>  $holds
     */
    public function __construct(public Warehouse $warehouse, public Collection $holds)
    {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Consignment holds waiting for pickup'));
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(markdown: 'mail.consignment-hold-reminder');
    }
}
