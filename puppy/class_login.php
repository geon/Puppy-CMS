<?php

// Secure cookie-based log in/out handler.
//   Dependent on user id and time since the last page access (1h).
//   That means a hacker needs to hijack the session within one hour, or it will be just a brute force attack.
class cLogIn{

  // $AccessName defines to where the login is valid.
  function __construct($AccessName){
    $this->AccessName = $AccessName;

    // Refresh the "session". If left out, the login will time out after one hour, regardless if the user have been active or not.
    if($this->IsLogedIn())
      $this->LogIn($this->GetUserID());
  }

  private static $Challenge = 'dldlfdjgffshjkjnasdkfjsfjkhggjdshb,amuvycxvtyvxcbtrvjhrbvewvcfgrvbnrmjklvdyvbtrvsgfvbevh34i7f6g3fg3n7ic7frcib6g34ongufx3m7o';

  // Do it!
  function LogIn($UserID = NULL){
    $Name = 'cLogIn_'.$this->AccessName;
    $Value = $UserID.'_'.sha1($UserID.$this->AccessName.floor(time()/600).cLogIn::$Challenge);
    setcookie($Name, $Value, false, '/', false); // Send the actual cookie.
    $_COOKIE[$Name] = (string) $Value; // Make the cookie visible to scripts within the same request.
  }

  // Do it!
  function LogOut(){
    setcookie('cLogIn_'.$this->AccessName, '', time() - 3600, '/', false);
  }

  // Who is/was loged in?
  function GetUserID(){
    $i = 'cLogIn_'.$this->AccessName;
    // The cookie must be set...
    if(isset($_COOKIE[$i])){
      // ...And there must be something before the '_'.
      $a = explode('_', $_COOKIE[$i]);
      if(isset($a[0]) && strlen($a[0]))
        return $a[0];
    }

    return NULL;
  }

  // Is the user still loged in?
  function IsLogedIn(){
    $i = 'cLogIn_'.$this->AccessName;
    // The cookie must be set...
    if(isset($_COOKIE[$i])){
      // ...And there must be something after the '_'.
      $a = explode('_', $_COOKIE[$i]);
      if(isset($a[1])){
        $Hash = $a[1];

        // Check if the hash matches any 10-minute period during the last hour.
        for($i=0; $i<6; $i++)
          if($Hash == sha1($this->GetUserID().$this->AccessName.floor((time()-$i*600)/600).cLogIn::$Challenge))
            return true;
      }
    }

    // HACKERS!!! Oh, wait, maby it just timed out.
    return false;
  }

}

?>
