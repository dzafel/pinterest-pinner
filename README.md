# PinterestPinner PHP Class

Pinterest API is not released yet, so there is no way to programmatically create a pin. So here is this class for - Autoposter, Autopinner, whatever you like to call it.

**This is an unofficial API, and likely to change and break at any moment.**

_PinterestPinner is not a way to avoid any Pinterest terms, conditions, rules and regulations. Please use the class in accordance with all Pinterest rules. If you abuse the service you will be banned there._

**Please follow the PSR-2 coding standards if you want to create a pull request.**


## How to use it?

To add a new pin:

```php
try {
    $pinterest = new PinterestPinner\Pinner;
    $pin_id = $pinterest->setLogin('Your Pinterest Login')
        ->setPassword('Your Pinterest Password')
        ->setBoardID('Pinterest Board ID')
        ->setImage('Image URL')
        ->setDescription('Pin Description')
        ->setLink('Pin Link')
        ->pin();
} catch (PinterestPinner\PinnerException $e) {
    echo $e->getMessage();
}
```

You can also get additional info:

```php
// Get a list of boards
$boards = $pinterest->getBoards();

// Get a list of pins
$pins = $pinterest->getPins();

// Get logged in user data
$user = $pinterest->getUserData();
```

## Version history

### 2.0 (2015-04-09)

- Library is now composer friendly
- Added Guzzle dependency

### 1.0.1 (2014-11-02)

- FIX: reload CSRF token upon login

### 1.0 (2014-06-04)

- Initial release
