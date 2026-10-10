<?php

namespace App\Services\Payments;

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
                'Payment made to %s against sale invoice #%s.',
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
    private function generatePaymentNumber()
    {
        return now()->format('YmdHis') . '-' . strtoupper(Str::random(5));
    }
    // public function paySupplier(
    //     PurchaseInvoice $invoice,
    // ): Payment {
    //     return DB::transaction(function () use (
    //         $invoice,
    //     ) {
    //         $notes = sprintf(
    //             'Payment made to %s against purchase invoice #%s.',
    //             $invoice->party->name,
    //             $invoice->system_invoice_number
    //         );

    //         if ($invoice->hawalaOffice) {
    //             $notes .= sprintf(
    //                 ' Via Hawala Office: %s.',
    //                 $invoice->hawalaOffice->name
    //             );
    //         }

    //         $payment = Payment::create([
    //             'party_id' => $invoice->party_id,
    //             'currency_id' => $invoice->currency_id,
    //             'reference_type' => PurchaseInvoice::class,
    //             'reference_id' => $invoice->id,

    //             'payment_number' => $this->generatePaymentNumber(),
    //             'payment_method' => $invoice->payment_method,
    //             'amount' => $invoice->paid_amount,
    //             'base_amount' => $invoice->base_paid_amount,
    //             'exchange_rate' => $invoice->exchange_rate,

    //             'payment_date' => now(),
    //             'notes' => $notes,
    //         ]);
    //         return $payment;
    //     });
    // }

    //         // if ($allocations) {

    //         //     foreach ($allocations as $allocation) {

    //         //         $this->allocationService->allocate(
    //         //             $payment,
    //         //             $allocation['document'],
    //         //             $allocation['payment_amount'],
    //         //             $allocation['document_amount'],
    //         //             $allocation['base_amount'],
    //         //             $allocation['exchange_rate'] ?? null,
    //         //         );
    //         //     }
    //         // }

    //         return $payment;
    //     });
    // }
}
