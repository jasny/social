<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social;

/**
 * Trait for APIs that support oEmbed.
 */
trait OEmbed
{
    /**
     * Get oEmbed information
     * 
     * @param string|array  $url     One or more URLs
     * @param array         $params  Additional parameters
     * @return object|array
     */
    public function oembed($url, $params=[])
    {
        if (!is_array($url)) return $this->get(static::oembedURL, ['url'=>$url] + $params + ['format'=>'json']);
        
        $requests = [];
        foreach ($url as $u) {
            $request = (object)['method'=>'GET', 'url'=>static::oembedURL,
                'params'=>['url'=>$url] + $params + ['format'=>'json']];

            if ($this->prepared) $this->addPreparedRequest($request);
             else $requests[] = $request;
        }
        
        return !empty($requests) ? $this->multiRequest($requests) : null; 
    }
}
