<?php
/* 
This database class assumes the existence of two tables:
 
--
-- Table structure for table `querycache`
--
CREATE TABLE IF NOT EXISTS `querycache` (
  `id` char(32) NOT NULL DEFAULT '0',
  `expires` datetime NOT NULL,
  `data` mediumblob NOT NULL,
  PRIMARY KEY (`id`),
  KEY `expires` (`expires`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
 
--
-- Table structure for table `throttle`
--
CREATE TABLE IF NOT EXISTS `throttle` (
  `id` varchar(24) NOT NULL,
  `time` double NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
 
*/
 
 
 
/*  ------------------------------------------------
    The Database class is not meant to be instantiated. 
    ------------------------------------------------ */
class dbclass {
    /* private variables for connection to DB */
    private static $dbName = 'PAAPI'; 
    private static $mysqli;
 
    /* public variables */
    public static $result;
    public static $last_cache_key = false;
 
    /* This function simply connects to the DB and sets the mysqli object */
    public static function connect($mysqli_obj='') {
        if($mysqli_obj)
            self::$mysqli = $mysqli_obj;
        else
            self::$mysqli = new mysqli($_SERVER['RDS_HOSTNAME'], $_SERVER['RDS_USERNAME'], $_SERVER['RDS_PASSWORD'], self::$dbName);
        if (self::$mysqli->connect_errno) {
            writelog("MySQLi error {$mysqli->connect_errno}: {$mysqli->connect_error}");
            die("MySQLi error {$mysqli->connect_errno}: see log.");
        }
        return self::$mysqli;
    } 
 
 
    /* This function is the main funnel for all queries 
       It returns the result, and also caches it in self::$result 
    */
    public static function query($query_str) {
        if (! self::$mysqli ) {
            self::connect();
        }
        elseif(is_object(self::$result)) {
            self::$result->close();
        }
        // ---Performing SQL query 
        self::$result = self::$mysqli->query($query_str) ;
        //---calling code responsible for freeing result
        return self::$result;
    }
 
    /* This function generates a unique key for the cache.
       It returns the result, and also caches it in self::$result.
       The parameter "$obj" should be data uniquely identifying the query.
    */
    public static function cachekey_from_keyobj($obj) {
        /* $str begins with the locale so the same query will 
           have a different cache for each locale. */
        $str = paapi::$amz_locale;
 
        /* $obj will probably be the array describing query parameters
           so stringify it */
        if(! is_string($obj)) {
            $str .= json_encode($obj);
        }
        else {
            $str .= $obj;
        }
        // md5: what a useful tool
        self::$last_cache_key = ($str ? md5($str) : false);
        return self::$last_cache_key;
    }
 
    /* This function finds a cache when supplied the key object.
       It returns the cache row plus "expsecs" as the seconds until expiration
       The parameter "$obj" should be data uniquely identifying the query.
    */
    public static function retrieve_cache($cachekey_obj) {
        if (! self::$mysqli ) self::connect();
        if(! self::cachekey_from_keyobj($cachekey_obj)) 
            return false;
        $sql = 'SELECT *, (UNIX_TIMESTAMP(expires)-UNIX_TIMESTAMP()) as expsecs FROM `querycache` WHERE id='.self::quote_string(self::$last_cache_key);
        if(! self::query($sql) || self::$result->num_rows < 1) 
            return false;
        $o = self::$result->fetch_object();
        if($o->expsecs < 1) { //expired
            return false;
        }
        return $o;
    }
 
    /* This function inserts or updates data in the cache.
       The expiry defaults to 24 hours, but can be set to any number of seconds.
       A cachekey can be provided, but using the last one calculated is preferred.
    */
    public static function set_cache($data, $cacheperiod=86400, $cachekey_obj=0) { //24 hrs
        if (! self::$mysqli ) self::connect();
        if($cachekey_obj) 
            self::cachekey_from_keyobj($cachekey_obj);
        if(! self::$last_cache_key)
            return false;
        $k = self::escape_set('id', self::$last_cache_key);
        $v = '`expires`=(NOW()+INTERVAL '.$cacheperiod.' SECOND),' 
            . self::escape_set('data', $data );
        $sql = 'INSERT INTO `querycache` SET '.$k.','.$v
                    . ' ON DUPLICATE KEY UPDATE '.$v;
        return self::query($sql);
    }
 
    /* This function pauses script execution to throttle the API queries.
       $key is intended to be your API public key so that you can have a throttle
       for each API key using this function. (e.g., several users sharing this code)
    */
    public static function throttle($key='my_PAAPI_key') { 
        if (! self::$mysqli ) self::connect();
        $k = self::escape_set('id', $key);
        //lock the table
        self::query('LOCK TABLE `throttle` WRITE');
 
        //retrieve "time", which is the time when it's okay to query
        $sql = 'SELECT * FROM `throttle` WHERE '.$k;
        if(self::query($sql) && self::$result->num_rows > 0) {
            $o = self::$result->fetch_object();
            $now = microtime(true);
            // set "$sleepuntil" to the time when we should query the API
            if($o->time > $now) {
                $sleepuntil = $o->time;
                $v = self::escape_set('time', ($sleepuntil+paapi::$throttle) );
            }
            else { //no need to sleep
                $v = self::escape_set('time', ($now+paapi::$throttle) );
                $sleepuntil = 0;
            }
        }
        else { //no time was found
            $v = self::escape_set('time', (microtime(true)+paapi::$throttle) );
        }
 
        /* insert a new "time" that is "$throttle" seconds into the future
           as indicated in "$v" set above */
        $sql = 'INSERT INTO `throttle` SET '.$k.','.$v
                    . ' ON DUPLICATE KEY UPDATE '.$v;
        self::query($sql);
 
        //unlock the table so that others can play
        self::query('UNLOCK TABLES');
 
        //now sleep
        if($sleepuntil)
            time_sleep_until($sleepuntil);
    }
 
    /* -----------------------------------------
       And now for some DB convenience functions 
       ----------------------------------------- */
    public static function escape_string($string='') {
        return self::$mysqli->real_escape_string($string);
    }
    public static function quote_string($string='') {
        return '"'.self::$mysqli->real_escape_string($string).'"';
    }
    public static function escape_set($fld, $val) {
        if(is_array($val)) {
            $values = array();
            foreach($val as $v) {
                $values[] = self::quote_string($v);
            }
            return '`'.$fld.'` IN ('. implode(',', $values).')';
        }
 
        return '`'.$fld.'`="' . self::escape_string($val) . '"';
    }
    public static function escape_null_set($fld, $str) {
        if($str===null)
            return '`'.$fld.'`='.'NULL';
        return '`'.$fld.'`="'.self::escape_string($str) . '"';
    }
    public static function get_set($array, $delimit=',') {
        $setarr = array();
        foreach($array as $fld=>$val) {
            $setarr[] = self::escape_set($fld, $val);
        }
        return implode($delimit, $setarr);
    }
}