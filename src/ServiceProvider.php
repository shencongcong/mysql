<?php
/**
 * Created by PhpStorm.
 * User: danielshen
 * Date: 2019/7/15
 * Time: 15:50
 */

namespace Shencongcong\Mysql;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{

    public function boot()
    {

    }

    public function register()
    {
        $this->app->singleton(Mysql::class,function (){
            return Mysql::getDbInstance();
        });

        $this->app->singleton(CoMysql::class,function (){
            return CoMysql::getDbInstance();
        });
    }
}