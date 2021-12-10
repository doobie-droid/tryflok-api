<?php

namespace App\Constants;

class Constants
{
    // cache
    public const MONTH_CACHE_TIME = 259200;
    public const DAY_CACHE_TIME = 86400;
    public const HOUR_CACHE_TIME = 3600;
    public const MINUTE_CACHE_TIME = 60;
    public const SECOND_CACHE_TIME = 1;

    // limits
    public const MAX_ITEMS_LIMIT = 100;

    // revenue share
    public const NORMAL_CREATOR_CHARGE = .15;
    public const NON_PROFIT_CREATOR_CHARGE = .10;
    public const TIPPING_CHARGE = .15;

    //trending metrics
    public const TRENDING_VIEWS_WEIGHT = .1;
    public const TRENDING_CONTENT_SUBSCRIBERS_WEIGHT = .1;
    public const TRENDING_PURCHASES_WEIGHT = 2;
    public const TRENDING_REVIEWS_WEIGHT = .2;

    public const TRENDING_COLLECTION_WEIGHT = .1;
    public const TRENDING_COLLECTION_CONTENT_WEIGHT = .1;
    public const TRENDING_COLLECTION_SUBSCRIBERS_WEIGHT = 3;
}
