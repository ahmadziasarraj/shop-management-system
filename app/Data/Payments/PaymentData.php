<?php

namespace App\Data\Payments;

use App\Enums\PaymentMethod;
use App\Enums\TransactionType;

readonly class PaymentData
{
    public function __construct(
        public string $party_id,
        public string $currency_id,
        public PaymentMethod $payment_method,
        public TransactionType $transaction_type,
        public ?string $hawala_office_id,
        public string $payment_date,
        public string $notes,
        public float $amount,
        public ?float $base_amount,
        public ?float $exchange_rate,

    ) {}

    public static function from(array $data): self
    {
        return new self(
            party_id: $data['party_id'],
            currency_id: $data['currency_id'],
            payment_method: $data['payment_method'] instanceof PaymentMethod
                ? $data['payment_method']
                : PaymentMethod::from($data['payment_method']),
            transaction_type: $data['transaction_type'] instanceof TransactionType
                ? $data['transaction_type']
                : TransactionType::from($data['transaction_type']),
            hawala_office_id: $data['hawala_office_id'] ?? null,
            payment_date: $data['payment_date'],
            amount: (float) ($data['amount'] ?? 0),
            notes: $data['notes'],
            base_amount: (float) ($data['base_amount'] ?? $data['amount']),
            exchange_rate: (float) ($data['exchange_rate'] ?? 1)
        );
    }
}
