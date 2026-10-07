<?php

namespace App\Data\Sales;

use App\Enums\PaymentMethod;

readonly class SaleInvoiceData
{
    public function __construct(
        public string $party_id,
        public string $currency_id,
        public string $currency_code,
        public PaymentMethod $payment_method,
        public ?string $hawala_office_id,
        public string $date,
        public ?string $notes,
        public float $paid_amount,
        public array $sold_products,
    ) {
    }

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
            date: $data['date'],
            paid_amount: (float) ($data['paid_amount'] ?? 0),
            sold_products: $data['sold_products'] ?? [],
            notes: $data['notes'] ?? '',
        );
    }
}