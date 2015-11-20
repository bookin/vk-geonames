<?php
namespace bookin\geonames;

/**
 * Class Dumper
 * @package bookin\geonames
 *
 * @property string $prefix
 */
class Dumper
{
    const TABLE_COUNTRY = 'countries';
    const TABLE_REGION = 'regions';
    const TABLE_CITY = 'cities';

    protected $_lang = ['ru'];
    protected $_prefix = '';
    private $_fp;
    private $_last_file_name = '';
    private $is_wrote = false;

    public function __construct($lang=[]){
        if(!empty($lang)){
            $this->setLang($lang);
        }
    }

    /**
     * @param $table
     * @return string
     * @throws \Exception
     */
    public function getTableName($table){
        $tables = [
            self::TABLE_COUNTRY,
            self::TABLE_REGION,
            self::TABLE_CITY
        ];
        if(in_array($table, $tables)){
            return $this->getPrefix().$table;
        }else{
            throw new \Exception('Table does not exit');
        }
    }

    /**
     * @param $table_name
     * @return string
     * @throws \Exception
     */
    public function getTableDump($table_name){
        switch($table_name){
            case self::TABLE_COUNTRY :
                return $this->getCountryTable();
                break;
            case self::TABLE_REGION :
                return $this->getRegionTable();
                break;
            case self::TABLE_CITY :
                return $this->getCityTable();
                break;
            default:
                throw new \Exception("Table does not exit");
        }
    }

    public function getCountryTable($lang=[]){
        $table_name = $this->getTableName(self::TABLE_COUNTRY);
        $rows = [
            "`country_id` int(11) NOT NULL",
        ];
        $keys = [
            "PRIMARY KEY  (`country_id`)"
        ];
        return $this->generateTableSql($table_name, $rows, $keys, $lang);
    }

    public function getRegionTable($lang=[]){
        $table_name = $this->getTableName(self::TABLE_REGION);
        $rows = [
            "`region_id` int(11) NOT NULL",
            "`country_id` int(11) NOT NULL",

        ];
        $keys = [
            "PRIMARY KEY  (`region_id`)",
            "KEY `in_region__country_id` (`country_id`)",
            "FOREIGN KEY fk_country_country_id__region_country_id (`country_id`) REFERENCES `".$this->getTableName(self::TABLE_COUNTRY)."`(`country_id`) ON DELETE CASCADE ON UPDATE CASCADE"
        ];
        return $this->generateTableSql($table_name, $rows, $keys, $lang);
    }

    public function getCityTable($lang=[]){
        $table_name = $this->getTableName(self::TABLE_CITY);
        $rows = [
            "`city_id` int(11) NOT NULL",
            "`country_id` int(11) NOT NULL",
            "`region_id` int(11) NOT NULL",
            "`important` tinyint(1) NOT NULL DEFAULT '0'"
        ];
        $keys = [
            "PRIMARY KEY  (`city_id`)",
            "KEY `in_city__country_id` (`country_id`)",
            "KEY `in_city__region_id` (`region_id`)",
            "FOREIGN KEY fk_country_country_id__city_country_id (`country_id`) REFERENCES `".$this->getTableName(self::TABLE_COUNTRY)."`(`country_id`) ON DELETE CASCADE ON UPDATE CASCADE",
            "FOREIGN KEY fk_region_region_id__city_region_id (`region_id`) REFERENCES `".$this->getTableName(self::TABLE_REGION)."`(`region_id`) ON DELETE CASCADE ON UPDATE CASCADE"
        ];
        return $this->generateTableSql($table_name, $rows, $keys, $lang);
    }

    public function openDump($table_name, $table_dump){
        $sql ="/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n\n";
        $table_name = $this->getTableName($table_name);
        $sql .= $table_dump."\n";
        $sql .= "LOCK TABLES `$table_name` WRITE;\n\n";
        $sql .= "INSERT INTO `$table_name` VALUES ";

        $file_name = $this->getFileName($table_name);
        if(file_exists($file_name)){
            unlink($this->getFileName($table_name));
        }
        $this->_fp = fopen($file_name, 'a');
        fwrite($this->_fp, $sql);
    }

    public function writeValues($rows){
        $sql = "";
        if(!empty($rows)){
            var_dump(count($rows), $this->is_wrote);
            $sql .= ($this->is_wrote?", ":"");
            if(is_array($rows)){
                foreach($rows as $row){
                    $sql .= (strlen($sql)>2?", ":"");
                    if(is_array($row)){
                        $sql .= "(";
                        $end = count($row)-1;
                        $i=0;
                        foreach($row as $data){
                            if(is_string($data)){
                                $data = self::escape($data);
                            }
                            if(is_int($data)){
                                $sql .= $data;
                            }else{
                                $sql .= "'$data'";
                            }
                            if($i!=$end){
                                $sql .= ", ";
                            }
                            $i++;
                        }
                        $sql .= ")";
                    }else{
                        $sql .= $row;
                    }
                }
            }else{
                $sql = $rows;
            }
            fwrite($this->_fp, $sql);
            $this->is_wrote = true;
        }
    }

    public function closeDump(){
        $sql = ";\n";
        $sql .= "UNLOCK TABLES;\n\n";
        $sql .= "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */";
        fwrite($this->_fp, $sql);
        fclose($this->_fp);
        unset($this->_fp);
    }

    /**
     * @param $table_name
     * @param $rows
     * @return string Dump file name
     * @throws \Exception
     */
    public function createDump($table_name, $rows){
        $table_dump = $this->getTableDump($table_name);
        $this->openDump($table_name, $table_dump);
        $this->writeValues($rows);
        $this->closeDump();
        return $this->getLastFileName();
    }

    protected function generateTableSql($table_name, $rows=[], $keys=[], $lang=[]){
        foreach($lang as $lng){
            $rows[] = "`name_$lng` varchar(60) DEFAULT NULL";
        }

        if(!empty($keys)){
            $rows = array_merge($rows, $keys);
        }
        $fields = implode(",\n",$rows);
        $sql = "DROP TABLE IF EXISTS `$table_name`;\n";
        $sql .= "CREATE TABLE $table_name (\n".$fields."\n) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        return $sql;
    }



    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->_prefix;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        $this->_prefix = $prefix;
    }

    /**
     * @return array
     */
    public function getLang()
    {
        return $this->_lang;
    }

    /**
     * @param $lang
     */
    public function setLang($lang)
    {
        $this->_lang = $lang;
    }

    public function getFileName($table_name){
        $name = $table_name.".sql";
        $this->setLastFileName($name);
        return $name;
    }

    /**
     * @return string
     */
    public function getLastFileName()
    {
        return $this->_last_file_name;
    }

    /**
     * @param string $last_file_name
     */
    public function setLastFileName($last_file_name)
    {
        $this->_last_file_name = $last_file_name;
    }


    public static function escape($value)
    {
        $search = array("\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a");
        $replace = array("\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z");

        return str_replace($search, $replace, $value);
    }


}