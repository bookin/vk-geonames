<?php
namespace bookin\geonames;

class Grabber
{
    public $lang = 'ru';

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getCountryCount(){
        $method = 'getCountries';
        $response = $this->request($method);
        return $response['response']['count'];
    }

    /**
     * @param int $offset
     * @return \Generator
     * @throws \Exception
     */
    public function getCountries($offset=0){
        $method = 'getCountries';
        $response = $this->request($method, [], $offset);

        if($response['response']['count']>0){
            foreach($response['response']['items'] as $country){
                yield $country;
            }
        }
    }

    /**
     * @param $country_id
     * @return mixed
     * @throws \Exception
     */
    public function getRegionCount($country_id){
        $method = 'getRegions';
        $response = $this->request($method, ['country_id'=>$country_id]);
        return $response['response']['count'];
    }

    /**
     * @param $country_id
     * @param int $offset
     * @return \Generator
     * @throws \Exception
     */
    public function getRegions($country_id, $offset=0){
        $method = 'getRegions';
        $response = $this->request($method, ['country_id'=>$country_id], $offset);
        if($response['response']['count']>0){
            foreach($response['response']['items'] as $region){
                yield $region;
            }
        }
    }

    /**
     * @param $country_id
     * @param $region_id
     * @return mixed
     * @throws \Exception
     */
    public function getCityCount($country_id, $region_id){
        $method = 'getCities';
        $response = $this->request($method, ['country_id'=>$country_id, 'region_id'=>$region_id]);
        return $response['response']['count'];
    }

    /**
     * @param $country_id
     * @param $region_id
     * @param int $offset
     * @return \Generator
     * @throws \Exception
     */
    public function getCities($country_id, $region_id, $offset=0){
        $method = 'getCities';
        $response = $this->request($method, ['country_id'=>$country_id, 'region_id'=>$region_id], $offset);
        if($response['response']['count']>0){
            foreach($response['response']['items'] as $city){
                yield $city;
            }
        }
    }

    /**
     * @param $method
     * @param array $params
     * @param int $offset
     * @return mixed
     * @throws \Exception
     */
    public function request($method, $params=[], $offset=0){
        $headerOptions = array(
            'http' => array(
                'method' => "GET"
            )
        );
        $getParams = [];
        foreach($params as $key=>$val){
            $getParams[] = "$key=$val";
        }
        if($this->lang){
            $getParams[]='lang='.$this->lang;
        }
        $methodUrl = "http://api.vk.com/method/database.$method?v=5.5&need_all=1&count=1000&offset=$offset".($getParams?'&'.implode('&',$getParams):'');
        $streamContext = stream_context_create($headerOptions);
        $json = file_get_contents($methodUrl, false, $streamContext);
        $arr = json_decode($json, true);

        echo $methodUrl."\n";
        echo 'Response count - '.$arr['response']['count']."\n";

        if(isset($arr['error'])){
            throw new \Exception("Api error - ".$arr['error']['error_msg']."; Url -".$methodUrl);
        }

        if(!isset($arr['response']['count'])){
            throw new \Exception("Wrong response");
        }

        return $arr;
    }
}