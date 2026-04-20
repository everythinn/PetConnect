<?php

namespace App\Enum;

enum ItemTypeEnum: string
{
    case FOOD = 'food';
    case TOY = 'toy';
    case MEDICINE = 'medicine';
    case COSMETIC = 'cosmetic';
}
