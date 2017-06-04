<?php
/**
 * Created by PhpStorm.
 * User: mc
 * Date: 09.05.17
 * Time: 19:54
 */
session_start();

$GLOBALS["config"] = array(
    "mysql" => array(
        "host" => "localhost",
        "db_name" => "bookList",
        "user" => "root",
        "password" => "test"
    )
);

spl_autoload_register( function($className){
    require_once(__DIR__.'/../classes/'.$className.'.php');
});

