<?php
namespace bookin\geonames;

class ConsoleApp
{
    public $lang = ['ru'];

    /** @var Grabber $grabber */
    protected $grabber;

    protected $countries = [];
    protected $regions = [];
    protected $cities = [];

    private static $arguments = [
        'lang'
    ];

    public function __construct(){
        $this->checkAttributes($_SERVER['argv']);
    }

    public function run(){
        $this->scrubCountries();
        $this->scrubRegions();
        $this->scrubCities();
    }

    public function checkAttributes($argv){
        if($argv && is_array($argv)){
            foreach($argv as $arg){
                $key = null;
                $value = null;
                list($key, $value) = explode("=", str_replace("--", "", $arg));
                if(!in_array($key, self::$arguments)){
                    throw new \Exception("Unknown parameter - ".$key);
                }
                if(empty($value)){
                    throw new \Exception("Value can not be empty");
                }

                switch($key){
                    case 'lang':
                        $this->lang = explode(',', $value);
                        break;
                }
            }
        }
    }

    public function scrubCountries(){
        $dumper = $this->getDumper();
        $grabber = $this->getGrabber();
        $country_ids = [];
        $county_values = [];
        foreach($this->lang as $lang){
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
        $dumper->openDump(Dumper::TABLE_COUNTRY, $dumper->getCountryTable($this->lang));
        $dumper->writeValues($county_values);
        $dumper->closeDump();
        $this->countries = $country_ids;
        unset($county_values, $country_ids);
    }

    public function scrubRegions(){
        $dumper = $this->getDumper();
        $grabber = $this->getGrabber();
        $region_ids = [];
        $dumper->openDump(Dumper::TABLE_REGION, $dumper->getRegionTable($this->lang));
        foreach($this->countries as $country_id){

            $region_values = [];
            $region_ids[$country_id] = [];

            foreach($this->lang as $lang){
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
        $this->regions = $region_ids;
        unset($region_values, $region_ids);
    }

    public function scrubCities(){
        $dumper = $this->getDumper();
        $grabber = $this->getGrabber();
        $dumper->openDump(Dumper::TABLE_CITY, $dumper->getCityTable($this->lang));
        foreach($this->regions as $country_id=>$regions){

            foreach($regions as $region_id){
                $city_values = [];

                foreach($this->lang as $lang){
                    $grabber->lang = $lang;

                    $city_count = $grabber->getCityCount($country_id, $region_id);
                    $offset = 0;
                    while($offset<$city_count){
                        self::getCities($country_id, $region_id, $lang, $offset, $city_values, $grabber);
                        $offset+=1000;
                        sleep(.5);
                    }
                }

                $dumper->writeValues($city_values);
            }

        }
        $dumper->closeDump();
        unset($city_values);
    }

    /**
     * @param $country_id
     * @param $region_id
     * @param $lang
     * @param $offset
     * @param $city_values
     * @param Grabber $grabber
     * @throws \Exception
     */
    protected static function getCities($country_id, $region_id, $lang, $offset, &$city_values, $grabber){
        try{

            foreach($grabber->getCities($country_id, $region_id, $offset) as $city){

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

        }catch (\Exception $c){
            if(in_array((int)$c->getCode(), [1, 10, 6])){
                sleep(2);
                get_cities($country_id, $region_id, $lang, $offset, $city_values, $grabber);
            }
            throw new \Exception($c->getMessage(), $c->getCode());
        }
    }

    /**
     * @return Dumper
     */
    public function getDumper(){
        return new Dumper();
    }

    /**
     * @return Grabber
     */
    public function getGrabber(){
        if(!$this->grabber instanceof Grabber){
            $this->grabber = new Grabber();
        }
        return $this->grabber;
    }
}