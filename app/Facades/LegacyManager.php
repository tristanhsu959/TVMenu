<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class LegacyManager extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Manager\LegacyManager::class;
    }
}