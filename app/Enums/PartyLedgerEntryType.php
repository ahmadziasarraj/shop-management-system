<?php

namespace App\Enums;

enum PartyLedgerEntryType: string
{
    case OpeningBalance = 'opening_balance';
    case Sale = 'sale';
    case CustomerPayment = 'customer_payment';
    case Purchase = 'purchase';
    case SupplierPayment = 'supplier_payment';
    case SalesReturn = 'sales_return';
    case PurchaseReturn = 'purchase_return';
    case AdvanceReceived = 'advance_received';
    case AdvancePaid = 'advance_paid';
    case DebitNote = 'debit_note';
    case CreditNote = 'credit_note';
    case Adjustment = 'adjustment';
    case Settlement = 'settlement';
    case HawalaPayment = 'hawala_payment';
    case LandedCostPayment = 'landed_cost_payment';
}
