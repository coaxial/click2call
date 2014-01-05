<?php
$myName = 'Pierre';
$myNumber = '15551231234';

$asteriskHost = 'pbx.example.com';
$asteriskAmiPort = 5039;
$asteriskUsername = 'webcallback';
$asteriskPassword = 'secret';
$connTimeout = 3;

$debug = false;

function checkExecution($socket, $command) {
  $line = '';
  $response = '';
  $startTime = time();
  while ($line != "\r\n" && time() < ($startTime + $GLOBALS['connTimeout'])) {
    $line = fgets($socket, 128);
    $response .= $line;
  }
  if (preg_match('/Response\: (Success|Goodbye)/', $response)) {
    if ($command === 'login') {
      if ($GLOBALS['debug']) echo "login OK\n";
      return true;
    }
    if ($command === 'orig') {
      if ($GLOBALS['debug']) echo "orig OK\n";
      return true;
    }
    if ($command === 'logoff') {
      if ($GLOBALS['debug']) echo "logoff OK\n";
      return true;
    }
  } else {
    fclose($socket);
    if ($GLOBALS['debug']) echo "$command NOK\n";
    return false;
  }
}

class phoneNumber {
  public $digits;
  public $isValid;
  public function checkNumber() {
    $this->digits = preg_replace('/\((\d{3})\)\s(\d{3})\-(\d{4})/', '${1}${2}${3}', $this->digits);
    if (preg_match('/\d{10}/', $this->digits)) {
      if (!preg_match('/^900/', $this->digits)) {
        $this->digits = '1' . $this->digits;
        $this->isValid = true;
        return true;
      } else {
        $this->isValid = false;
        return false;
      }
    } else {
      $this->isValid = false;
      return false;
    }
  }
}

class apiResult {
  public $socket = false;
  public $amiLogin = false;
  public $amiOrig = false;
  public $amiLogoff = false;
  public $callSpooled = false;
}

if ($debug) echo "Raw number is: " . $_POST["callerNumber"] . "\n";

$callerNumber = new phoneNumber;
$callerNumber->digits = $_POST["callerNumber"];
$callerNumber->checkNumber();
$lang = new StdClass();
$lang->is = $_POST["lang"];

if ($debug) echo "Number to call is $callerNumber->digits and language is $lang->is\n";

if ($lang->is == 'FR' || $lang->is == 'EN') {
  // lang is valid
  $lang->isValid = true;
} else {
  // lang is not valid
  $lang->isValid = false;
}

if ($debug) echo "callerNumber valid: $callerNumber->isValid\ncallerNumber digits: $callerNumber->digits\n";
if ($debug) echo "lang is: $lang->is\nlang is valid: $lang->isValid\n";

if ($lang->isValid && $callerNumber->isValid) {
  // creating the jsonResult object
  $jsonResult = new apiResult();
  // putting together the login message
  $amiLoginMsg = "Action: login\r\nEvents: off\r\nUsername: $asteriskUsername\r\nSecret: $asteriskPassword\r\n\r\n";
  // putting together the originate message
  $amiOrigMsg = "Action: originate\r\nChannel: SIP/cwu-pierre/$callerNumber->digits\r\nCallerID: \"$myName\" <$myNumber>\r\nMax Retries: 2\r\nRetry Time: 10\r\nWait Time: 1\r\nContext: webcallback\r\nExten: callMe$lang->is\r\nPriority: 1\r\nAsync: yes\r\n\r\n";
  // putting together the logoff message
  $amiLogoffMsg = "Action: logoff\r\n\r\n";

  if ($debug) echo "AMI Login Msg: $amiLoginMsg\nAMI Orig Msg: $amiOrigMsg\nAMI Logoff Msg: $amiLogoffMsg\n";

  $socket = fsockopen('tls://'.$asteriskHost, $asteriskAmiPort, $errno, $errstr, $connTimeout);

  if ($socket) {
    $jsonResult->socket = true;
    if ($jsonResult->socket) {
      fputs($socket, $amiLoginMsg);
      $jsonResult->amiLogin = checkExecution($socket, 'login');
    }

    if ($jsonResult->amiLogin) {
      fputs($socket, $amiOrigMsg);
      $jsonResult->amiOrig = checkExecution($socket, 'orig');
    }

    if ($jsonResult->amiOrig) {
      fputs($socket, $amiLogoffMsg);
      $jsonResult->amiLogoff = checkExecution($socket, 'logoff');
    }

    if ($jsonResult->amiLogin && $jsonResult->amiOrig && $jsonResult->amiLogoff) {
      $jsonResult->callSpooled = true;
    } else {
      $jsonResult->callSpooled = false;
    }
  } else {
    $jsonResult->socket = false;
    $jsonResult->callSpooled = false;
    if ($debug) echo "-ERR SOCKET: $errno '$errstr'\n";
  }
}

echo json_encode($jsonResult);
?>
