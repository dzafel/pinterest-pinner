# PinterestPinner PHP Class

Pinterest API is not released yet, so there is no way to programmatically create a pin. So here is this class for.

**This is an unofficial API, and likely to change and break at any moment.**

_PinterestPinner is not a way to avoid any Pinterest terms, conditions, rules and regulations. Please use the class in accordance with all Pinterest rules. If you abuse the service you will be banned there._

## How to use it?

Short method:

```php
require_once 'PinterestPinner.php';
$pinterest = new PinterestPinner('Your Login', 'Your Password');
$result = $pinterest->pin('Board ID', 'Image URL', 'Description', 'URL');
```

Another short method:

```php
require_once 'PinterestPinner.php';
$pinterest = new PinterestPinner('Your Login', 'Your Password');
$result = $pinterest->pin(array(
    'board' => 'Board ID',
    'image' => 'Image URL',
    'description' => 'Description',
    'link' => 'URL',
));
```

Chained method:

```php
require_once 'PinterestPinner.php';
$pinterest = new PinterestPinner;
$pinterest->setLogin('Your Pinterest Login')
    ->setPassword('Your Pinterest Password')
    ->setBoardID('Pinterest Board ID')
    ->setImage('Image URL')
    ->setDescription('Pin Description')
    ->setLink('Pin Link')
    ->pin();
```

Then to check if pin has been added successfully:

```php
if (false === $result) {
    echo 'Error: ' . $pinterest->getError();
}
else {
    echo 'Pin Created, ID: ' . $pinterest->getPinID();
}
```

You can also specify cURL options by adding third argument to constructor, i.e.:

```php
$pinterest = new PinterestPinner('Your Login', 'Your Password', array(
    CURLOPT_COOKIEFILE     => 'cookie.txt',
    CURLOPT_COOKIEJAR      => 'cookie.txt',
    CURLOPT_CONNECTTIMEOUT => 30,
    CURLOPT_TIMEOUT        => 30,
));
```

## Requirements

- PHP 5.2.1
- cURL

## Version history

### 1.0 (2014-06-04)

- Initial release
