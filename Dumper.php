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
            self::TABLE_REGION
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
        $rows[] = "`country_id` int(11) NOT NULL";
        return $this->generateTableSql($table_name, $rows, $lang);
    }

    public function getRegionTable($lang=[]){
        $table_name = $this->getTableName(self::TABLE_COUNTRY);
        $rows = [];
        $rows[] = "`region_id` int(11) NOT NULL";
        $rows[] = "`country_id` int(11) NOT NULL";
        return $this->generateTableSql($table_name, $rows, $lang);
    }

    public function getCityTable($lang=[]){
        $table_name = $this->getTableName(self::TABLE_COUNTRY);
        $rows = [];
        $rows[] = "`city_id` int(11) NOT NULL";
        $rows[] = "`country_id` int(11) NOT NULL";
        $rows[] = "`region_id` int(11) NOT NULL";
        $rows[] = "`important` tinyint(1) NOT NULL DEFAULT '0'";
        return $this->generateTableSql($table_name, $rows, $lang);
    }

    public function openDump($table_name, $table_dump){
        $table_name = $this->getTableName($table_name);
        $sql = $table_dump."\n\n";
        $sql .= "INSERT INTO `$table_name` VALUES ";

        $file_name = $this->getFileName($table_name);
        if(file_exists($file_name)){
            unlink($this->getFileName($table_name));
        }
        $this->_fp = fopen($file_name, 'a');
        fwrite($this->_fp, $sql);
    }

    public function writeValues($rows){
        $sql = '';
        if(is_array($rows)){
            foreach($rows as $row){
                $sql .= (strlen($sql)?", ":"");
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
    }

    public function closeDump(){
        fwrite($this->_fp, ";");
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

    protected function generateTableSql($table_name, $rows=[], $lang=[]){
        foreach($lang as $lang){
            $rows[] = "`name_$lang` varchar(60) DEFAULT NULL";
        }
        $sql = "DROP TABLE IF EXISTS ".$table_name.";
CREATE TABLE ".$table_name." (
  ".implode(', ',$rows)."
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ";
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