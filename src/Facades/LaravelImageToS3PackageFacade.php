<?php

namespace jeremybrammer\laravelimagetos3package\Facades;

use Illuminate\Support\Facades\Facade;

class LaravelImageToS3PackageFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'laravelimagetos3package';
    }
}
