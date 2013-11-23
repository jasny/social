<?php
/**
 * Jasny Social
 * World's best PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Freebase;

use Social\Google\Base as Google;

/**
 * Freebase API connection
 * @see https://developers.google.com/freebase/v1
 * @package Freebase
 * 
 * Before you start register your application at https://code.google.com/apis/console/#access and retrieve an API key.
 * Optionally also get a client ID and secret for OAuth2 to access freebase as a specific user.
 * 
 * OAuth2 scopes:
 *   - freebase
 *   - freebase.readonly
 */
class Connection extends Google
{
    /**
     * Api name
     * @var string 
     */
    protected $apiName = "freebase";
    
    /**
     * Api version
     * @var string 
     */
    protected $apiVersion = "v1";
    
    
    /**
     * Search Freebase using a free text query.
     * @see https://developers.google.com/freebase/v1/search
     * 
     * <code>
     *   $freebase->search("Obama"); // 1 request
     *   $freebase->search(["Obama", "Clinton", "Bush"]); // 3 requests
     *   $freebase->search(["Obama", "Clinton", "Bush"], ['filter'=>"(all type:/people/person)"]); // 3 requests
     * </code>
     * 
     * @param string|array $query   Search query
     * @param array        $params  Other paramaters
     * @return array
     */
    public function search($query, $params=[])
    {
        // Multiple requests
        if (is_array($query)) {
            $requests = [];
            foreach ($query as $q) {
                $requests[] = (object)['method'=>'GET', 'url'=>'search', 'params'=>['query'=>$q] + $params];
            }
            
            return $this->request($requests); 
        }
        
        // Single request
        return $this->get('search', ['query'=>$query] + $params);
    }
    
    /**
     * Search Freebase using a filter.
     * @see https://developers.google.com/freebase/v1/search
     * 
     * <code>
     *   $freebase->filter("(all type:film /film/film/directed_by:Ridley+Scott)");
     * </code>
     * 
     * @param string|array $filter  Search filter
     * @param array        $params  Other paramaters
     * @return array
     */
    public function filter($filter, $params=[])
    {
        // Multiple requests
        if (is_array($filter)) {
            $requests = [];
            foreach ($filter as $f) {
                $requests[] = (object)['method'=>'GET', 'url'=>'search', 'params'=>['filter'=>$f] + $params];
            }
            
            return $this->request($requests); 
        }
        
        // Single request
        return $this->get('search', ['filter'=>$filter] + $params);
    }
    
    /**
     * Uniquely match an entity in Freebase with some structured data about that entity.
     * @see https://developers.google.com/freebase/v1/reconcile
     * 
     * <code>
     *   $freebase->reconcile("Prometheus"); // 1 request
     *   $freebase->reconcile("Prometheus", "film", "directed_by:Ridley Scott"); // 1 request
     *   $freebase->reconcile(["Prometheus", "The Hobbit", "Toy Story"], "film"); // 3 requests
     * 
     *   $freebase->prepare()
     *     ->reconcile("Prometheus", "film")
     *     ->reconcile("Barack Obama", "person")
     *     ->reconcile("Starman", "song")
     *     ->execute(); // 3 requests
     * </code>
     * 
     * @param string|array $name    Entity name or multiple entity names
     * @param string|array $kind    Freebase type
     * @param string|array $prop    Known entity property
     * @param array        $params  Other paramaters
     * @return object|array
     */
    public function reconcile($name, $kind=null, $prop=null, $params=[])
    {
        // Multiple requests
        if (is_array($name)) {
            $params = compact('kind', 'prop') + $params;
            $requests = [];
            foreach ($name as $n) {
                $requests[] = (object)['method'=>'GET', 'url'=>'reconcile', 'params'=>['name'=>$n] + $params];
            }
            
            return $this->request($requests); 
        }
        
        // Single request
        return $this->get('reconcile', compact('name', 'kind', 'prop') + $params);
    }
    
    /**
     * Query Freebase using the Metaweb query language (MQL).
     * @see https://developers.google.com/freebase/v1/mqlread
     * @see http://mql.freebaseapps.com/
     * 
     * @param array|object|string $query   MQL query
     * @param array               $params  Other paramaters
     * @return object|array
     */
    public function mqlread($query, $params=[])
    {
        return $this->get('mqlread', compact('query') + $params);
    }
    
    /**
     * Write to Freebase using the Metaweb query language (MQL).
     * 
     * @see https://developers.google.com/freebase/v1/mqlwrite
     * @see http://mql.freebaseapps.com/
     * 
     * @param array|object|string $query   MQL query
     * @param array               $params  Other paramaters
     * @return object|array
     */
    public function mqlwrite($query, $params=[])
    {
        return $this->get('mqlwrite', compact('query') + $params);
    }
    
    /**
     * Return all the known facts for a given topic including images and text blurbs.
     * @see https://developers.google.com/freebase/v1/topic
     * 
     * Multiple filters may be passed as array.
     * 
     * <code>
     *   $freebase->topic("/m/02mjmr", ['filter'=>['/common', '/person']]);
     * </code>
     * 
     * @param string|array $id      Freebase ID or multiple IDs
     * @param array        $params  Other paramaters
     * @return object|array
     */
    public function topic($id, $params=[])
    {
        // Multiple requests
        if (is_array($id)) {
            $requests = [];
            foreach ($id as $i) {
                $requests[] = (object)['method'=>'GET', 'url'=>'topic/:id', 'params'=>[':id'=>$i] + $params];
            }
            
            return $this->request($requests); 
        }
        
        // Single request
        return $this->get('topic/:id', [':id'=>$id] + $params);
    }
    
    
    /**
     * Build a HTTP query, converting arrays to a comma seperated list and removing null parameters.
     * 
     * @param type $params
     * @return string
     */
    protected static function buildHttpQuery($params)
    {
        $query = "";
        
        foreach ($params as $key=>&$value) {
            if (!isset($value)) {
                unset($params[$key]);
                continue;
            }

            // Special case allowing multiple filters for topic
            if ($key === 'filter' && is_array($value)) {
                foreach ($value as $val) $query .= "&filter=" . rawurlencode($val);
                continue;
            }
            
            if (is_object($value) || is_array($value)) {
                $value = rawurlencode(json_encode($value));
            } else {
                $value = (is_bool($value) ? ($value ? 'true' : 'false') : rawurlencode($value));
            }
            
            $query .= '&' . rawurlencode($key) . '=' . $value;
        }
        
        return substr($query, 1);
    }
}
