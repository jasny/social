# Social
A webservice API abstraction layer for PHP

### Consistent interface
Have a consistent way to develop for Facebook, Twitter, Google and other webservice APIs.

### Parallel execution
Fetch data and execute methods for whole collections at once, allowing your application to respond much faster than with other libraries.

## Examples

### Tweet hello world

```php
use Social\Twitter;

$twitter = new Twitter\Connection($key, $secret, $_SESSION);
$twitter->auth();

echo "<h1>Hi ", $twitter->me()->name, "</h1>";

$twitter->post("status/update", ["status"=>"Hello world!"]);
```

### Display all my photos from Facebook

```php
use Social\Facebook;

$facebook = new Facebook\Connection($key, $secret, $_SESSION);
$facebook->auth();

echo "<h1>Hi ", $facebook->me()->name, "</h1>";

$albums = $facebook->get('me/albums');

$facebook->prepare();
foreach ($albums as $album) {
    $facebook->get("{$album->id}/photos");
}
$results = $facebook->execute();

foreach ($results as $i=>$photos) {
    echo "<h2>", $albums[$i]->name, "</h2>";
    foreach ($photos as $photo) {
        echo "<img src='", $photo->picture, "'>";
    }
}
```
