# PinterestPinner PHP Class

~~Pinterest API is not released yet, so there is no way to programmatically create a pin. So here is this class for - Autoposter, Autopinner, whatever you like to call it.~~

Please see [the official Pinterest API](https://developers.pinterest.com/docs/getting-started/introduction/) before trying this one.

**This is an unofficial API, and likely to change and break at any moment.**

_PinterestPinner is not a way to avoid any Pinterest terms, conditions, rules and regulations. Please use the class in accordance with all Pinterest rules. If you abuse the service you will be banned there._

**Please follow the PSR-2 coding standards if you would like to create a pull request.**

## Installation

You can easily install PinterestPinner with the following command:

`composer require dzafel/pinterest-pinner:2.*`

or alternatively, include a dependency for `dzafel/pinterest-pinner` in your `composer.json` file. For example:

```json
{
    "require": {
        "dzafel/pinterest-pinner": "2.*"
    }
}
```

## How to use it?

To add a new pin:

```php
try {
    $pinterest = new \PinterestPinner\Pinner;
    $pin_id = $pinterest->setLogin('Your Pinterest Login')
        ->setPassword('Your Pinterest Password')
        ->setBoardID('Pinterest Board ID')
        ->setImage('Image URL')
        ->setDescription('Pin Description')
        ->setLink('Pin Link')
        ->pin();
} catch (\PinterestPinner\PinnerException $e) {
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

## Changelog

### 2.0.7 (2016-09-22)

- FIX: `composer.json` version fix

### 2.0.6 (2016-09-21)

- FIX: `getUserData()` should load the user details from `tree > data` array path instead of `resourceDataCache` ([#21](/../../issues/21))

### 2.0.5 (2016-06-11)

- Added Guzzle 6 support (required: `>=5.0`)

### 2.0.4 (2016-03-12)

- FIX: `_responseToArray()` now search for config JSON in `<script id="jsInit1">` instead of `P.main.start()` function ([#17](/../../issues/17))

### 2.0.3 (2015-11-11)

- FIX: new `getBoards()` logic, now it returns all boards instead of just first 50 ([#16](/../../issues/16))

### 2.0.2 (2015-10-05)

- FIX: typo in init function - `P.start.start` instead of `P.main.start` ([#15](/../../issues/15))
- FIX: `getBoards()` always returned empty array, because `getPins()` returned pins collection, not full response json ([#15](/../../issues/15))

### 2.0.1 (2015-09-23)

- FIX: init function name changed from `P.scout.init` to `P.main.start` ([#14](/../../issues/14))
- FIX: do `preg_match()` only if response value is a string
- NEW: added public `$user_data` variable
- NEW: changed some private methods and vars to protected so class can be extended

### 2.0 (2015-04-09)

- NEW: Library is now composer friendly
- NEW: Added Guzzle dependency

### 1.0.1 (2014-11-02)

- FIX: reload CSRF token upon login

### 1.0 (2014-06-04)

- Initial release
