<?php

  class Auth {
    private $user = null;

    public function __construct(\PDO $dbh, $config=null)
    {
      $this->dbh = $dbh;
      // $this->config = $config;
      $this->config = new stdClass();

      $this->config->table_users = 'users';
      $this->config->table_sessions = 'sessions';
      $this->config->site_key = 'alabalaportocala';
      $this->config->cookie_remember = '+1 month';
      $this->config->bcrypt_cost = '10';
    }


    /*
  	* Logs a user in
  	* @param string $password
  	* @param bool $remember
  	* @return array $return
  	*/

  	public function login($email, $password, $remember = 0)
  	{
  		$return['error'] = 1;

  		$validateEmail = $this->validateEmail($email);
  		$validatePassword = $this->validatePassword($password);

  		if ($validateEmail['error'] == 1) {

  			$return['message'] = "email_password_invalid";
  			return $return;
  		} elseif($validatePassword['error'] == 1) {

  			$return['message'] = "email_password_invalid";
  			return $return;
  		} elseif($remember != 0 && $remember != 1) {

  			$return['message'] = "remember_me_invalid";
  			return $return;
  		}

  		$uid = $this->getUID(strtolower($email));

  		if(!$uid) {

  			$return['message'] = "email_password_incorrect";
  			return $return;
  		}

  		$user = $this->getUser($uid);
      $this->user = $user;

  		if (!password_verify($password, $user['password'])) {

  			$return['message'] = "email_password_incorrect";
  			return $return;
  		}

  		$sessiondata = $this->addSession($user['uid'], $remember);

  		if($sessiondata == false) {
  			$return['message'] = "system_error";
  			return $return;
  		}

  		$return['error'] = 0;
  		$return['message'] = "logged_in";

      $return['user'] = $user;
  		$return['hash'] = $sessiondata['hash'];
  		$return['expire'] = $sessiondata['expiretime'];

      $_SESSION['user']['uid'] = $user['uid'];
      $_SESSION['user']['email'] = $user['email'];
      $_SESSION['user']['rights'] = $user['rights'];
      $_SESSION['user']['name'] = $user['name'];
      $_SESSION['user']['phone'] = $user['phone'];

      return $return;
  	}


    function logout() {
      // session_unset();
      session_destroy();
    }


  	/*
  	* Creates a new user, adds them to database
  	* @param string $email
  	* @param string $password
  	* @param string $repeatpassword
  	* @return array $return
  	*/

  	public function register($email, $password, $repeatpassword)
  	{
  		$return['error'] = 1;

  		$validateEmail = $this->validateEmail($email);
  		$validatePassword = $this->validatePassword($password);

  		if ($validateEmail['error'] == 1) {
  			$return['message'] = $validateEmail['message'];
  			return $return;
  		} elseif ($validatePassword['error'] == 1) {
  			$return['message'] = $validatePassword['message'];
  			return $return;
  		} elseif($password !== $repeatpassword) {
  			$return['message'] = "password_nomatch";
  			return $return;
  		}

  		if ($this->isEmailTaken($email)) {

  			$return['message'] = "email_taken";
  			return $return;
  		}

  		$addUser = $this->addUser($email, $password);

  		if($addUser['error'] != 0 ) {
  			$return['message'] = $addUser['message'];
  			return $return;
  		}

  		$return['error'] = 0;
  		$return['message'] = "register_success";

  		return $return;
  	}


    public function getErrorMessage($message) {
      switch ($message) {
        case 'password_nomatch':
          return 'Passwords don\'t match.';
          break;

        case 'email_taken':
          return 'Email is taken.';
          break;

        case 'email_invalid':
          return 'Email is not valid.';
          break;

        case 'register_success':
          return 'Successfully registered.';
          break;

        case 'email_password_incorrect':
          return 'Email or password incorrect.';
          break;

        case 'email_password_invalid':
          return 'Email or password incorrect.';
          break;

        case 'password_short':
          return 'Password too short.';
          break;

        case 'logged_in':
          return 'Logged in.';
          break;

        case 'system_error':
          return 'System error please try again later.';
          break;

        case 'email_long':
          return 'Email too long.';
          break;

        case 'email_short':
          return 'Email too short.';
          break;

        case 'password_long':
          return 'Password too long.';
          break;

        case 'password_short':
          return 'Password too short.';
          break;


        default:
          # code...
          break;
      }
    }

    // public function logout($hash)
    // {
    //   if (strlen($hash) != 40) {
    //     return false;
    //   }
    //
    //   return $this->deleteSession($hash);
    // }


    public function getUID($email)
  	{
  		$query = $this->dbh->prepare("SELECT id FROM {$this->config->table_users} WHERE email = ?");
  		$query->execute(array($email));

  		if($query->rowCount() == 0) {
  			return false;
  		}

  		return $query->fetch(PDO::FETCH_ASSOC)['id'];
  	}


    /*
  	* Checks if an email is already in use
  	* @param string $email
  	* @return boolean
  	*/

  	private function isEmailTaken($email)
  	{
  		$query = $this->dbh->prepare("SELECT * FROM {$this->config->table_users} WHERE email = ?");
  		$query->execute(array($email));

  		if ($query->rowCount() == 0) {
  			return false;
  		}

  		return true;
  	}


  	/*
  	* Adds a new user to database
  	* @param string $email
  	* @param string $password
  	* @return int $uid
  	*/

  	private function addUser($email, $password)
  	{
  		$return['error'] = 1;

  		$query = $this->dbh->prepare("INSERT INTO {$this->config->table_users} VALUES ()");

  		if(!$query->execute()) {
  			$return['message'] = "system_error";
  			return $return;
  		}

  		$uid = $this->dbh->lastInsertId();
  		$email = htmlentities($email);

  		$salt = substr(strtr(base64_encode(mcrypt_create_iv(22, MCRYPT_DEV_URANDOM)), '+', '.'), 0, 22);

  		$password = $this->getHash($password, $salt);

  		$query = $this->dbh->prepare("UPDATE {$this->config->table_users} SET password = ?, email = ?, salt = ? WHERE id = ?");

  		if(!$query->execute(array($password, $email, $salt, $uid))) {
  			$query = $this->dbh->prepare("DELETE FROM {$this->config->table_users} WHERE id = ?");
  			$query->execute(array($uid));

  			$return['message'] = "system_error";
  			return $return;
  		}

  		$return['error'] = 0;
  		return $return;
  	}


  	/*
  	* Gets user data for a given UID and returns an array
  	* @param int $uid
  	* @return array $data
  	*/

  	public function getUser($uid)
  	{
      if ($this->user != null) return $this->user;
  		$query = $this->dbh->prepare("SELECT password, email, salt, isactive, rights, name, phone FROM {$this->config->table_users} WHERE id = ?");
  		$query->execute(array($uid));

  		if ($query->rowCount() == 0) {
  			return false;
  		}

  		$data = $query->fetch(PDO::FETCH_ASSOC);

  		if (!$data) {
  			return false;
  		}

  		$data['uid'] = $uid;
  		return $data;
  	}


    public function getSignedInUser()
    {
      return $_SESSION['user'];
    }

  	/*
  	* Verifies that a password is valid and respects security requirements
  	* @param string $password
  	* @return array $return
  	*/

  	private function validatePassword($password)
    {
  		$return['error'] = 1;

  		if (strlen($password) < 6) {
  			$return['message'] = "password_short";
  			return $return;
  		} elseif (strlen($password) > 72) {
  			$return['message'] = "password_long";
  			return $return;
  		} elseif (!preg_match('@[A-Z]@', $password) || !preg_match('@[a-z]@', $password) || !preg_match('@[0-9]@', $password)) {
  			$return['message'] = "password_invalid";
  			return $return;
  		}

  		$return['error'] = 0;
  		return $return;
  	}


  	/*
  	* Verifies that an email is valid
  	* @param string $email
  	* @return array $return
  	*/

  	private function validateEmail($email)
    {
  		$return['error'] = 1;

  		if (strlen($email) < 5) {
  			$return['message'] = "email_short";
  			return $return;
  		} elseif (strlen($email) > 100) {
  			$return['message'] = "email_long";
  			return $return;
  		} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  			$return['message'] = "email_invalid";
  			return $return;
  		}

  		$return['error'] = 0;
  		return $return;
  	}


    /*
  	* Changes a user's password
  	* @param int $uid
  	* @param string $currpass
  	* @param string $newpass
  	* @return array $return
  	*/

  	public function changePassword($uid, $currpass, $newpass, $repeatnewpass)
  	{
  		$return['error'] = 1;

  		$validatePassword = $this->validatePassword($currpass);

  		if($validatePassword['error'] == 1) {

  			$return['message'] = $validatePassword['message'];
  			return $return;
  		}

  		$validatePassword = $this->validatePassword($newpass);

  		if($validatePassword['error'] == 1) {
  			$return['message'] = $validatePassword['message'];
  			return $return;
  		} elseif($newpass !== $repeatnewpass) {
  			$return['message'] = "newpassword_nomatch";
  			return $return;
  		}

  		$user = $this->getUser($uid);

  		if(!$user) {

  			$return['message'] = "system_error";
  			return $return;
  		}

  		$newpass = $this->getHash($newpass, $user['salt']);

  		if($currpass == $newpass) {
  			$return['message'] = "newpassword_match";
  			return $return;
  		}

  		if(!password_verify($currpass, $user['password'])) {

  			$return['message'] = "password_incorrect";
  			return $return;
  		}

  		$query = $this->dbh->prepare("UPDATE {$this->config->table_users} SET password = ? WHERE id = ?");
  		$query->execute(array($newpass, $uid));

  		$return['error'] = 0;
  		$return['message'] = "password_changed";
  		return $return;
  	}


  	/*
  	* Gets a user's email address by UID
  	* @param int $uid
  	* @return string $email
  	*/

  	public function getEmail($uid)
  	{
  		$query = $this->dbh->prepare("SELECT email FROM {$this->config->table_users} WHERE id = ?");
  		$query->execute(array($uid));
  		$row = $query->fetch(PDO::FETCH_ASSOC);

  		if (!$row) {
  			return false;
  		}

  		return $row['email'];
  	}


  	/*
  	* Changes a user's email
  	* @param int $uid
  	* @param string $currpass
  	* @param string $newpass
  	* @return array $return
  	*/

  	public function changeEmail($uid, $email, $password)
  	{
  		$return['error'] = 1;

  		$validateEmail = $this->validateEmail($email);

  		if($validateEmail['error'] == 1)
  		{
  			$return['message'] = $validateEmail['message'];
  			return $return;
  		}

  		$validatePassword = $this->validatePassword($password);

  		if ($validatePassword['error'] == 1) {
  			$return['message'] = "password_notvalid";
  			return $return;
  		}

  		$user = $this->getUser($uid);

  		if(!$user) {

  			$return['message'] = "system_error";
  			return $return;
  		}

  		if(!password_verify($password, $user['password'])) {

  			$return['message'] = "password_incorrect";
  			return $return;
  		}

  		if ($email == $user['email']) {

  			$return['message'] = "newemail_match";
  			return $return;
  		}

  		$query = $this->dbh->prepare("UPDATE {$this->config->table_users} SET email = ? WHERE id = ?");
  		$query->execute(array($email, $uid));

  		if ($query->rowCount() == 0) {
  			$return['message'] = "system_error";
  			return $return;
  		}

  		$return['error'] = 0;
  		$return['message'] = "email_changed";
  		return $return;
  	}


    /*
  	* Returns IP address
  	* @return string $ip
  	*/

  	private function getIp()
  	{
  		return $_SERVER['REMOTE_ADDR'];
  	}


    /*
    * Hashes provided string with Bcrypt
    * @param string $string
    * @param string $salt
    * @return string $hash
    */

    public function getHash($string, $salt)
    {
      return password_hash($string, PASSWORD_BCRYPT, ['salt' => $salt, 'cost' => $this->config->bcrypt_cost]);
    }


    /*
    * Creates a session for a specified user id
    * @param int $uid
    * @param boolean $remember
    * @return array $data
    */

    private function addSession($uid, $remember)
  	{
  		$ip = $this->getIp();
  		$user = $this->getUser($uid);

  		if(!$user) {
  			return false;
  		}

  		$data['hash'] = sha1($user['salt'] . microtime());
  		$agent = $_SERVER['HTTP_USER_AGENT'];

  		// $this->deleteExistingSessions($uid);

  		if($remember == true) {
  			$data['expire'] = date("Y-m-d H:i:s", strtotime($this->config->cookie_remember));
  			$data['expiretime'] = strtotime($data['expire']);
  		} else {
  			$data['expire'] = date("Y-m-d H:i:s", strtotime($this->config->cookie_remember));
  			$data['expiretime'] = 0;
  		}

  		$data['cookie_crc'] = sha1($data['hash'] . $this->config->site_key);

  		// $query = $this->dbh->prepare("INSERT INTO {$this->config->table_sessions} (uid, hash, expiredate, ip, agent, cookie_crc) VALUES (?, ?, ?, ?, ?, ?)");
      //
  		// if(!$query->execute(array($uid, $data['hash'], $data['expire'], $ip, $agent, $data['cookie_crc']))) {
  		// 	return false;
  		// }

  		$data['expire'] = strtotime($data['expire']);
  		return $data;
  	}


    /*
    * Check if current user is admin
    */
    public function isAdmin()
    {
      if (!isset($_SESSION['user'])) return false;
      if ($_SESSION['user']['rights'] == 1) return true;
      return false;
    }


    /*
  	* Function to check if a session is valid
  	* @param string $hash
  	* @return boolean
  	*/

  	public function checkSession(/*$hash*/)
  	{
      if (empty($_SESSION['user'])) return false;
      return true;

  		$ip = $this->getIp();

  		if ($this->isBlocked()) {
  			return false;
  		}

  		if (strlen($hash) != 40) {
  			return false;
  		}

  		$query = $this->dbh->prepare("SELECT id, uid, expiredate, ip, agent, cookie_crc FROM {$this->config->table_sessions} WHERE hash = ?");
  		$query->execute(array($hash));

  		if ($query->rowCount() == 0) {
  			return false;
  		}

  		$row = $query->fetch(PDO::FETCH_ASSOC);

  		$sid = $row['id'];
  		$uid = $row['uid'];
  		$expiredate = strtotime($row['expiredate']);
  		$currentdate = strtotime(date("Y-m-d H:i:s"));
  		$db_ip = $row['ip'];
  		$db_agent = $row['agent'];
  		$db_cookie = $row['cookie_crc'];

  		if ($currentdate > $expiredate) {
  			$this->deleteExistingSessions($uid);

  			return false;
  		}

  		if ($ip != $db_ip) {
  			if ($_SERVER['HTTP_USER_AGENT'] != $db_agent) {
  				$this->deleteExistingSessions($uid);

  				return false;
  			}

  			return $this->updateSessionIp($sid, $ip);
  		}

  		if ($db_cookie == sha1($hash . $this->config->site_key)) {
  			return true;
  		}

  		return false;
  	}


    /*
    * Update user account informations
    */
    function updateUser($user)
    {
      $query = $this->dbh->prepare('UPDATE users SET email = ?, name = ?, phone = ? WHERE id = ?');
      if ($query->execute(array($user['email'], $user['name'], $user['phone'], $_SESSION['user']['uid']))) {
        $_SESSION['user']['name'] = $user['name'];
        $_SESSION['user']['email'] = $user['email'];
        $_SESSION['user']['phone'] = $user['phone'];
        return true;
      }
      return false;
    }

  }
