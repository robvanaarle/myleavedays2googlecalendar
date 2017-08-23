<?php

namespace myleavedays;

class MyLeaveDaysClient {
  protected $baseUrl = 'https://members.myleavedays.com';
  public $httpClient;
  
  const LEAVE_REQUESTS_GROUP = 1;
  const LEAVE_REQUESTS_PREFERRED_LIST = 2;
  
  public function __construct() {
    $this->httpClient = new \ultimo\net\http\Client(new \ultimo\net\http\adapters\Curl());
    $this->httpClient->setCookieJar(new \ultimo\net\http\CookieJar());
    $this->httpClient->setHeader("User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:46.0) Gecko/20100101 Firefox/46.0");
    $this->httpClient->setHeader("Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8");
    $this->httpClient->setHeader("Accept-Language: en-US,en;q=0.5");
    //$this->httpClient->setProxy("127.0.0.1:8888");
  }
  
  public function login($username, $password, $organisationCode) {
    //return true;
    // ensure session is initalised
    $this->httpClient->get($this->baseUrl . "/inloggen.asp"); // Not necessary
    $this->httpClient->post($this->baseUrl . "/default.asp"); // Not necessary
    $this->httpClient->post($this->baseUrl . "/Include/Functies/setuserdevice.asp", array('ismobile' => 'false', 'istablet' => 'false', 'isdesktop' => 'true'));

    $request = new \ultimo\net\http\XRequest($this->baseUrl . "/inloggen.asp", \ultimo\net\http\Request::METHOD_POST);
    $request->setPostParams(array(
      'languageid' => '2',
      'gebruikersnaam' => $username,
      'wachtwoord1' => $password,
      'bedrijfscode' => $organisationCode,
      'Aanmelden' => 'Login'
    ));
    $request->setHeader("Referer: {$this->baseUrl}/inloggen.asp");
    $response = $this->httpClient->request($request);

    //echo "Login response length: " . strlen($response) . "\n";

    return strpos($response->getBody(), "Login error") === false;
  }
  
  public function getLeaveRequests($year, $selection_id, $selectionOption=self::LEAVE_REQUESTS_GROUP) {   
    $params = array(
      'overzichtid' => '5',
      'selectieoptie' => $selectionOption,
      'jaarnr' => $year,
      'groep' => $selectionOption == static::LEAVE_REQUESTS_GROUP ? $selection_id : '0',
      'werknemernr' => '0',
      'maandvannr' => '1',
      'maandtmnr' => 12,
      'voorkeurslijstid' => $selectionOption == static::LEAVE_REQUESTS_PREFERRED_LIST ? $selection_id : '-1'
    );
    
    // Set filter
    $request = new \ultimo\net\http\XRequest($this->baseUrl . "/export/excel_verlofverzoekenfilter.asp", \ultimo\net\http\Request::METHOD_POST);
    $request->setPostParams($params);
    $request->setHeader("Referer: {$this->baseUrl}/export/excel_verlofverzoekenfilter.asp");
    $response = $this->httpClient->request($request);
    
    // Download export
    $request = new \ultimo\net\http\XRequest($this->baseUrl . "/export/excel_verlofverzoeken.asp", \ultimo\net\http\Request::METHOD_POST);
    $request->setPostParams($params);
    $request->setHeader("Referer: {$this->baseUrl}/export/excel_verlofverzoekenfilter.asp");
    $response = $this->httpClient->request($request);
    
    return LeaveRequest::fromResponse($response->getBody());
  }
}
