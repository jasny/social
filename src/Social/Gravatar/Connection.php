<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Gravatar;

use Social\Connection as Base;

/**
 * Gravatar Graph API connection.
 * @link http://developers.facebook.com/docs/reference/api/
 * @package Gravatar
 * 
 * Before you start register your application at https://developers.facebook.com/apps and retrieve a client ID and
 *  secret.
 */
class Connection extends Base
{
    /**
     * Name of the API service
     */
    const serviceProvider = 'gravatar';
    
    /**
     * Gravatar API URL
     */
    const apiURL = "http://www.gravatar.com/";

    /**
     * Gravatar HTTPS API URL
     */
    const secureURL = "https://secure.gravatar.com/";
    
    
    /**
     * Use secure connection
     * @var boolean
     */
    protected $secure;
    
    
    /**
     * Get/set where to return https urls.
     * 
     * @param boolean $set  Leave NULL to only get the settings
     * @return boolean
     */
    public function useSecure($set=null)
    {
        if (isset($set)) $this->secure = (boolean)$set;
        return isset($this->secure) ? $this->secure : !empty($_SERVER['HTTPS']);
    }

    /**
     * Initialise an HTTP request object.
     *
     * @param object|string $request  value object or url
     * @return object
     */
    protected function initRequest($request)
    {
        $request = parent::initRequest($request);
                
        $path = parse_url($request->url, PHP_URL_PATH);
        if (dirname($path) != 'avatar' && pathinfo($path, PATHINFO_EXTENSION) == '' && empty($request->no_ext)) {
            $request->url .= '.json';
        }
        
        $request->expect[] = 404;
        
        return $request;
    }
    
    /**
     * Process the body.
     * 
     * @param string $contenttype  Mime type
     * @param string $response     The HTTP response
     * @param object $info         Curl info
     * @return mixed
     */
    protected function decodeResponse($info, $response)
    {
        if ($info->http_code === 404) return null;
        return parent::decodeResponse($info, $response);
    }
    
    
    /**
     * Get the hash for an e-mail address
     * 
     * @param string $email
     * @return string
     */
    public function hash($email)
    {
        return md5(strtolower($email));
    }
    
    /**
     * Get the link to an avatar image.
     * Images are always square.
     * 
     * @link https://en.gravatar.com/site/implement/images/
     * 
     * Default:
     *  - Any url
     *  - 404: do not load any image if none is associated with the email hash, instead return an HTTP 404 (File Not Found) response
     *  - mm: (mystery-man) a simple, cartoon-style silhouetted outline of a person (does not vary by email hash)
     *  - identicon: a geometric pattern based on an email hash
     *  - monsterid: a generated 'monster' with different colors, faces, etc
     *  - wavatar: generated faces with differing features and backgrounds
     *  - retro: awesome generated, 8-bit arcade-style pixelated faces
     *  - blank: a transparent PNG image
     * 
     * Rating:
     *  - g: suitable for display on all websites with any audience type.
     *  - pg: may contain rude gestures, provocatively dressed individuals, the lesser swear words, or mild violence.
     *  - r: may contain such things as harsh profanity, intense violence, nudity, or hard drug use.
     *  - x: may contain hardcore sexual imagery or extremely disturbing violence.
     * 
     * @param string  $email
     * @param int     $size
     * @param string  $default  URL or option
     * @param string  $rating   'g', 'pg', 'r', 'x'
     * @return string
     */
    public function avatar($email, $size=800, $default='404', $rating=null)
    {
        $base = $this->useSecure() ? static::apiURL : static::secureURL;
        $params = "?size=$size" . ($default ? "&d=$default" : '') . ($rating ? "&r=$rating" : '');
        
        return $base . 'avatar/' . $this->hash($email) . $params;
    }
    
    /**
     * Check if the user has uploaded an avatar
     * 
     * @param type $email
     * @return boolean
     */
    public function avatarExists($email)
    {
        $url = 'avatar/' . $this->hash($email);
        $info = $this->request((object)['method'=>'HEAD', 'url'=>$url, 'params'=>['d'=>404]]);
        
        return $info->http_code != 404;
    }
    
    /**
     * Get profile information
     * 
     * @param string $resource  E-mail address, hash or username
     * @param array  $params
     * @return object
     */
    public function user($resource, array $params = [])
    {
        if (strpos($resource, '@')) $resource = $this->hash($resource);
        
        $result = $this->get($resource, $params);
        return $result ? new Profile($result->entry[0]) : null;
    }
}
