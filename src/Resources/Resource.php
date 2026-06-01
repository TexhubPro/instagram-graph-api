<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\Resources;

use TexHub\InstagramGraphApi\Config;
use TexHub\InstagramGraphApi\Http\HttpClient;

abstract class Resource
{
    public function __construct(
        protected readonly HttpClient $http,
        protected readonly Config $config,
    ) {
    }
}
