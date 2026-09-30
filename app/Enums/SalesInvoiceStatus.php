<?php

namespace App\Enums;

enum SalesInvoiceStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Voided = 'voided';
}
