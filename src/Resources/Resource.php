<?php

declare(strict_types=1);

namespace Paybeta\Resources;

use Paybeta\HttpClient;

abstract class Resource
{
    public function __construct(protected readonly HttpClient $http)
    {
    }
}
