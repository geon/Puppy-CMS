<?php

// Secure cookie-based log in/out handler.
//	 Dependent on user id and time since the last page access (1h).
//	 That means a hacker needs to hijack the session within one hour, or it will be just a brute force attack.
class LogIn{

	// $accessName defines to where the login is valid.
	function __construct($accessName) {
		$this->accessName = $accessName;

		// Refresh the "session". If left out, the login will time out after one hour, regardless if the user have been active or not.
		if ($this->isLoggedIn())
			$this->logIn($this->getUserID());
	}

	private static $challenge = 'dldlfdjgffshjkjnasdkfjsfjkhggjdshb,amuvycxvtyvxcbtrvjhrbvewvcfgrvbnrmjklvdyvbtrvsgfvbevh34i7f6g3fg3n7ic7frcib6g34ongufx3m7o';

	// Do it!
	function logIn($userID = NULL) {
		$name = 'LogIn_'.$this->accessName;
		$value = $userID.'_'.sha1($userID.$this->accessName.floor(time()/600).LogIn::$challenge);
		setcookie($name, $value, false, '/', false); // Send the actual cookie.
		$_COOKIE[$name] = (string) $value; // Make the cookie visible to scripts within the same request.
	}

	// Do it!
	function logOut() {
		setcookie('LogIn_'.$this->accessName, '', time() - 3600, '/', false);
	}

	// Who is/was logged in?
	function getUserID() {
		$i = 'LogIn_'.$this->accessName;
		// The cookie must be set...
		if (isset($_COOKIE[$i])) {
			// ...And there must be something before the '_'.
			$a = explode('_', $_COOKIE[$i]);
			if (isset($a[0]) && strlen($a[0]))
				return $a[0];
		}

		return NULL;
	}

	// Is the user still logged in?
	function isLoggedIn() {
		$i = 'LogIn_'.$this->accessName;
		// The cookie must be set...
		if (isset($_COOKIE[$i])) {
			// ...And there must be something after the '_'.
			$a = explode('_', $_COOKIE[$i]);
			if (isset($a[1])) {
				$hash = $a[1];

				// Check if the hash matches any 10-minute period during the last hour.
				for($i=0; $i<6; $i++)
					if ($hash == sha1($this->getUserID().$this->accessName.floor((time()-$i*600)/600).LogIn::$challenge))
						return true;
			}
		}

		// HACKERS!!! Oh, wait, maby it just timed out.
		return false;
	}

}
