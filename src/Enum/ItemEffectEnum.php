<?php

namespace App\Enum;

enum ItemEffectEnum: string
{
    case HUNGER = 'hunger';
    case HAPPINESS = 'happiness';
    case HEALTH = 'health';
    case ENERGY = 'energy';
}
