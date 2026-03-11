<?php

namespace App\Enums;

enum CdnMode: string
{
    case ORIGIN = 'origin';
    case STORAGE = 'storage';
}
