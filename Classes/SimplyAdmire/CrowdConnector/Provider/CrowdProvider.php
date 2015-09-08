<?php
namespace SimplyAdmire\CrowdConnector\Provider;

use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Uri;
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
	protected $providerOptions;

	/**
	 * @Flow\Inject
	 * @var HttpClient
	 */
	protected $httpClient;

	/**
	 * @Flow\Inject
	 * @var Request
	 */
	protected $request;

	/**
	 * @param TokenInterface $authenticationToken
	 * @return void
	 */
	public function authenticate(TokenInterface $authenticationToken) {
		$credentials = $authenticationToken->getCredentials();
		if (is_array($credentials) && isset($credentials['username']) && isset($credentials['password'])) {
			$providerName = $this->name;
			$uri = new Uri($this->providerOptions['crowdServerUrl'] . $this->providerOptions['apiUrls']['authenticate']); // combined api url

			/** @todo create a correct http request */
			$request = $this->request->create($uri, 'GET', []); // building the request
			$response = $this->httpClient->sendRequest($request); // sending the request
			$result = json_decode($response->getContent()); // json result decoded

			if (isset($result['name']) && $result['name'] === $credentials['username']) {
				// go see if account exists :)
			}
		}
	}

	public function getTokenClassNames() {
	}

}