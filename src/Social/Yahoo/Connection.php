<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Yahoo;

use Social\Connection as Base;

/**
 * Yahoo! API connection.
 * @see http://developers.soundcloud.com/docs/api/reference
 * @package Yahoo
 * 
 * Before you start register your application at https://developer.apps.yahoo.com/projects and retrieve a consumer
 *  key and consumer secret
 */
class Connection extends Base implements \Social\Auth
{
    use \Social\OAuth1;
    
    /**
     * Name of the API's service provider
     */
    const serviceProvider = 'yahoo';
    
    
    /**
     * Yahoo! Social directory API URL
     */
    const apiURL = "http://social.yahooapis.com/v1/";
    
    /**
     * Yahoo! authentication URL
     */
    const authURL = "https://api.login.yahoo.com/oauth/v2/";

    /**
     * Yahoo! Query Language URL
     */
    const yqlURL = "http://query.yahooapis.com/v1/public/yql";
    
    
    /**
     * Class constructor.
     * 
     * Passing a user id is not required to act as the user, you're only required to specify the access token.
     * 
     * @param string $consumerKey     Application's consumer key
     * @param string $consumerSecret  Application's consumer secret
     * @param array  $access          [ token, secret, Yahoo! id ] or { 'token': string, 'secret': string, 'user': Yahoo! id }
     */
    public function __construct($consumerKey, $consumerSecret, $access=null)
    {
        $this->setCredentials($consumerKey, $consumerSecret);
        $this->setAccessInfo($access);
    }
    
    /**
     * Build a full url.
     * 
     * @param string  $url
     * @param array   $params
     * @return string
     */
    protected function getFullUrl($url, array $params=[])
    {
        if ($url === 'oauth/request_token') $url = 'get_request_token';
        return parent::getFullUrl($url, $params);
    }

    
    /**
     * Bind parameters to YQL query.
     * 
     * @param string $yql     YQL query
     * @param array  $params
     * @return string
     */
    protected static function YQLBind($yql, $params)
    {
        return preg_replace_callback('/(:\w+)/', function($match) use($params) {
            if (!array_key_exists($match[1], $params)) return $match[1];
       
            $value = $params[$match[1]];
            
            if (is_bool($value)) return $value ? 'true' : 'false';
            if (is_int($value) || is_float($value)) return $value;
            return '"' . addcslashes($value, '"') . '"';
        }, $yql);
    }
    
    /**
     * Query Yahoo! using YQL.
     * @see http://developer.yahoo.com/yql/
     * 
     * You may use placeholders in the query using `:placeholder`.
     * <code>
     *   $yahoo->query("select * from geo.places where text=:text", [':text'=>'Amsterdam']);
     * </code>
     * 
     * @param string $yql     YQL query
     * @param array  $params
     * @return mixed
     */
    public function query($yql, $params=[])
    {
        $params['q'] = static::YQLBind($yql, $params);
        return $this->get(static::yqlURL, $params);
    }
    
    
    /**
     * Get current user profile.
     * 
     * @return object
     */
    public function me()
    {
        return $this->query('SELECT * FROM social.profile WHERE guid=me')->results->profile;
    }
}
