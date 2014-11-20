<?php

App::uses('BaseAuthenticate', 'Controller/Component/Auth');
App::uses('CakeTime', 'Utility');

class RestAuthenticate extends BaseAuthenticate {

	/*
		Signed requests expect four parameters:
		1) user -- username of user to authenticate
		2) rand -- random string that was used by client to generate signature
		3) time -- timestamp of request
		4) signature -- random string ('rand') hashed via sha1 with private key (user's sha1-hashed password)
		
		Example request: 
		http://mydomain.com/mycontroller/myaction?user=myuser&rand=16967817&time=1416507394&signature=eed8669cfbc7f62d9a7751f5da9b993e47cbdeaa
	*/

	public function getUser(CakeRequest $request) {
		$userModel = $this->settings['userModel'];

		// Retrieve request data
		$username = array_key_exists('user', $request->query) ? $request->query['user'] : '';
		$rand = array_key_exists('rand', $request->query) ? $request->query['rand'] : '';
		$time = array_key_exists('time', $request->query) ? $request->query['time'] : '';
		$signature = array_key_exists('signature', $request->query) ? $request->query['signature'] : '';

		// check for allowable timestamp
		if (!CakeTime::wasWithinLast('5 minutes', $time)) {
			return false;
		}

		$stringToSign = "&user=".$username."&rand=".$rand."&time=".$time;
		// get user by Username
		$user = ClassRegistry::init($userModel)->findByUsername($username, 'User.password');
		$password = !empty($user['User']) ? $user['User']['password'] : '';
		// try to recreate signature
		$signedString = hash_hmac('sha1', $stringToSign, $password);

		//test values and return result
		if ($signedString === $signature) {
			$user['User']['username'] = $username; // need to manually add in username (for some reason)
			unset($user['User']['password']);
			return $user;
		} else {
			return false;
		}
	}

	public function authenticate(CakeRequest $request, CakeResponse $response) {
		return $this->getUser($request);
	}

	public function unauthenticated(CakeRequest $request, CakeResponse $response) {
		throw new UnauthorizedException('Access Denied.');
	}

}

