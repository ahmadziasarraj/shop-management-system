<?php

namespace App\Actions\Payments;

use App\Data\Payments\PaymentData;
use App\Enums\TransactionType;
use App\Models\Currency;
use App\Models\Payment;
use App\Services\Accounting\ExchangeRateService;
use App\Services\Payments\CustomerPaymentService;
use App\Services\Payments\HawalaSettlementService;
use App\Services\Payments\PaymentService;
use App\Services\Payments\SupplierPaymentService;

class CreatePaymentAction
{
    public function __construct(
        protected PaymentService $paymentService,
        protected ExchangeRateService $exchangeRateService,
        protected CustomerPaymentService $customerService,
        protected SupplierPaymentService $supplierService,
        protected HawalaSettlementService $hawalaService,
    ) {}
    public function execute(array $data): Payment
    {

        $baseCurrency = Currency::query()->where('is_base', true)->first();
        $currency = Currency::query()->findOrFail($data['currency_id']);
        $rate = $this->exchangeRateService->getRate(
            fromCurrencyCode: $baseCurrency->code,
            toCurrencyCode: $currency->code,
        );
        $baseAmount = $this->exchangeRateService->convert(
            amount: $data['amount'],
            fromCurrencyCode: $currency->code,
            toCurrencyCode: $baseCurrency->code,
        );
        $data['base_amount'] = $baseAmount;
        $data['exchange_rate'] = $rate;

        $paymentData = PaymentData::from($data);
        $payment = $this->paymentService->createPayment($paymentData);

        match ($paymentData->transaction_type) {
            TransactionType::ReceiveFromCustomer => $this->customerService->receiveFundFromCustomer($payment),
            TransactionType::RefundToCustomer => $this->customerService->refundToCustomer($payment),
            TransactionType::PayVendor => $this->supplierService->paySupplier($payment),
            TransactionType::ReceiveFromVendor => $this->supplierService->refundFromSupplier($payment),
            TransactionType::HawalaReceipt => $this->hawalaService->receiveMoney($payment),
            TransactionType::HawalaPayment => $this->hawalaService->payMoney($payment)
        };
        return $payment;
        // dd($data);
    }
}
