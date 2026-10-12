<?php

namespace App\Services\Payments;

use App\Data\Payments\PaymentData;
use App\Models\LandedCost;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\SaleInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function purchaseInvoicePayment(
        PurchaseInvoice $invoice
    ): Payment {
        return DB::transaction(function () use (
            $invoice,
        ) {
            $notes = sprintf(
                'Payment made to %s against purchase invoice #%s.',
                $invoice->party->name,
                $invoice->system_invoice_number
            );

            if ($invoice->hawalaOffice) {
                $notes .= sprintf(
                    ' Via Hawala Office: %s.',
                    $invoice->hawalaOffice->name
                );
            }

            $payment = Payment::create([
                'party_id' => $invoice->party_id,
                'currency_id' => $invoice->currency_id,
                'reference_type' => PurchaseInvoice::class,
                'reference_id' => $invoice->id,

                'payment_number' => $this->generatePaymentNumber(),
                'payment_method' => $invoice->payment_method,
                'amount' => $invoice->paid_amount,
                'base_amount' => $invoice->base_paid_amount,
                'exchange_rate' => $invoice->exchange_rate,

                'payment_date' => now(),
                'notes' => $notes,
            ]);
            return $payment;
        });
    }
    public function landedCostPayment(
        LandedCost $landedCost
    ): Payment {
        return DB::transaction(function () use (
            $landedCost,
        ) {
            $notes = sprintf(
                'Payment made to %s against purchase invoice #%s. Reason: %s. ',
                $landedCost->party->name,
                $landedCost->purchaseInvoice->system_invoice_number,
                $landedCost->description
            );

            if ($landedCost->hawalaOffice) {
                $notes .= sprintf(
                    ' Via Hawala Office: %s.',
                    $landedCost->hawalaOffice->name
                );
            }

            $payment = Payment::create([
                'party_id' => $landedCost->party_id,
                'currency_id' => $landedCost->currency_id,
                'reference_type' => LandedCost::class,
                'reference_id' => $landedCost->id,

                'payment_number' => $this->generatePaymentNumber(),
                'payment_method' => $landedCost->payment_method,
                'amount' => $landedCost->amount,
                'base_amount' => $landedCost->base_amount,
                'exchange_rate' => $landedCost->exchange_rate,

                'payment_date' => now(),
                'notes' => $notes,
            ]);
            return $payment;
        });
    }

    public function saleInvoicePayment(SaleInvoice $invoice): Payment
    {
        return DB::transaction(function () use (
            $invoice,
        ) {
            $notes = sprintf(
                'Payment paid by %s against sale invoice #%s.',
                $invoice->party->name,
                $invoice->system_invoice_number
            );

            if ($invoice->hawalaOffice) {
                $notes .= sprintf(
                    ' Via Hawala Office: %s.',
                    $invoice->hawalaOffice->name
                );
            }

            $payment = Payment::create([
                'party_id' => $invoice->party_id,
                'currency_id' => $invoice->currency_id,
                'reference_type' => SaleInvoice::class,
                'reference_id' => $invoice->id,

                'payment_number' => $this->generatePaymentNumber(),
                'payment_method' => $invoice->payment_method,
                'amount' => $invoice->paid_amount,
                'base_amount' => $invoice->base_paid_amount,
                'exchange_rate' => $invoice->exchange_rate,

                'payment_date' => now(),
                'notes' => $notes,
            ]);
            return $payment;
        });
    }

    public function createPayment(PaymentData $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $payment = Payment::create([
                'party_id' => $data->party_id,
                'currency_id' => $data->currency_id,
                'hawala_office_id' => $data->hawala_office_id,
                'reference_type' => Payment::class,
                'reference_id' => Str::ulid(),
                'payment_number' => $this->generatePaymentNumber(),
                'payment_method' => $data->payment_method,
                'transaction_type' => $data->transaction_type,
                'amount' => $data->amount,
                'base_amount' => $data->base_amount,
                'exchange_rate' => $data->exchange_rate,
                'payment_date' => $data->payment_date,
                'notes' => $data->notes,
            ]);

            $payment->update([
                'reference_id' => $payment->id
            ]);

            return $payment;
        });
    }
    private function generatePaymentNumber()
    {
        return now()->format('YmdHis') . '-' . strtoupper(Str::random(5));
    }
}
