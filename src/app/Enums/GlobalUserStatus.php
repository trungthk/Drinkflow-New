<?php

namespace App\Enums;

enum GlobalUserStatus: string
{
    case Active = 'active';
    case Blocked = 'blocked';
    case Disabled = 'disabled';
}
