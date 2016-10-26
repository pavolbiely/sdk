<?php
use Neonus\Neoship\NeoshipSdk;

$clientId = '2_osoowoogoo0gwoko00kggkws';
$clientSecret = '34cooc0480c4scw0gkko48wcsk444g';
$redirectUri = 'http://www.example.com/neoship/redirect.php';

$neoship = new NeoshipSdk($clientId, $clientSecret, $redirectUri);

$packagedata = array(
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
);
// edits existing package with ID 40272 via regular API call
$neoship->apiPutPackage($packagedata);


// multi-package api call

$packageCount = 4;
$variableNumber = '123TEST';


for ($i = 1; $i <= $packageCount; $i++) {
    // sets different variable number for each subpackage
    $vn = ($packageCount > 1) ? $variableNumber . $i : $variableNumber;
    $packagedata['package']['variableNumber'] = $vn;
    if (isset($packagedata['package']['mainPackageNumber']) || $i > 1) {
        // setting main package variable number for each subpackage
        $packagedata['package']['mainPackageNumber'] = (isset($packagedata['package']['mainPackageNumber']))? $packagedata['package']['mainPackageNumber'] : $variableNumber . 1;
    }
    // sends each subpackage separately
    $neoship->apiPostPackage('40272', $packagedata);
}
