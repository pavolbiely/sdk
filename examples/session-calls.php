<?php
use Neonus\Neoship\NeoshipSdk;

$clientId = '2_osoowoogoo0gwoko00kggkws';
$clientSecret = '34cooc0480c4scw0gkko48wcsk444g';
$redirectUri = 'http://www.example.com/neoship/redirect.php';

$neoship = new NeoshipSdk($clientId, $clientSecret, $redirectUri);

// edits existing package with ID 40272 via session api call
// when called first time it saves all parameters in session
// so after authorizing and redirecting back to your site, you
// can just call $neoship->apiSessionCall()
// in every other way it's interchangeable with example from regular-calls.php

$neoship->apiSessionCall('putPackage', '40272', array(
    'package' => array(
        'sender' => array(
            'appelation' => 'Mr',
            'name' => 'Jeffe',
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
            'name' => 'Jeffe',
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
