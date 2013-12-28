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
  while ($line != "\r\n") {
    $line = fgets($socket, 128);
    $response .= $line;
  }
  if (preg_match('/Response\: (Success|Goodbye)/', $response)) {
    if ($command === 'login') {
      if ($GLOBALS['debug']) echo "login OK\n";
      return 1;
    }
    if ($command === 'orig') {
      if ($GLOBALS['debug']) echo "orig OK\n";
      return 1;
    }
    if ($command === 'logoff') {
      if ($GLOBALS['debug']) echo "logoff OK\n";
      return 1;
    }
  } else {
    echo "$command NOK\n";
    return 0;
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
  // putting together the login message
  $amiLoginMsg = "Action: login\r\nEvents: off\r\nUsername: $asteriskUsername\r\nSecret: $asteriskPassword\r\n\r\n";
  // putting together the originate message
  $amiOrigMsg = "Action: originate\r\nChannel: SIP/cwu-pierre/$callerNumber->digits\r\nCallerID: \"$myName\" <$myNumber>\r\nMax Retries: 2\r\nRetry Time: 10\r\nWait Time: 1\r\nContext: webcallback\r\nExten: callMe$lang->is\r\nPriority: 1\r\n\r\n";
  // putting together the logoff message
  $amiLogoffMsg = "Action: logoff\r\n\r\n";

  if ($debug) echo "AMI Login Msg: $amiLoginMsg\nAMI Orig Msg: $amiOrigMsg\nAMI Logoff Msg: $amiLogoffMsg\n";

  $socket = fsockopen('tls://'.$asteriskHost, $asteriskAmiPort, $errno, $errstr, $connTimeout);

  fputs($socket, $amiLoginMsg);
  $resultLogin = checkExecution($socket, 'login');
  fputs($socket, $amiOrigMsg);
  $resultOrig = checkExecution($socket, 'orig');
  fputs($socket, $amiLogoffMsg);
  $resultLogoff = checkExecution($socket, 'logoff');
  if ($resultLogin && $resultOrig && $resultLogoff) echo "OK\r\n";
}

echo "\n";
?>

