<?php
namespace SimplyAdmire\CrowdConnector\Provider;

use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Security\Authentication\Provider\AbstractProvider;
use TYPO3\Flow\Http\Client\CurlEngine as HttpClient;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Authentication\TokenInterface;

class CrowdProvider extends AbstractProvider {

	/**
	 * Name of the provider as set in Settings.yaml
	 *
	 * @var string
	 */
	protected $name = 'CrowdProvider';

	/**
	 * @Flow\InjectConfiguration(path="security.authentication.providers.CrowdProvider.providerOptions", package="TYPO3.Flow")
	 * @var array
	 */
	protected $ldapOptions;

	/**
	 * @Flow\Inject
	 * @var HttpClient
	 */
	protected $httpClient;

	/**
	 * @param TokenInterface $authenticationToken
	 * @return void
	 */
	public function authenticate(TokenInterface $authenticationToken) {
	}

	public function getTokenClassNames() {
	}

}