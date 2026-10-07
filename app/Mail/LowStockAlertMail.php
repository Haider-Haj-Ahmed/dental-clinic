<?php

namespace App\Mail;

use App\Models\InventoryItem;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

class LowStockAlertMail extends BaseMail
{
    public function __construct(private readonly Collection $items) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Low Stock Alert — ' . $this->items->count() . ' item(s) need reordering',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.clinical.low-stock-alert',
            with: [
                'items'    => $this->items,
                'branding' => $this->branding(),
            ],
        );
    }
}
