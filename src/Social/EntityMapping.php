<?php
/**
 * Jasny Social
 * World's best PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012-2014 Jasny
 */

/** */
namespace Social;

/**
 * Trait to support converting the API's data to Entity objects
 */
trait EntityMapping
{
    /**
     * Current user
     * @var Entity
     */
    protected $me;
    
    
    /**
     * Run a single HTTP request.
     * 
     * @param object $request  Value objects { 'method': string, 'url': string, 'params': array, 'headers': array, 'convert': boolean, 'writefunction': callback }
     * @return mixed
     */
    public function singleRequest($request)
    {
        $data = parent::singleRequest($request);
        return $this->convertResponse($request, $data);
    }
    
    /**
     * Run multiple HTTP requests in parallel.
     * 
     * @param array $requests  Array of value objects { 'method': string, 'url': string, 'params': array, 'headers': array, 'convert': boolean, 'writefunction': callback }
     * @return array
     */
    protected function multiRequest(array $requests)
    {
        $results = parent::multiRequest($requests);
        
        foreach ($results as $i=>&$data) {
            $data = $this->convertResponse($requests[$i], $data, $i);
        }
        
        return $results;
    }
    
    
    /**
     * Convert returned data of request
     * 
     * @param object $request
     * @param mixed  $data
     * @param int    $i        Number of request
     * @return mixed
     */
    protected function convertResponse($request, $data, $i=0)
    {
        if (!$request->convert) return $data;
        
        $data = $this->convert($data, true, Entity::NO_STUB, $request);

        $convert = $request->convert;
        if ($convert instanceof Data) {
            $data = $convert->setData($data);
        } elseif (is_callable($convert)) {
            $data = $convert($data, $i);
        }
        
        return $data;
    }

    /**
     * Convert data to Entity, Collection or DateTime.
     * 
     * @param mixed   $data
     * @param string  $type     Entity type, true is autodetect
     * @param boolean $stub     If an Entity, asume it's a stub
     * @param object  $request  Request used to get this data
     * @return Entity|Collection|DateTime|mixed
     */
    abstract public function convert($data, $type=null, $stub=Entity::NO_STUB, $request=null);
    
    
    /**
     * Get a class name for a type.
     * 
     * @param string $type
     * @return string
     */
    protected function getClassName($type)
    {
        $class = strtolower(preg_replace('/\W/', '', $type));
        $class = __NAMESPACE__ . '\\' . join('', array_map('ucfirst', explode('_', $class)));

        if (!class_exists($class) || !is_a($class, __NAMESPACE__ . '\\Entity', true)) {
            throw new Exception("Unknown entity type '$type'");
        }
        
        return $class;
    }
    
    
    /**
     * Get current user profile.
     * 
     * @return Me
     */
    public function me()
    {
        if (!isset($this->me)) {
            if (!method_exists($this, 'isAuth') || !$this->isAuth()) throw new Exception("There is no current user.");
            $this->me = $this->entity('me', [], Entity::AUTO_HYDRATE);
        }
        
        return $this->me;
    }

    /**
     * Factory method for an entity.
     * 
     * @param string    $type  'me', 'user', 'tweet', 'direct_message', 'user_list', 'saved_search' or 'place'
     * @param array|int $data  Properties or ID
     * @param int       $stub  Entity::NO_STUB, Entity::STUB or Entity::AUTO_HYDRATE
     * @return Entity
     */
    public function entity($type, $data=[], $stub=Entity::AUTO_HYDRATE)
    {
        if (isset($type) && $type[0] == '@') {
            $type = substr($type, 1);
            $stub = true;
        }
        
        $class = $this->getClassName($type);
        return new $class($this, $data, $stub);
    }
    
    /**
     * Factory method a collection.
     * 
     * @param string $type  Type of entities in the collection (may be omitted)
     * @param array  $data
     * @param int    $stub  Entity::NO_STUB, Entity::STUB or Entity::AUTO_HYDRATE
     * @return Collection
     */
    public function collection($type, array $data=[], $stub=Entity::STUB)
    {
        $this->getClassName($type); // checks type
        return $this->convert($data, $type, $stub);
    }
    
    
    /**
     * Serialization
     * 
     * @return array
     */
    public function __sleep()
    {
        if ($this->me) $this->_me = $this->me->getId();
        return array_diff(array_keys(get_object_vars($this)), ['me']);
    }
    
    /**
     * Unserialization
     * 
     * @return array
     */
    public function __wakeup()
    {
        if (isset($this->_me)) $this->me = $this->entity('me', $this->_me, Entity::AUTO_HYDRATE);
        unset($this->_me);
    }
}
