<?php
namespace SimplyAdmire\CrowdConnector\Provider;

use TYPO3\LDAP\Security\Authentication\Provider\LDAPProvider;

class CrowdProvider extends LDAPProvider {

	/**
	 * @param string $username
	 */
	public function authenticateUser($username) {
	}

}