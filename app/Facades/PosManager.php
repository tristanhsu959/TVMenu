<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class PosManager extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Manager\PosManager::class;
    }
}