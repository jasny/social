<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\LinkedIn;

/**
 * Entity representing a LinkedIn member.
 * 
 * @package LinkedIn
 */
class Person implements \Social\Person, \Social\User, \Social\Profile
{
    /**
     * Available profile fields
     * 
     * @var array
     */
    static public $fields = [
        // r_basicprofile'
        'id',
        'first-name',
        'last-name',
        'maiden-name',
        'formatted-name',
        'phonetic-first-name',
        'phonetic-last-name',
        'formatted-phonetic-name',
        'headline',
        'location',
        'industry',
        'distance',
        'relation-to-viewer',
        'current-share',
        'num-connections',
        'num-connections-capped',
        'summary',
        'specialties',
        'positions',
        'picture-url',
        'site-standard-profile-request',
        'api-standard-profile-request',
        'public-profile-url',

        // r_emailaddress
        'email-address',

        // r_fullprofile
        'last-modified-timestamp',
        'proposal-comments',
        'associations',
        'interests',
        'publications',
        'patents',
        'languages',
        'skills',
        'certifications',
        'educations',
        'courses',
        'volunteer',
        'three-current-positions',
        'three-past-positions',
        'num-recommenders',
        'recommendations-received',
        'mfeed-rss-url',
        'following',
        'job-bookmarks',
        'suggestions',
        'date-of-birth',
        'member-url-resources',
        'related-profile-views',
        'honors-awards',

        // r_contactinfo
        'phone-numbers',
        'bound-account-types',
        'im-accounts',
        'main-address',
        'twitter-accounts',
        'primary-twitter-account',

        // r_network
        'connections'
    ];
    
    /**
     * Class constructor
     * 
     * @param object|array $data
     */
    public function __construct($data)
    {
        foreach ($data as $key=>$value) {
            $this->$key = $value;
        }
        
        $this->cast();
    }
    
    /**
     * Cast part of the data to entities
     */
    protected function cast()
    {
        if (isset($this->location)) $this->location = new Address($this->location);
        
        if (isset($this->positions)) {
            foreach ($this->positions->values as &$position) {
               $position = new Position($position);
            }
        }
    }
    
    
    /**
     * Get the person's ID at another service provider.
     * 
     * <code>
     *   $linkedin->me()->atProvider('twitter');
     * </code>
     * 
     * @param \Social\Connection $service  Service provider
     * @return \Social\Person
     */
    public function atProvider($service)
    {
        if ($service instanceof \Social\Twitter\Connection && isset($this->primaryTwitterAccount)) {
            $account = $this->primaryTwitterAccount;
            return $service->user(['id'=>$account->providerAccountId, 'screen_name'=>$account->providerAccountName]);
        }
    }
    
    
    /**
     * Get person's full name
     * 
     * @return string
     */
    public function getName()
    {
        if (isset($this->formattedName)) return $this->formattedName;
        return join(' ', array_filter([$this->getFirstName(), $this->getLastName()]));
    }
    
    /**
     * Get person's first name
     * 
     * @return string
     */
    public function getFirstName()
    {
        return isset($this->firstName) ? $this->firstName : null;
    }
    
    /**
     * Get person's last name
     * 
     * @return string
     */
    public function getLastName()
    {
        return isset($this->lastName) ? $this->lastName : null;
    }
    
    
    /**
     * Get person's gender
     * 
     * @return string
     */
    public function getGender()
    {
        return isset($this->gender) ? $this->gender : null;
    }
    
    /**
     * Get person's date of birth
     * 
     * @return string
     */
    public function getDateOfBirth()
    {
        return null;
    }
    
    /**
     * Get url to person's locale (= language)
     * 
     * @return string
     */
    public function getLocale()
    {
        return isset($this->locale) ? $this->locale : null;
    }
    
    
    /**
     * Get username on Facebook.
     * 
     * @return string
     */
    public function getUsername()
    {
        return isset($this->emailAddress) ? $this->emailAddress : null;
    }
    
    /**
     * Get URL to profile on Facebook
     * 
     * @return string
     */
    public function getLink()
    {
        return isset($this->publicProfileUrl) ? $this->publicProfileUrl : null;
    }
    
    /**
     * Get url to profile picture.
     * 
     * @param string $size  Not used
     * @return string
     */
    public function getPicture($size=null)
    {
        return isset($this->pictureUrl) ? $this->pictureUrl : null;
    }
    
    
    /**
     * Get person's email address
     * 
     * @return string
     */
    public function getEmail()
    {
        return isset($this->emailAddress) ? $this->emailAddress : null;
    }
    
    /**
     * Get person's website.
     * Return the main (or first) website, if multiple are known.
     * 
     * @return string
     */
    public function getWebsite()
    {
        return null;
    }
    
    /**
     * Get person's location
     * 
     * @return Address
     */
    public function getLocation()
    {
        return isset($this->location) ? $this->location : null;
    }
    
    /**
     * Get person's timezone
     * 
     * @return null
     */
    public function getTimezone()
    {
        return null;
    }
    
    /**
     * Get person's bio
     * 
     * @return string
     */
    public function getDescription()
    {
        return $this->summary;
    }
    
    /**
     * Get information about person's employment
     * 
     * @return Employment
     */
    public function getEmployment()
    {
        if (!empty($this->positions->values)) return $this->positions->values[0];
        return isset($this->headline) ? new Position($this->headline) : null;
    }
    
    /**
     * Get person's employment company.
     * 
     * @return Company
     */
    public function getCompany()
    {
        $employment = $this->getEmployment();
        return isset($employment) ? $employment->getCompany() : null;
    }
    
    
    /**
     * Cast object to string
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->getName() ?: (string)$this->getUsername();
    }
}
