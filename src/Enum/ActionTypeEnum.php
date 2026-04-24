<?php

namespace App\Enum;

enum ActionTypeEnum: string
{
    case FEED = 'feed';
    case PLAY = 'play';
    case HEAL = 'heal';
    case SLEEP = 'sleep';
}
