<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class StoreManager extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Manager\StoreManager::class;
    }
}