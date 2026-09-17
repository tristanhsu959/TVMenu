<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class PurchaseManager extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Manager\PurchaseManager::class;
    }
}