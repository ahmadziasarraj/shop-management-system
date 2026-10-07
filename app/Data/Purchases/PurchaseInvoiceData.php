<?php

namespace App\Data\Purchases;

use App\Enums\PaymentMethod;

readonly class PurchaseInvoiceData
{
    public function __construct( 
        public string $party_id,
        public string $currency_id,
        public string $currency_code,

        public PaymentMethod $payment_method,

        public ?string $hawala_office_id,

        public ?string $original_invoice_number,

        public string $date,

        public float $discount_amount,

        public float $paid_amount,

        public array $purchased_products,
    ) {}

     public static function from(array $data): self
    {
        return new self(
            party_id: $data['party_id'] ?? null,

            currency_id: $data['currency_id'],

            currency_code: $data['currency_code'],

            payment_method: $data['payment_method'] instanceof PaymentMethod
                ? $data['payment_method']
                : PaymentMethod::from($data['payment_method']),

            hawala_office_id: $data['hawala_office_id'] ?? null,

            original_invoice_number:
                $data['original_invoice_number'] ?? null,

            date: $data['date'],

            discount_amount:
                (float) ($data['discount_amount'] ?? 0),

            paid_amount:
                (float) ($data['paid_amount'] ?? 0),

            purchased_products:
                $data['purchased_products'] ?? [],
        );
    }
}
