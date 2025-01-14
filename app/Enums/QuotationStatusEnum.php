<?php

namespace App\Enums;

enum QuotationStatusEnum: string
{
    case new = 'new';
    case email_sent = 'email_sent';
    case accepted = 'accepted';
    case rejected = 'rejected';
}
