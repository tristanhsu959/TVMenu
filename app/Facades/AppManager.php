<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class AppManager extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Manager\AppManager::class;
    }
}