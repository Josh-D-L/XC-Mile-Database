<?php 
/* A database is used to cache queries and throttle API access */
include_once('dbclass.php');
 
/* First, call the locale setter defined in the class below.
   Set it from a URL parameter, a cookie, or a default.
   Save the locale in a cookie. */
if($_GET['loc']) {
    paapi::set_locale($_GET['loc']);
}
elseif($_COOKIE['loc']) {
    paapi::set_locale($_COOKIE['loc']);
}
else {
    paapi::set_locale('');
}
setcookie('loc', paapi::$amz_locale, time()+(86400*1000)); //expires after 1000 days
 
 
/*  ------------------------------------------------
    The PAAPI class is not meant to be instantiated. 
    ------------------------------------------------ */
 
class paapi {
    /* The API user credentials: */
    public static $key_Public = 'AKIAI4T7453NBQLMTF5Q';
    public static $key_Private = 'aZ3k2Je0/ACm/jXSVqkic6ljjmBKrsIeWsFl3L8X';
    public static $associate_id = 'mil04f-20';
 
    /* The API host domain + locale */
    public static $amz_host = 'webservices.amazon';
    public static $amz_locale = '.com';
 
    /* Other class variables */
    public static $top_browse_nodes;//locale-specific BrowseNode IDs
    public static $qurl = '';       //the signed url
    public static $qkey = '';       //the submitted query parameters
    public static $qresponse = '';  //the api response
    public static $qinfo = '';      //the CURL info
    public static $cache_obj = '';  //the row found in the cache
    public static $throttle = 2.5;  //number of seconds between api queries
 
    /* This function accepts an associative array of API parameters and
       returns a signed URL */
    public static function sign_query($parameters) {
        //sanity check
        if(! $parameters) return '';
 
        /* blank all class variables related to the previous query */
        self::$qurl = self::$qkey = self::$qresponse = self::$cache_obj = '';
 
        /* create an array that contains url encoded values
           like "parameter=encoded%20value" 
           USE rawurlencode !!! */
        $encoded_values = array();
        foreach($parameters as $key=>$val) {
            $encoded_values[$key] = rawurlencode($key) . '=' . rawurlencode(trim($val));
        }
 
        /* before adding common values, sort and save as the query key */
        ksort($encoded_values);
        self::$qkey = $encoded_values;
        /* add locale to query key */
        self::$qkey['loc'] = self::$amz_locale;
 
        /* add the parameters that are needed for every query
           if they do not already exist */
        if(!isset($encoded_values['AssociateTag']))
            $encoded_values['AssociateTag']= 'AssociateTag='.rawurlencode(self::$associate_id);
        if(!isset($encoded_values['AWSAccessKeyId']))
            $encoded_values['AWSAccessKeyId'] = 'AWSAccessKeyId='.rawurlencode(self::$key_Public);
        if(!isset($encoded_values['Service']))
            $encoded_values['Service'] = 'Service=AWSECommerceService';
        if(!isset($encoded_values['Timestamp']))
            $encoded_values['Timestamp'] = 'Timestamp='.rawurlencode(gmdate('Y-m-d\TH:i:s\Z'));
        if(!isset($encoded_values['Version']))
            $encoded_values['Version'] = 'Version=2013-08-01';
 
        /* sort the array by key before generating the signature */
        ksort($encoded_values);
 
        /* set the server, uri, and method in variables to ensure that the 
           same strings are used to create the URL and to generate the signature */
        $server = self::$amz_host . self::$amz_locale;
        $uri = '/onca/xml'; //used in $sig and $url
        $method = 'GET'; //used in $sig
 
 
        /* implode the encoded values and generate signature
           depending on PHP version, tildes need to be decoded
           note the method, server, uri, and query string are separated by a newline */
        $query_string = str_replace("%7E", "~", implode('&',$encoded_values));   
        $sig = base64_encode(hash_hmac('sha256', "{$method}\n{$server}\n{$uri}\n{$query_string}", self::$key_Private, true));
 
        /* build the URL string with the pieces defined above
           and add the signature as the last parameter */
        self::$qurl = "http://{$server}{$uri}?{$query_string}&Signature=" . str_replace("%7E", "~", rawurlencode($sig));
        return self::$qurl;
    }
 
 
    /* This function uses a signed URL to retrieve XML from the API.
       The response and CURL info are saved in class variables.
       The response is cached in a database table.
    */
 
    public static function retrieve() {
        /* A signed URL should have already been generated */
        if(! self::$qurl || ! self::$qkey) return false;
 
        /* If the dbclass does not exist, a few cache and throttle lines must be skipped */
        $dbexists = class_exists(dbclass);
 
        /* Check the query cache and use it if found */
        if($dbexists &&  
            class_exists(dbclass) && self::$cache_obj = dbclass::retrieve_cache(self::$qkey)) {
            self::$qresponse = self::$cache_obj->data;
        }
 
        /* no cache found, so query the API */
        else { 
            //First, allow the throttle to pause script execution
            if($dbexists) dbclass::throttle();
 
            /* prepare a CURL object */
            $ch = curl_init(self::$qurl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 7);
 
            /* do the query and save response and curlinfo */
            self::$qresponse = curl_exec($ch);
            self::$qinfo = curl_getinfo($ch);
 
            /* if not an error, cache the response */
            if(self::$qinfo['http_code']==200) {
                if($dbexists) dbclass::set_cache(self::$qresponse);
            }
        }
        return self::$qresponse;
    }
 
    /* This convenience function accepts an associative array of API parameters,
       uses the functions above,
       and returns a SimpleXML object */
    public static function get_xml_obj($query, $raw=false) {
        if(! self::sign_query($query) ) {
            print('Sign query failed:');
            print_r($query);
            die();
        }
        if(! self::retrieve() ) {
            die('Failed to query API: ' .self::$qurl);
        }
        if($raw) return self::$qresponse;
        if(! $obj=simplexml_load_string(self::$qresponse) ) {
            self::dump_text("SimpleXML failed to load the string retrieved from {self::$qurl}\n\n{self::$qresponse}");
        }
        return $obj;
    }
 
    /* This function accepts a string containing a Top Level Domain that
       is an Amazon locale, and sets some global variables */
    public static function set_locale($loc) {
        /* ensure $loc is lowercase and begins with dot */
        self::$amz_locale = strtolower(trim($loc));
        if(substr(self::$amz_locale,0,1) != '.') {
            self::$amz_locale = '.' . self::$amz_locale;
        }
        /* ensure it is a locale in self::$amz_locale_nodes.
           if not, use ".com" */
        if(! (self::$top_browse_nodes = self::$amz_locale_nodes[self::$amz_locale]) ) {
            self::$amz_locale = '.com';
            self::$top_browse_nodes = self::$amz_locale_nodes[self::$amz_locale];
        }
    }
 
 
    /* This function returns an unordered list of HTML links that 
       will change the locale using URL parameter "loc"
       The calling file can supply a custom URI. 
       The default URI is $_SERVER['SCRIPT_NAME'] */
    public static function get_locale_list($uri='') {
        if(! $uri) {
            $uri = $_SERVER['SCRIPT_NAME'];
        }
        $loclist = '<ul class="linklist">';
        foreach(self::$amz_locale_nodes as $loc => $nodes) {
            if($loc != self::$amz_locale) {
                $loclist .= '<li><a href="'.$uri.'?loc='.$loc.'">['.$loc.']</a></li>';
            }
        }
        $loclist .= '</ul>';
        return $loclist;
    }
 
    /* This debugging function tells the browser to display an XML doc */
    public static function dump_xml($string) {
        header("Content-type: text/xml; charset=utf-8");
        if(is_object ($string))
            die($string->asXML());
        die($string);
    }
    public static function dump_text($string) {
        header("Content-type: text; charset=utf-8");
        die($string);
    }
 
    /* This class array is keyed with amazon locales that contain 
       an array of top-level browsenodes */
    public static $amz_locale_nodes = array(
        '.in'   => array('empty'=>000),
        '.com' => array(
                        'Apparel'       => 7141123011,
                        'Appliances'    => 2619526011,
                        'ArtsAndCrafts' => 2617942011,
                        'Automotive'    => 15690151,
                        'Baby'          => 165797011,
                        'Beauty'        => 11055981,
                        'Books'         => 1000,
                        'CellPhones'    => 2335753011,
                        'Classical'     => 301668,
                        'Collectibles'  => 4991426011,
                        'DVD'           => 2625374011,
                        'DigitalMusic'  => 624868011,
                        'Electronics'   => 493964,
                        'GiftCards'     => 2864120011,
                        'GourmetFood'   => 16310211,
                        'Grocery'       => 16310211,
                        'HealthPersonalCare'    => 3760931,
                        'HomeGarden'    => 1063498,
                        'Industrial'    => 16310161,
                        'KindleStore'   => 133141011,
                        'Kitchen'       => 284507,
                        'LawnAndGarden' => 3238155011,
                        'MP3Downloads'  => 624868011,
                        'Magazines'     => 599872,
                        'Miscellaneous' => 10304191,
                        'MobileApps'    => 2350150011,
                        'Movies'        => 2625373011,
                        'Music'         => 301668,
                        'MusicTracks'   => 301668,
                        'MusicalInstruments'    => 11965861,
                        'OfficeProducts' => 1084128,
                        'OutdoorLiving' => 2972638011,
                        'PCHardware'    => 541966,
                        'PetSupplies'   => 2619534011,
                        'Photo'         => 502394,
                        'Software'      => 409488,
                        'SportingGoods' => 3375301,
                        'Tools'         => 468240,
                        'Toys'          => 165795011,
                        'VideoGames'    => 11846801,
                        'Watches'       => 1079730,
                        'WirelessAccessories'   => 13900851
                        ),
        '.ca' => array(
                        'Automotive'    => 6948389011,
                        'Baby'          => 3561347011,
                        'Beauty'        => 6205125011,
                        'Books'         => 927726,
                        'Classical'     => 962454,
                        'DVD'           => 14113311,
                        'Electronics'   => 677211011,
                        'ForeignBooks'  => 927726,
                        'Grocery'       => 6967216011,
                        'HealthPersonalCare'    => 6205178011,
                        'Jewelry'       =>  0,
                        'KindleStore'   => 2972706011,
                        'Kitchen'       => 2206276011,
                        'LawnAndGarden' => 6299024011,
                        'Luggage'       => 6205506011,
                        'Music'         => 962454,
                        'MusicalInstruments'    =>  0,
                        'OfficeProducts'=> 6205512011,
                        'PetSupplies'   => 6291628011,
                        'Software'      => 3234171,
                        'SoftwareVideoGames'    => 3323751,
                        'SportingGoods' => 2242990011,
                        'Tools'         => 3006903011,
                        'Toys'          => 6205517011,
                        'VHS'           => 962072,
                        'Video'         => 962454,
                        'VideoGames'    => 110218011,
                        'Watches'       => 2235621011
                        ),
        '.co.uk' => array(
                        'Apparel'       => 83451031,
                        'Appliances'    => 908799031,
                        'Automotive'    => 248878031,
                        'Baby'          => 60032031,
                        'Beauty'        => 66280031,
                        'Books'         => 1025612,
                        'Classical'     => 505510,
                        'DVD'           => 573406,
                        'Electronics'   => 560800,
                        'Grocery'       => 344155031,
                        'HealthPersonalCare'    => 66280031,
                        'HomeGarden'    => 11052591,
                        'HomeImprovement'   => 79904031,
                        'Jewelry'       => 193717031,
                        'KindleStore'   => 341677031,
                        'Kitchen'       => 11052591,
                        'Lighting'      => 213078031,
                        'Luggage'       => 2454167031,
                        'MP3Downloads'  => 77925031,
                        'MobileApps'    => 1661658031,
                        'Music'         => 520920,
                        'MusicTracks'   => 520920,
                        'MusicalInstruments'    => 340837031,
                        'OfficeProducts'    => 560800,
                        'OutdoorLiving' => 11052591,
                        'Outlet'        => 245408031,
                        'PCHardware'    => 340832031,
                        'PetSupplies'   => 340841031,
                        'Shoes'         => 362350011,
                        'Software'      => 1025614,
                        'SoftwareVideoGames'    => 1025616,
                        'SportingGoods' => 319530011,
                        'Tools'         => 11052591,
                        'Toys'          => 712832,
                        'UnboxVideo'    =>  0,
                        'VHS'           => 125556011,
                        'Video'         => 283926,
                        'VideoGames'    => 1025616,
                        'Watches'       => 328229011
                        ),
        '.de' => array(
                        'Apparel'   => 78689031,
                        'Automotive'    => 78194031,
                        'Baby'  => 357577011,
                        'Beauty'    => 64257031,
                        'Books'     => 541686,
                        'Classical'     => 542676,
                        'DVD'   => 547664,
                        'Electronics'   => 569604,
                        'ForeignBooks'  => 54071011,
                        'HealthPersonalCare'    => 64257031,
                        'HomeGarden'    => 10925241,
                        'Jewelry'   => 327473011,
                        'Kitchen'   => 3169011,
                        'Magazines'     => 1198526,
                        'MP3Downloads'  => 77256031,
                        'Music'     => 542676,
                        'OfficeProducts'    => 16291311,
                        'OutdoorLiving'     => 10925051,
                        'PCHardware'    => 569604,
                        'Photo'     => 569604,
                        'Software'  => 542064,
                        'SoftwareVideoGames'    => 541708,
                        'SportingGoods'     => 16435121,
                        'Toys'  => 12950661,
                        'VHS'   => 547082,
                        'Video'     => 547664,
                        'VideoGames'    => 541708,
                        'Watches'   => 193708031
                        ),
        '.fr' => array(
                        'Books'     => 468256,
                        'Classical'     => 537366,
                        'DVD'   => 578608,
                        'Electronics'   => 1058082,
                        'ForeignBooks'  => 69633011,
                        'Jewelry'   => 193711031,
                        'Kitchen'   => 57686031,
                        'Music'     => 537366,
                        'OfficeProducts'    => 192420031,
                        'Software'  => 548012,
                        'SoftwareVideoGames'    => 548014,
                        'Toys'  => 548014,
                        'VHS'   => 578610,
                        'Video'     => 578608,
                        'VideoGames'    => 548014,
                        'Watches'   => 60937031,
                        ),
        );
} //end paapi class definition