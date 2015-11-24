<?php
namespace bookin\geonames;
class ConsoleApp
{
    private static $arguments = [
        'lang'
    ];

    public function __construct(){

    }

    public function checkAttributes($argv){
        if($argv && is_array($argv)){
            foreach($argv as $arg){
                $key = $value = null;
                list($key, $value) = explode("=", str_replace("--", "", $arg));
                if(!in_array($key, self::$arguments)){
                    throw new \Exception("Unknown parameter - ".$key);
                }
                if(empty($value)){
                    throw new \Exception("Value can not be empty");
                }
            }
        }
    }

}