<?php

namespace Social\Twitter;

require_once 'Social/Twitter/Connection.php';
require_once 'Social/Twitter/Me.php';

/**
 * Test class for Social\Twitter\Connection.
 * 
 * Note: This class will post several tweets.
 * Running this test multiple times in 15 minutes can cause rate limit exceptions.
 */
class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var object
     */
    protected $cfg;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->cfg = $GLOBALS['cfg']->twitter;
        $this->connection = new Connection($this->cfg->consumer_key, $this->cfg->consumer_secret, $this->cfg->access_token, $this->cfg->access_secret);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        $this->connection = null;
    }

    /**
     * Test object creation.
     */
    public function testConstruct()
    {
        $this->assertAttributeEquals($this->cfg->consumer_key, 'consumerKey', $this->connection);
        $this->assertAttributeEquals($this->cfg->consumer_secret, 'consumerSecret', $this->connection);
        $this->assertAttributeEquals($this->cfg->access_token, 'accessToken', $this->connection);
        $this->assertAttributeEquals($this->cfg->access_secret, 'accessSecret', $this->connection);
    }

    /**
     * Test object creation with access information.
     */
    public function testConstruct_Access()
    {
        $twitter = new Connection('foo', 'bar', (object)array('token' => 'dog', 'secret' => 'fox'));

        $this->assertAttributeEquals('foo', 'consumerKey', $twitter);
        $this->assertAttributeEquals('bar', 'consumerSecret', $twitter);
        $this->assertAttributeEquals('dog', 'accessToken', $twitter);
        $this->assertAttributeEquals('fox', 'accessSecret', $twitter);
    }

    /**
     * Test object creation with Me entity.
     */
    public function testConstruct_WithMe()
    {
        $me = unserialize('O:17:"Social\\Twitter\\Me":3:{s:8:"' . "\0" . '*' . "\0" . '_type";s:2:"me";s:8:"' . "\0" . '*' . "\0" . '_stub";b:1;s:7:"user_id";i:12345;}');

        $twitter = new Connection('foo', 'bar', 'dog', 'fox', $me);
        $this->assertAttributeSame($me, 'me', $twitter);
        $this->assertAttributeSame($twitter, '_connection', $twitter->me());
    }

    /**
     * Test object creation with Me entity.
     */
    public function testConstruct_WithMeId()
    {
        $twitter = new Connection('foo', 'bar', 'dog', 'fox', 12345);

        $this->assertAttributeEquals(12345, 'user_id', $twitter->me());
        $this->assertAttributeEquals(true, '_stub', $twitter->me());
        $this->assertAttributeSame($twitter, '_connection', $twitter->me());
    }

    /**
     * Test asUser().
     * 
     * @depends testConstruct
     */
    public function testAsUser()
    {
        $twitter = $this->connection->asUser('test_access', 'test_secret');

        $this->assertAttributeEquals($this->cfg->consumer_key, 'consumerKey', $twitter);
        $this->assertAttributeEquals($this->cfg->consumer_secret, 'consumerSecret', $twitter);
        $this->assertAttributeEquals('test_access', 'accessToken', $twitter);
        $this->assertAttributeEquals('test_secret', 'accessSecret', $twitter);
    }

    /**
     * Test getConsumerKey().
     */
    public function testGetConsumerKey()
    {
        $this->assertEquals($this->cfg->consumer_key, $this->connection->getConsumerKey());
    }

    /**
     * Test getAccessToken().
     */
    public function testGetAccessToken()
    {
        $this->assertEquals($this->cfg->access_token, $this->connection->getAccessToken());
    }

    /**
     * Test getAccessSecret().
     */
    public function testGetAccessSecret()
    {
        $this->assertEquals($this->cfg->access_secret, $this->connection->getAccessSecret());
    }

    /**
     * Test getAccessInfo().
     */
    public function testGetAccessInfo()
    {
        $this->assertEquals((object)array('token' => $this->cfg->access_token, 'secret' => $this->cfg->access_secret), $this->connection->getAccessInfo());
    }

    /**
     * Test getBaseUrl().
     */
    public function testGetBaseUrl()
    {
        $method = new \ReflectionMethod($this->connection, 'getBaseUrl');
        $method->setAccessible(true);

        $this->assertEquals(Connection::restURL, $method->invoke($this->connection, 'statuses/home_timeline'));
        $this->assertEquals(Connection::restURL, $method->invoke($this->connection, 'statuses/home_timeline.json'));
        $this->assertEquals(Connection::restURL, $method->invoke($this->connection, 'statuses/destroy/12345.json'));

        $this->assertEquals(Connection::uploadURL, $method->invoke($this->connection, 'statuses/update_with_media'));

        $this->assertEquals(Connection::restURL, $method->invoke($this->connection, 'search'));
        $this->assertEquals(Connection::restURL, $method->invoke($this->connection, 'search.json'));
        $this->assertEquals(Connection::restURL, $method->invoke($this->connection, 'search?q=#foo'));
        $this->assertEquals(Connection::restURL, $method->invoke($this->connection, 'search.json?q=#foo'));
        $this->assertEquals(Connection::restURL, $method->invoke($this->connection, 'users/search'));

        $this->assertEquals(Connection::streamUrl, $method->invoke($this->connection, 'statuses/filter'));
        $this->assertEquals(Connection::streamUrl, $method->invoke($this->connection, 'statuses/sample'));
        $this->assertEquals(Connection::streamUrl, $method->invoke($this->connection, 'statuses/firehose'));

        $this->assertEquals(Connection::userstreamUrl, $method->invoke($this->connection, 'user'));
        $this->assertEquals(Connection::sitestreamUrl, $method->invoke($this->connection, 'site'));
    }

    /**
     * Test getUrl().
     */
    public function testGetUrl()
    {
        $this->assertEquals(Connection::restURL . "statuses/home_timeline.json", $this->connection->getUrl('statuses/home_timeline.json'));
        $this->assertEquals(Connection::restURL . 'search.json', $this->connection->getUrl('search.json'));

        $this->assertEquals(Connection::restURL . "statuses/home_timeline.json?foo=bar", $this->connection->getUrl('statuses/home_timeline.json', array('foo' => 'bar')));
        $this->assertEquals(Connection::restURL . "statuses/home_timeline.json?dog=fox&foo=bar", $this->connection->getUrl("statuses/home_timeline.json?dog=fox", array('foo' => 'bar')));
        $this->assertEquals(Connection::restURL . "statuses/home_timeline.json?dog=fox&foo=bar", $this->connection->getUrl((object)array('url' => "statuses/home_timeline.json", 'params' => array('dog' => 'fox')), array('foo' => 'bar')));

        $this->assertEquals(Connection::restURL . "statuses/show/12345.json", $this->connection->getUrl('statuses/show/:id.json', array(':id' => '12345')));
        $this->assertEquals(Connection::restURL . "statuses/show/12345.json?foo=bar", $this->connection->getUrl('statuses/show/:id.json', array(':id' => '12345', 'foo' => 'bar')));

        $this->assertEquals("http://example.com/?foo=bar", $this->connection->getUrl('http://example.com', array('foo' => 'bar')));
        $this->assertEquals("https://example.com/?foo=bar", $this->connection->getUrl('https://example.com', array('foo' => 'bar')));
    }

    /**
     * Test getCurrentUrl().
     */
    public function testGetCurrentUrl()
    {
        $_SERVER['HTTP_HOST'] = 'example.com';

        $_SERVER['REQUEST_URI'] = '';
        $this->assertEquals("http://example.com/", $this->connection->getCurrentUrl());
        $this->assertEquals("http://example.com/connect.php", $this->connection->getCurrentUrl('connect.php'));
        $this->assertEquals("http://example.com/connect.php?lion=cat", $this->connection->getCurrentUrl('connect.php', array('lion' => 'cat')));

        $_SERVER['REQUEST_URI'] = '?fox=dog&oauth_token=foo&oauth_verifier=bar';
        $this->assertEquals("http://example.com/?fox=dog", $this->connection->getCurrentUrl());
        $this->assertEquals("http://example.com/?fox=dog&lion=cat", $this->connection->getCurrentUrl(null, array('lion' => 'cat')));
    }

    /**
     * Test normalizeResource().
     */
    public function testNormalizeResource()
    {
        $this->assertEquals("foo/*/bar", $this->connection->normalizeResource('foo/12345/bar.json'));
        $this->assertEquals("dog/*/fox", $this->connection->normalizeResource('dog/:id/fox?a=b'));
    }

    /**
     * Test getDefaultParams().
     */
    public function testGetDefaultParams()
    {
        $this->assertEquals(array(), $this->connection->getDefaultParams('foo'));
        $this->assertEquals(array('max_id' => null), $this->connection->getDefaultParams('statuses/home_timeline'));
    }

    /**
     * Test detectType().
     */
    public function testDetectType()
    {
        $this->assertEquals('tweet', $this->connection->detectType('statuses'));
        $this->assertEquals('tweet', $this->connection->detectType('statuses/12345'));
        $this->assertEquals('user', $this->connection->detectType('statuses/12345/retweeted_by'));
        $this->assertNull($this->connection->detectType('statuses/oembed'));
        $this->assertEquals('@user', $this->connection->detectType('friendships'));
    }

    /**
     * Test simple get().
     * 
     * @depends testConstruct
     */
    public function testGet()
    {
        $result = $this->connection->get('help/tos');
        
        $this->assertInternalType('object', $result);
        $this->assertObjectHasAttribute('tos', $result);
        $this->assertStringStartsWith("Terms of Service\n", $result->tos);
    }

    /**
     * Test an error using get().
     * 
     * @depends testConstruct
     */
    public function testGet_Error()
    {
        $this->setExpectedException('Social\Exception', "HTTP GET request for 'http://foo.bar' failed");
        $this->connection->get('http://foo.bar');
    }

    /**
     * Test getting a tweet.
     * 
     * @depends testGet
     */
    public function testGet_Tweet()
    {
        $response = $this->connection->get('statuses/show/:id', array(':id' => '231230427510239232'), false);

        $this->assertEquals('231230427510239232', $response->id_str);
        $this->assertEquals("Cool! You're testing #JasnySocialTwitterSearch", $response->text);
        $this->assertEquals('JasnyArnold', $response->user->screen_name);
    }

    /**
     * Test getting a tweet entity.
     * 
     * @depends testGet_Tweet
     */
    public function testGet_TweetEntity()
    {
        $tweet = $this->connection->get('statuses/show/:id', array(':id' => '231230427510239232'));

        $this->assertInstanceOf('Social\Twitter\Tweet', $tweet);
        $this->assertEquals('231230427510239232', $tweet->id);
        $this->assertEquals("Cool! You're testing #JasnySocialTwitterSearch", $tweet->text);

        $this->assertInstanceOf('Social\Twitter\User', $tweet->user);
        $this->assertEquals('JasnyArnold', $tweet->user->screen_name);
    }
    
    public function testGet_FollowersEntity()
    {
        $followers = $this->connection->get('followers/ids', array('screen_name' => 'JasnyArnold'));

        $this->assertInstanceOf('Social\Twitter\Collection', $followers);
        $this->assertInstanceOf('Social\Twitter\User', $followers[0]);
    }

    /**
     * Test getting a tweet.
     * 
     * @depends testGet
     */
    public function testGet_Param()
    {
        $compare = $this->connection->get('friends/ids', array('screen_name' => 'JasnyArnold'), false);
        $response = $this->connection->get('friends/ids?screen_name=JasnyArnold', array(), false);

        $this->assertEquals($compare, $response);
    }

    /**
     * Test get() following the cursor.
     * 
     * @depends testGet
     */
    public function testGet_FollowCursor()
    {
        $response = $this->connection->get('followers/ids', array('screen_name' => 'zend'), false);

        $this->assertObjectHasAttribute('ids', $response);
        $this->assertGreaterThan(15000, count($response->ids));
        $this->assertEquals(0, $response->next_cursor);
    }

    /**
     * Test get() without following the cursor.
     * 
     * @depends testGet
     */
    public function testGet_DontFollowCursor()
    {
        $response = $this->connection->get('followers/ids', array('screen_name' => 'zend', 'cursor' => -1), false);

        $this->assertObjectHasAttribute('ids', $response);
        $this->assertGreaterThan(4900, count($response->ids));     // Number of followers should be 5000, but blocked users
        $this->assertLessThanOrEqual(5000, count($response->ids)); //  are taken out of the list after pagination
        $this->assertGreaterThan(0, $response->next_cursor);
    }

    /**
     * Test posting a tweet.
     * 
     * @depends testConstruct
     */
    public function testPost_Tweet()
    {
        $text = "This is a test. #JasnySocialTest " . base_convert(uniqid(), 16, 36);
        $response = $this->connection->post("statuses/update", array('status' => $text), false);

        if (isset($response->id_str)) $this->connection->post('statuses/destroy/' . $response->id_str, array(), false);

        $this->assertEquals($text, $response->text);
    }

    /**
     * @todo Implement testStream().
     */
    public function testStream()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * Test simple multi requests.
     * 
     * @depends testConstruct
     */
    public function testDoMultiRequest()
    {
        $response = $this->connection->doMultiRequest(array(
            'help/tos',
            'help/privacy',
        ), false);

        $this->assertInternalType('array', $response);
        $this->assertCount(2, $response);
        
        $this->assertInternalType('object', $response[0]);
        $this->assertObjectHasAttribute('tos', $response[0]);
        $this->assertStringStartsWith("Terms of Service\n", $response[0]->tos);
        
        $this->assertInternalType('object', $response[1]);
        $this->assertObjectHasAttribute('privacy', $response[1]);
        $this->assertStringStartsWith("Twitter Privacy Policy\n", $response[1]->privacy);
    }

    /**
     * Test simple multi requests.
     * 
     * @depends testConstruct
     */
    public function testDoMultiRequest_Error()
    {
        $response = $this->connection->doMultiRequest(array(
            'help/tos',
            'foo/bar',
            'help/privacy',
        ), false);

        $errors = $this->connection->getMultiRequestErrors();

        $this->assertArrayHasKey(0, $response);
        $this->assertArrayNotHasKey(1, $response);
        $this->assertArrayHasKey(2, $response);
        
        $this->assertArrayHasKey(1, $errors);
        $this->assertContains("HTTP GET request for 'foo/bar.json' failed", $errors[1]);
    }

    /**
     * Test multi requests.
     * 
     * @depends testDoMultiRequest
     */
    public function testDoMultiRequest_Complex()
    {
        $text = "This is a test. #JasnySocialTest " . base_convert(uniqid(), 16, 36);

        $response = $this->connection->doMultiRequest(array(
            'help/tos',
            array('url' => 'statuses/show/:id', 'params' => array(':id' => '231230427510239232'), 'convert'=>false),
            (object)array('method' => 'GET', 'url' => 'followers/ids', 'params' => array('screen_name' => 'StackExchange'), 'convert'=>false),
            (object)array('method' => 'GET', 'url' => 'followers/ids', 'params' => array('screen_name' => 'phpc'), 'convert'=>false),
            (object)array('method' => 'GET', 'url' => 'followers/ids', 'params' => array('screen_name' => 'php_net', 'cursor' => -1), 'convert'=>false),
            (object)array('method' => 'POST', 'url' => 'statuses/update', 'params' => array('status' => $text), 'convert'=>false)
        ), false);

        if (isset($response[5]->id_str)) $this->connection->post('statuses/destroy/' . $response[5]->id_str, array(), false);

        if ($this->connection->getMultiRequestErrors()) $this->fail(join("\n", $this->connection->getMultiRequestErrors()));
        
        $this->assertArrayHasKey(0, $response);
        $this->assertStringStartsWith("Terms of Service\n", $response[0]->tos);

        $this->assertArrayHasKey(1, $response);
        $this->assertEquals('231230427510239232', $response[1]->id_str);
        $this->assertEquals("Cool! You're testing #JasnySocialTwitterSearch", $response[1]->text);
        $this->assertEquals('JasnyArnold', $response[1]->user->screen_name);

        $this->assertArrayHasKey(2, $response);
        $this->assertObjectHasAttribute('ids', $response[2]);
        $this->assertGreaterThan(5500, count($response[2]->ids));
        $this->assertEquals(0, $response[2]->next_cursor);

        $this->assertArrayHasKey(3, $response);
        $this->assertObjectHasAttribute('ids', $response[3]);
        $this->assertGreaterThan(19000, count($response[3]->ids));
        $this->assertEquals(0, $response[3]->next_cursor);

        $this->assertArrayHasKey(4, $response);
        $this->assertObjectHasAttribute('ids', $response[4]);
        $this->assertGreaterThan(4900, count($response[4]->ids));     // Number of followers should be 5000, but blocked users
        $this->assertLessThanOrEqual(5000, count($response[4]->ids)); //  are taken out of the list after pagination
        $this->assertGreaterThan(0, $response[4]->next_cursor);

        $this->assertArrayHasKey(5, $response);
        $this->assertEquals($text, $response[5]->text);
    }

    /**
     * Test getting a tweet entity using multiRequest.
     * 
     * @depends testDoMultiRequest
     */
    public function testDoMultiRequest_TweetEntity()
    {
        $response = $this->connection->doMultiRequest(array(
            array('url' => 'statuses/show/:id', 'params' => array(':id' => '231230427510239232')),
            array('url' => 'users/show', 'params' => array('screen_name' => 'JasnyArnold')),
            array('url' => 'followers/ids', 'params' => array('screen_name' => 'JasnyArnold'))
        ));

        if ($this->connection->getMultiRequestErrors()) $this->fail(join("\n", $this->connection->getMultiRequestErrors()));
        
        $this->assertInternalType('array', $response);
        $this->assertCount(3, $response);

        $this->assertInstanceOf('Social\Twitter\Tweet', $response[0]);
        $this->assertEquals('231230427510239232', $response[0]->id);
        $this->assertEquals("Cool! You're testing #JasnySocialTwitterSearch", $response[0]->text);

        $this->assertInstanceOf('Social\Twitter\User', $response[1]);
        $this->assertEquals('89494775', $response[1]->id);
        $this->assertEquals("JasnyArnold", $response[1]->screen_name);
        $this->assertEquals("Jasny Test account", $response[1]->name);

        $this->assertInstanceOf('Social\Twitter\Collection', $response[2]);
        $this->assertInstanceOf('Social\Twitter\User', $response[2][0]);
    }

    /**
     * Test search().
     * 
     * @depends testConstruct
     */
    public function testSearch()
    {
        $tag = "#facebook";
        $response = $this->connection->search($tag, array(), false);

        $this->assertEquals($tag, urldecode($response->search_metadata->query));
        $this->assertInternalType('array', $response->statuses);
        $this->assertObjectHasAttribute('text', $response->statuses[0]);
        $this->assertContains("#facebook", strtolower($response->statuses[0]->text));
    }

    /**
     * Test searchUsers().
     */
    public function testSearchUsers()
    {
        $response = $this->connection->searchUsers('JasnyArnold', array(), false);
        $this->assertEquals("89494775", $response[0]->id_str);
        $this->assertEquals("Jasny Test account", $response[0]->name);
    }

    /**
     * Test me().
     * 
     * The properties of Me are not fetched. @see Social\Twitter\MeTest for that.
     */
    public function testMe()
    {
        $this->assertInstanceOf('Social\Twitter\Me', $this->connection->me());
        $this->assertAttributeSame($this->connection, '_connection', $this->connection->me());
        $this->assertAttributeEquals(true, '_stub', $this->connection->me());
    }

    /**
     * Test entity().
     */
    public function testEntity()
    {
        $this->assertInstanceOf('Social\Twitter\Me', $this->connection->entity('me'));
        $this->assertInstanceOf('Social\Twitter\User', $this->connection->entity('user'));
        $this->assertInstanceOf('Social\Twitter\Tweet', $this->connection->entity('tweet'));
        $this->assertInstanceOf('Social\Twitter\DirectMessage', $this->connection->entity('direct_message'));
        $this->assertInstanceOf('Social\Twitter\UserList', $this->connection->entity('user_list'));
        $this->assertInstanceOf('Social\Twitter\SavedSearch', $this->connection->entity('saved_search'));
        $this->assertInstanceOf('Social\Twitter\Place', $this->connection->entity('place'));
    }

    /**
     * Test entity() more extensively
     */
    public function testEntity_User()
    {
        $user = $this->connection->entity('user');
        $this->assertAttributeSame($this->connection, '_connection', $user);
        $this->assertAttributeEquals(true, '_stub', $user);
        $this->assertObjectNotHasAttribute('user_id', $user);

        $user = $this->connection->entity('user', 12345);
        $this->assertAttributeSame($this->connection, '_connection', $user);
        $this->assertAttributeEquals(true, '_stub', $user);
        $this->assertAttributeEquals(12345, 'user_id', $user);

        $user = $this->connection->entity('user', array('user_id' => 12345, 'screen_name' => "FooBar"));
        $this->assertAttributeSame($this->connection, '_connection', $user);
        $this->assertAttributeEquals(true, '_stub', $user);
        $this->assertAttributeEquals(12345, 'user_id', $user);
        $this->assertAttributeEquals("FooBar", 'screen_name', $user);

        $this->assertAttributeEquals(false, '_stub', $this->connection->entity('user', array('user_id' => 12345, 'screen_name' => "FooBar"), false));
        $this->assertAttributeEquals(true, '_stub', $this->connection->entity('@user', array('user_id' => 12345, 'screen_name' => "FooBar"), false));
    }

    /**
     * Test entity() for a non-existant type
     */
    public function testEntity_Exception()
    {
        $this->setExpectedException('Social\Exception', "Unable to create a Twitter entity: unknown entity type 'foo'");
        $this->connection->entity('foo');
    }

    /**
     * Test collection().
     * 
     * @see Social\Twitter\CollectionTest for more collection tests.
     */
    public function testCollection()
    {
        $collection = $this->connection->collection('user', array(1, 2));
        
        $this->assertInstanceOf('Social\Twitter\Collection', $collection);
        $this->assertSame($this->connection, $collection->getConnection());
        $this->assertInstanceOf('Social\Twitter\User', $collection[0]);
        $this->assertEquals(1, $collection[0]->user_id);
        $this->assertInstanceOf('Social\Twitter\User', $collection[1]);
        $this->assertEquals(2, $collection[1]->user_id);
    }

    /**
     * Test user().
     * 
     * @see Social\Twitter\CollectionTest for more user tests.
     */
    public function testUser()
    {
        $user = $this->connection->user(12345);
        $this->assertInstanceOf('Social\Twitter\User', $user);
        $this->assertAttributeSame($this->connection, '_connection', $user);
        $this->assertAttributeEquals(true, '_stub', $user);
        $this->assertAttributeEquals(12345, 'user_id', $user);

        $user = $this->connection->user(array('user_id' => 12345, 'screen_name' => "FooBar"), false);
        $this->assertInstanceOf('Social\Twitter\User', $user);
        $this->assertAttributeSame($this->connection, '_connection', $user);
        $this->assertAttributeEquals(false, '_stub', $user);
        $this->assertAttributeEquals(12345, 'user_id', $user);
        $this->assertAttributeEquals("FooBar", 'screen_name', $user);
    }

    /**
     * Test convertData().
     */
    public function testConvertData()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement test__sleep().
     */
    public function test__sleep()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

}
