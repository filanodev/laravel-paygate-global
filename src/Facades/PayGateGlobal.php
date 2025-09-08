<?php

namespace PayGate\LaravelPayGateGlobal\Facades;

use Illuminate\Support\Facades\Facade;

class PayGateGlobal extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'paygate-global';
    }
}