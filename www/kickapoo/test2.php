<?php
/* Define the constants needed to access the PA API */
define( 'MY_ASSOCIATE_ID', 'mil04f-20' );
define( 'MY_PUBLIC_KEY',   'AKIAI4T7453NBQLMTF5Q' );
define( 'MY_PRIVATE_KEY',  'aZ3k2Je0/ACm/jXSVqkic6ljjmBKrsIeWsFl3L8X' );
 
/* Set the Amazon locale, which is the top-level domain of the server */
$amz_locale = '.com';
 
$query = array( 'Operation'     =>'ItemLookup', 
                'ResponseGroup' =>'Small,Images',
                'IdType'        =>'ASIN',
                'ItemId'        =>'0060558121' );
$signed_url = sign_query($query);
 
/* Use CURL to retrieve the data so that http errors can be examined */
$ch = curl_init($signed_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 7);
$xml_string = curl_exec($ch);
$curl_info = curl_getinfo($ch);
curl_close($ch);
 
if($curl_info['http_code']==200) {
    dump_xml($xml_string);
    $xml_obj = simplexml_load_string($xml_string);
}
else {
    /* examine the $curl_info to discover why AWS returned an error 
       $xml_string may still contain valid XML, and may include an
       informative error message */
}
 
/* Traverse $xml_obj to display parts of it on your website */
print_r($xml_obj);
exit();
 
 
function sign_query($parameters) {
    //sanity check
    if(! $parameters) return '';
 
    /* create an array that contains url encoded values
       like "parameter=encoded%20value" 
       USE rawurlencode !!! */
    $encoded_values = array();
    foreach($parameters as $key=>$val) {
        $encoded_values[$key] = rawurlencode($key) . '=' . rawurlencode($val);
    }
 
    /* add the parameters that are needed for every query
       if they do not already exist */
    if(! $encoded_values['AssociateTag'])
        $encoded_values['AssociateTag']= 'AssociateTag='.rawurlencode('MY_ASSOCIATE_ID');
    if(! $encoded_values['AWSAccessKeyId'])
        $encoded_values['AWSAccessKeyId'] = 'AWSAccessKeyId='.rawurlencode('MY_PUBLIC_KEY');
    if(! $encoded_values['Service'])
        $encoded_values['Service'] = 'Service=AWSECommerceService';
    if(! $encoded_values['Timestamp'])
        $encoded_values['Timestamp'] = 'Timestamp='.rawurlencode(gmdate('Y-m-d\TH:i:s\Z'));
    if(! $encoded_values['Version'])
        $encoded_values['Version'] = 'Version=2011-08-01';
 
    /* sort the array by key before generating the signature */
    ksort($encoded_values);
 
 
    /* set the server, uri, and method in variables to ensure that the 
       same strings are used to create the URL and to generate the signature */
    global $amz_locale;
    $server = 'webservices.amazon'.$amz_locale;
    $uri = '/onca/xml'; //used in $sig and $url
    $method = 'GET'; //used in $sig
 
 
    /* implode the encoded values and generate signature
       depending on PHP version, tildes need to be decoded
       note the method, server, uri, and query string are separated by a newline */
    $query_string = str_replace("%7E", "~", implode('&',$encoded_values));   
    $sig = base64_encode(hash_hmac('sha256', "{$method}\n{$server}\n{$uri}\n{$query_string}", 'MY_PRIVATE_KEY', true));
 
    /* build the URL string with the pieces defined above
       and add the signature as the last parameter */
    $url = "http://{$server}{$uri}?{$query_string}&Signature=" . str_replace("%7E", "~", rawurlencode($sig));
    return $url;
}
 
 
/*	This function displays the XML. 
	It sends a header so your browser can format the XML for you. */
function dump_xml($XML_string) {
    header("Content-type: text/xml; charset=utf-8");
    die($XML_string);
}
?>