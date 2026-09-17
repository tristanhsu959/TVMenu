<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class LocalLegacyManager extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Manager\LocalLegacyManager::class;
    }
}