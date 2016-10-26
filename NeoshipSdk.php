<?php

namespace Neonus\Neoship;

use Pest;
use PestJSON;
use DateTime;

class NeoshipSdk
{
    /**
     * Client ID, used for authentication and is supplied to customer
     * @var [string]
     */
    private $clientId;

    /**
     * Client secret, used for authentication and is supplied to customer
     * @var [type]
     */
    private $clientSecret;

    /**
     * URI to which you'll be redirected after authentication
     * @var [string]
     */
    private $redirectUri;

    /**
     * Value returned after logging in, used for getting oauth token
     * @var [string]
     */
    private $code;

    /**
     * Value used for all API calls in single session
     * @var [string]
     */
    private $token;

    /**
     * URL for API.
     * @var [string]
     */
    private $apiUrl = 'https://www.neoship.sk';
    
    /**
     * constructor
     * @param [string] $clientId     supplied to customer, used for authentication
     * @param [string] $clientSecret supplied to customer, used for authentication
     * @param [string] $redirectUri  address to which API will redirect
     * @param [string] $apiUrl       URL of Neoship API
     */
    public function __construct($clientId = null, $clientSecret = null, $redirectUri = null, $apiUrl = null)
    {
        if ($clientId) {
            $this->setClientId($clientId);
        }
        if ($clientSecret) {
            $this->setClientSecret($clientSecret);
        }
        if ($redirectUri) {
            $this->setRedirectUri($redirectUri);
        }
        if ($apiUrl) {
            $this->setApiUrl($apiUrl);
        }

        // Initialize session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function getClientId()
    {
        return $this->clientId;
    }
    
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }
    
    public function getClientSecret()
    {
        return $this->clientSecret;
    }
    
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }
    
    public function getRedirectUri()
    {
        return $this->redirectUri;
    }
    
    public function setRedirectUri($redirectUri)
    {
        $this->redirectUri = $redirectUri;
    }
    
    public function getCode()
    {
        return $this->code;
    }
    
    public function setCode($code)
    {
        $this->code = $code;
    }
    
    public function getToken()
    {
        if (isset($this->token)) {
            if ($this->token->expiration_time > new DateTime('now')) {
                return $this->token;
            }
        } elseif ($oauth = $this->getOauth()) {
            if ($oauth['expiration_time'] > new DateTime('now')) {
                return (object) array(
                    'access_token'    => $oauth['access_token'],
                    'expires_in'      => $oauth['expires_in'],
                    'token_type'      => $oauth['token_type'],
                    'scope'           => $oauth['scope'],
                    'refresh_token'   => $oauth['refresh_token'],
                    'expiration_time' => $oauth['expiration_time'],
                );
            }
        }
    }
    
    public function setToken($token)
    {
        $this->token = $token;
        $this->token->expiration_time = new DateTime('now');
        date_add($this->token->expiration_time, date_interval_create_from_date_string($this->token->expires_in-60 . ' seconds'));
    }
    
    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    public function setApiUrl($url)
    {
        $this->apiUrl = $url;
    }

    private function getRestUrl()
    {
        return $this->getApiUrl() . '/api/rest';
    }

    private function getOauthUrl()
    {
        return $this->getApiUrl() . '/oauth/v2';
    }

    private function persistOauth($data)
    {
        $_SESSION['oauth'] = $data;
    }

    private function getOauth()
    {
        if (isset($_SESSION['oauth'])) {
            return $_SESSION['oauth'];
        } else {
            return false;
        }
    }

    /**
     * authorizes client and provides token and code for further communication
     * @return [boolean] returns whether user gets succesfully authenticated
     */
    public function authorize()
    {
        if (!$this->getCode() && !$this->getToken()) {
            if (!isset($_GET['code'])) {
                $pest = new PestJSON($this->getRestUrl());
                $url = "/auth?client_id=" . $this->clientId . "&response_type=code&redirect_uri=" . urlencode($this->redirectUri);
                header('Location: ' . $this->getOauthUrl() . $url);
                exit;
            } else {
                $this->setCode($_GET['code']);
            }
        }
        if (!$this->getToken()) {
            $url = "/token?client_id=" . $this->clientId .
                "&client_secret=" . $this->clientSecret .
                "&grant_type=authorization_code&code=" . $this->getCode() .
                "&redirect_uri=" . urlencode($this->redirectUri);
            $pestauth = new Pest($this->getOauthUrl());
            try {
                $this->setToken(json_decode($pestauth->get($url)));
            } catch (Pest_ClientError $ex) {
                throw $ex;
            }
        }
        if ($this->getToken()) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Calls NeoShip API
     * @param  [string] $apiName address of called API (eg. '/package/count')
     * @param  [string] $method  HTTP method of API call
     * @param  [array]  $data    data to be sent alongside API call
     * @return [string]          API response
     */
    private function apiCall($apiName, $method, $data = null)
    {
        if ($this->authorize()) {
            $token = $this->getToken();
            $oauth["access_token"]= $token->access_token;
            $oauth["expires_in"]= $token->expires_in;
            $oauth["token_type"]= $token->token_type;
            $oauth["scope"]= $token->scope;
            $oauth["refresh_token"]= $token->refresh_token;
            $oauth["expiration_time"]= $token->expiration_time;
            
            if ($data && $method == 'get') {
                $oauth = array_merge($oauth, $data);
            }

            $this->persistOauth($oauth);
            
            $pest = new Pest($this->getRestUrl());
            try {
                unset($_SESSION['data1']);
                unset($_SESSION['data2']);
                switch (strtolower($method)) {
                    case 'get':
                        $results = $pest->get($apiName . '?' . http_build_query($oauth));
                        break;
                    case 'post':
                        $results = $pest->post($apiName . '?' . http_build_query($oauth), $data);
                        break;
                    case 'delete':
                        $results = $pest->delete($apiName . '?' . http_build_query($oauth));
                        break;
                    case 'put':
                        $results = $pest->put($apiName . '?' . http_build_query($oauth), $data);
                        break;
                    default:
                        break;
                }
                return $results;
            } catch (Pest_ClientError $ex) {
                throw $ex;
            }
        }
    }
    
    /**
     * Returns all log entries for user
     * @return [object]
     */
    public function apiGetLog()
    {
        $_SESSION['apiName'] = 'getLog';
        return json_decode($this->apiCall('/log/', 'get'));
    }
    
    /**
     * Returns count of log entries for user
     * @return [int]
     */
    public function apiGetLogCount()
    {
        $_SESSION['apiName'] = 'getLogCount';
        return json_decode($this->apiCall('/log/count', 'get'));
    }
    
    /**
     * Returns requested page of log entries (each page has 200 entries)
     * @param  [int]     $pageNum  page number
     * @return [object]
     */
    public function apiGetLogPage($pageNum)
    {
        $_SESSION['apiName'] = 'getLogPage';
        $_SESSION['data1'] = $pageNum;
        return json_decode($this->apiCall('/log/page/' . $pageNum, 'get'));
    }
    
    /**
     * Returns current user
     * @return [object]
     */
    public function apiGetUser()
    {
        $_SESSION['apiName'] = 'getUser';
        return json_decode($this->apiCall('/user/', 'get'));
    }
    
    /**
     * Returns list of all states
     * @return [object]
     */
    public function apiGetState()
    {
        $_SESSION['apiName'] = 'getState';
        return json_decode($this->apiCall('/state/', 'get'));
    }
    
    /**
     * Returns list of all currencies
     * @return [object]
     */
    public function apiGetCurrency()
    {
        $_SESSION['apiName'] = 'getCurrency';
        return json_decode($this->apiCall('/currency/', 'get'));
    }
    
    /**
     * Returns list of statuses of package with given ID
     * @param  [int]      $id   package id
     * @return [object]
     */
    public function apiGetStatus($id)
    {
        $_SESSION['apiName'] = 'getStatus';
        $_SESSION['data1'] = $id;
        return json_decode($this->apiCall('/status/' . $id, 'get'));
    }
    
    /**
     * Returns package with given ID, returns all for current user if no ID is given
     * @param  [int]      $id   package id
     * @param  [array]      $ref   packages
     * @return [object]
     */
    public function apiGetPackage($id = null, $ref = NULL)
    {
        $_SESSION['apiName'] = 'getPackage';
        $_SESSION['data1'] = $id;
        $_SESSION['data2'] = $ref;
        return json_decode($this->apiCall('/package/' . $id, 'get', ['ref' => $ref]));
    }
    
    /**
     * Returns count of packages for current user
     * @return [int]
     */
    public function apiGetPackageCount()
    {
        $_SESSION['apiName'] = 'getPackageCount';
        return json_decode($this->apiCall('/package/count', 'get'));
    }
    
    /**
     * Returns requested page of packages (each page has 50 packages)
     * @param  [int]     $pageNum    page number
     * @return [object]
     */
    public function apiGetPackagePage($pageNum)
    {
        $_SESSION['apiName'] = 'getPackagePage';
        $_SESSION['data1'] = $pageNum;
        return json_decode($this->apiCall('/package/page/' . $pageNum, 'get'));
    }
    
    /**
     * Calculates price of package
     * @param  [array]   $package   array of package info, refer to http://neoship.sk/help/api-volania#package for array content
     * @return [object]              prices and selling prices
     */
    public function apiPostPackagePrice($package)
    {
        $_SESSION['apiName'] = 'postPackagePrice';
        $_SESSION['data1'] = $package;
        return json_decode($this->apiCall('/package/price', 'post', $package));
    }
    
    /**
     * Creates new package
     * @param  [array]   $package    array of package info, refer to http://neoship.sk/help/api-volania#package for array content
     * @return [null]
     */
    public function apiPostPackage($package)
    {
        $_SESSION['apiName'] = 'postPackage';
        $_SESSION['data1'] = $package;
        return json_decode($this->apiCall('/package/', 'post', $package));
    }
    /**
     * Edits existing package
     * @param  [type]    $id         id of package to edit
     * @param  [array]   $package    array of package info, refer to http://neoship.sk/help/api-volania#package for array content
     * @return [null]
     */
    public function apiPutPackage($id, $package)
    {
        $_SESSION['apiName'] = 'putPackage';
        $_SESSION['data1'] = $id;
        $_SESSION['data2'] = $package;
        return json_decode($this->apiCall('/package/' . $id, 'put', $package));
    }
    
    /**
     * Deletes package
     * @param  [string]  $id   id of package to delete
     * @return [null]
     */
    public function apiDeletePackage($id)
    {
        $_SESSION['apiName'] = 'deletePackage';
        $_SESSION['data1'] = $id;
        return json_decode($this->apiCall('/package/' . $id, 'delete'));
    }
    
    /**
     * Saves sticker PDF to file or outputs it to browser for download
     * @param  [array]  $varNums  variable numbers of packages
     * @param  [string] $filename file for PDF to be written in, downloads file if no filename is given
     * @return [mixed]            returns whether write to file was succesfull
     */
    public function apiGetPackageSticker($varNums, $filename = null)
    {
        $_SESSION['apiName'] = 'getPackageSticker';
        $_SESSION['data1'] = $varNums;
        if ($filename) {
            $_SESSION['data2'] = $filename;
        }
        $result = $this->apiCall('/package/sticker', 'get', ['ref' => $varNums]);
        if ($filename) {
            if (file_put_contents($filename, $result, LOCK_EX)>0) {
                return true;
            } else {
                return false;
            }
        } else {
            header("Content-type:application/pdf");
            header("Content-Disposition:attachment;filename=stickers-" . date('Y-m-d'));
            echo($result);
            die();
        }
    }
    
    /**
     * Saves acceptance PDF to file or outputs it to browser for download
     * @param  [array]  $varNums  variable numbers of packages
     * @param  [string] $filename filename/path for PDF to be written, downloads file if no filename is given
     * @return [mixed]            returns whether write to file was succesfull
     */
    public function apiGetPackageAcceptance($varNums, $filename = null)
    {
        $_SESSION['apiName'] = 'getPackageAcceptance';
        $_SESSION['data1'] = $varNums;
        if ($filename) {
            $_SESSION['data2'] = $filename;
        }
        $result = $this->apiCall('/package/acceptance', 'get', ['ref' => $varNums]);
        if ($filename) {
            if (file_put_contents($filename, $result, LOCK_EX)>0) {
                return true;
            } else {
                return false;
            }
        } else {
            header("Content-type:application/pdf");
            header("Content-Disposition:attachment;filename=acceptance-" . date('Y-m-d'));
            echo($result);
            die();
        }
    }
    
    /**
     * Returns packagemat with given ID, returns all for current user if no ID is given
     * @param  [int]      $id   packagemat id
     * @return [object]
     */
    public function apiGetPackagemat($id = null)
    {
        $_SESSION['apiName'] = 'getPackagemat';
        $_SESSION['data1'] = $id;
        return json_decode($this->apiCall('/packagemat/' . $id, 'get'));
    }
    
    /**
     * Returns list of packagemat boxes
     * @return [object]
     */
    public function apiGetPackagematBoxes()
    {
        $_SESSION['apiName'] = 'getPackagematBoxes';
        return json_decode($this->apiCall('/packagemat/boxes', 'get'));
    }
    
    public function apiSessionCall($apiName = null, $data1 = null, $data2 = null)
    {
        if (isset($_SESSION['apiName']) && $apiName === null) {
            $apiName = $_SESSION['apiName'];
        }
        if (isset($_SESSION['data1']) && $data1 === null) {
            $data1 = $_SESSION['data1'];
        }
        if (isset($_SESSION['data2']) && $data2 === null) {
            $data2 = $_SESSION['data2'];
        }
        switch ($apiName) {
            case 'getLog':
                return $this->apiGetLog();
                break;
            case 'getLogCount':
                return $this->apiGetLogCount();
                break;
            case 'getLogPage':
                return $this->apiGetLogCount($data1);
                break;
            case 'getUser':
                return $this->apiGetUser();
                break;
            case 'getState':
                return $this->apiGetState();
                break;
            case 'getCurrency':
                return $this->apiGetCurrency();
                break;
            case 'getStatus':
                return $this->apiGetStatus($data1);
                break;
            case 'getPackage':
                return $this->apiGetPackage($data1, $data2);
                break;
            case 'getPackageCount':
                return $this->apiGetPackageCount();
                break;
            case 'getPackagePage':
                return $this->apiGetPackagePage($data1);
                break;
            case 'postPackagePrice':
                return $this->apiPostPackagePrice($data1);
                break;
            case 'postPackage':
                return $this->apiPostPackage($data1);
                break;
            case 'putPackage':
                return $this->apiPutPackage($data1, $data2);
                break;
            case 'deletePackage':
                return $this->apiDeletePackage($data1);
                break;
            case 'getPackageSticker':
                return $this->apiGetPackageSticker($data1, $data2);
                break;
            case 'getPackageAcceptance':
                return $this->apiGetPackageAcceptance($data1, $data2);
                break;
            case 'getPackagemat':
                return $this->apiGetPackagemat($data1);
                break;
            case 'getPackagematBoxes':
                return $this->apiGetPackagematBoxes();
                break;
        }
        return false;
    }
}
