<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012-2014 Jasny
 */

/** */
namespace Social\LinkedIn;

/**
 * Entity representing the current authenticated user
 * 
 * @package LinkedIn
 */
class Me extends Person implements \Social\Me
{ 
    /**
     * API connection object
     * @var Social\LinkedIn\Connection
     */
    private $connection;

     /**
     * Sets the API connection object
     * 
     * @param Social\LinkedIn\Connection   $connection     Linkedin API connection object
     * @return Me 
     */    
    public function setConnection(\Social\LinkedIn\Connection $connection)
    {
        $this->connection = $connection;
        return $this;
    }

     /**
     * Posts a share to the user's profile.
     * 
     * @param string   $comment     Share comment
     * @param array    $content     ['title', 'submitted-url', 'submitted-image-url', 'description']
     * @param boolean  $anyone      Set to False to restrict visibility to just the user's connections
     * @return object
     */
    public function postShare($comment, array $content, $anyone = True)
    {
        $response = $this->connection->post('people/~/shares', ['comment' => $comment,
                                                    'content' => $content,
                                                    'visibility' => ['code' => ($anyone ? 'anyone' : 'connections-only')]
                                                    ],
                                                    ['Content-Type' => 'application/json']
       );
        return $response;
    }

}