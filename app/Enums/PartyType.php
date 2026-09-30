<?php

namespace App\Enums;

enum PartyType: string
{
    case Vendor = 'vendor';
    case Customer = 'customer';
    case VendorCustomer = 'vendor\customer';
    case HawalaOffice = 'hawala_office';
}
