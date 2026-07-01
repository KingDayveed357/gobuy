<?php

namespace App\Modules\Catalog\Mail;

use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BackInStockMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly ProductVariant $variant) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "{$this->variant->product->name} is back in stock");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.catalog.back-in-stock', with: [
            'product' => $this->variant->product,
            'variant' => $this->variant,
        ]);
    }
}
