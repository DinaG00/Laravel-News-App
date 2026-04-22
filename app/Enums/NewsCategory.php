<?php

namespace App\Enums;

enum NewsCategory: string
{
    case GENERAL = 'general';
    case TOP_NEWS = 'top news';
    case BUSINESS = 'business';
    case TECHNOLOGY = 'technology';
}
