<?php


class config {

    
    public static function getConfig($path){
        $config = $GLOBALS['config'];
        $arrayPath = explode('/', $path);

        foreach($arrayPath as $level){
            $config = $config[$level];
            }
            
            return $config;
        }
        
}
    
