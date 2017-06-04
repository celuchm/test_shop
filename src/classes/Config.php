<?php

/**
 * Created by PhpStorm.
 * User: mc
 * Date: 10.05.17
 * Time: 08:23
 */
class Config
{

    public static function get_config($path){

        $paramArray = explode("/", $path);
        $config = $GLOBALS["config"];

        foreach($paramArray as $configName){
            $config = $config[$configName];
        }
        return $config;
    }


}