<?php
$startMemory = 0;
$startMemory = memory_get_usage();

require 'Grabber.php';
require 'Dumper.php';

use bookin\geonames\Dumper;

$lang_param = ['ru'];
foreach($argv as $arg){
    if(strpos($arg, '--lang') !== false){
        $lang_param=explode(',', str_replace('--lang=', '', $arg));
    }
}

$dumper = new Dumper();
$grabber = new \bookin\geonames\Grabber();

echo "=======Countries=======\n\n";
$country_ids = [];
$county_values = [];
foreach($lang_param as $lang){
    $grabber->lang = $lang;

    foreach($grabber->getCountries() as $country){

        if(!in_array($country['id'], $country_ids))
            $country_ids[]=$country['id'];

        if(!isset($county_values[$country['id']])){
            $county_values[$country['id']] = [
                'country_id'=>(int)$country['id'],
                'name_'.$lang=>$country['title']
            ];
        }else{
            $county_values[$country['id']]['name_'.$lang]=$country['title'];
        }

    }
    sleep(.5);
}
$dumper->openDump(Dumper::TABLE_COUNTRY, $dumper->getCountryTable($lang_param));
$dumper->writeValues($county_values);
$dumper->closeDump();
unset($county_values);

#=====================#

echo "=======Regions=======\n\n";
$region_ids = [];
$dumper->openDump(Dumper::TABLE_REGION, $dumper->getRegionTable($lang_param));
foreach($country_ids as $country_id){

    $region_values = [];
    $region_ids[$country_id] = [];

    foreach($lang_param as $lang){
        $grabber->lang = $lang;

        foreach($grabber->getRegions($country_id) as $region){

            if(!in_array($region['id'], $region_ids[$country_id]))
                $region_ids[$country_id][]=$region['id'];

            if(!isset($region_values[$region['id']])){
                $region_values[$region['id']] = [
                    'region_id'=>(int)$region['id'],
                    'country_id'=>(int)$country_id,
                    'name_'.$lang=>$region['title']
                ];
            }else{
                $region_values[$region['id']]['name_'.$lang]=$region['title'];
            }

        }
        sleep(.5);
    }

    $dumper->writeValues($region_values);
}
$dumper->closeDump();
unset($region_values);

#=====================#

echo "=======Cities=======\n\n";
$dumper->openDump(Dumper::TABLE_CITY, $dumper->getCityTable($lang_param));
foreach($region_ids as $country_id=>$regions){

    foreach($regions as $region_id){
        $city_values = [];


        foreach($lang_param as $lang){
            $grabber->lang = $lang;

            $city_count = $grabber->getCityCount($country_id, $region_id);
            $offset = 0;
            while($offset<$city_count){
                try{

                    foreach($grabber->getCities($country_id, $region_id) as $city){

                        if(!isset($city_values[$city['id']])){
                            $city_values[$city['id']] = [
                                'city_id'=>(int)$city['id'],
                                'country_id'=>(int)$country_id,
                                'region_id'=>(int)$region_id,
                                'important'=>(int)$city['important'],
                                'name_'.$lang=>$city['title']
                            ];
                        }else{
                            $city_values[$city['id']]['name_'.$lang]=$city['title'];
                        }

                    }

                }catch (Exception $c){
                    echo "ERROR: ".$c->getMessage();
                }
                $offset+=1000;
                sleep(.5);
            }
        }

        $dumper->writeValues($city_values);
    }

}
$dumper->closeDump();
unset($city_values);

echo "Memory - ".(memory_get_usage() - $startMemory) . ' bytes' . PHP_EOL;