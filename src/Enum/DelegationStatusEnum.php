<?php

namespace App\Enum;

enum DelegationStatusEnum: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case REVOKED = 'revoked';
}
