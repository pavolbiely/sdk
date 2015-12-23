# SDK for Neoship API

Simple SDK for communication with Neoship API.

## Requirements

  * API ID
  * API secret passphrase
  * redirect URL

You need to contact our tech support at podpora@neoship.sk for these credentials.

## Example code
```php
use Neonus\Neoship\NeoshipSdk;

$clientId = '2_osoowoogoo0gwoko00kggkws';
$clientSecret = '34cooc0480c4scw0gkko48wcsk444g';
$redirectUri = 'http://www.example.com/neoship/redirect.php';

$neoship = new NeoshipSdk($clientId, $clientSecret, $redirectUri);

// create new package
$neoship->apiPutPackage('40272', array(
     'package' => array(
         'sender' => array(
             'appelation' => 'Mr',
             'name' => 'Jeffese',
             'company' => 'Jeffe S.r.o.',
             'street' => 'Slnecna',
             'city' => 'Namestovo',
             'houseNumber' => '158',
             'houseNumberExt' => null,
             'zIP' => '02901',
             'state' => 1,
             'email' => null,
             'phone' => null,
         ),
         'reciever' => array(
             'appelation' => 'Mr',
             'name' => 'Jeffese',
             'company' => 'Jeffe S.r.o.',
             'street' => 'Slnecna',
             'city' => 'Namestovo',
             'houseNumber' => '158',
             'houseNumberExt' => null,
             'zIP' => '02901',
             'state' => 1,
             'email' => null,
             'phone' => null,
         ),
     ),
 ));
