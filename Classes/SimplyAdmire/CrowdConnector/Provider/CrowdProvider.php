<?php
namespace SimplyAdmire\CrowdConnector\Provider;

use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Security\AccountRepository;
use TYPO3\Flow\Http\Client\CurlEngine as HttpClient;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Authentication\Provider\PersistedUsernamePasswordProvider;
use TYPO3\Flow\Security\Authentication\TokenInterface;

class CrowdProvider extends PersistedUsernamePasswordProvider {

	/**
	 * Name of the provider as set in Settings.yaml
	 *
	 * @var string
	 */
	protected $name = 'crowdProvider';

	/**
	 * @Flow\InjectConfiguration(path="security.authentication.providers.crowdProvider.providerOptions", package="TYPO3.Flow")
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
	 * @var AccountRepository
	 */
	protected $accountRepository;

	/**
	 * @Flow\Inject
	 * @var SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @param TokenInterface $authenticationToken
	 * @return void
	 */
	public function authenticate(TokenInterface $authenticationToken) {
		$credentials = $authenticationToken->getCredentials();
		if (is_array($credentials) && isset($credentials['username']) && isset($credentials['password'])) {
			$providerName = $this->name;
			$authenticationResponse = $this->getAuthenticationResponse($credentials);

			$statusCode = $authenticationResponse['info']['http_code'];

			if ($statusCode === 200 && isset($authenticationResponse['response']['name']) && $authenticationResponse['response']['name'] === $credentials['username']) {
				$account = $this->accountRepository->findActiveByAccountIdentifierAndAuthenticationProviderName($authenticationResponse['response']['name'], $providerName);
			} else {
				$authenticationToken->setAuthenticationStatus(TokenInterface::WRONG_CREDENTIALS);
			}

			if (isset($account) && $account instanceof Account) {
				$authenticationToken->setAuthenticationStatus(TokenInterface::AUTHENTICATION_SUCCESSFUL);
				$authenticationToken->setAccount($account);
			} else {
				$authenticationToken->setAuthenticationStatus(TokenInterface::WRONG_CREDENTIALS);
			}
		} else {
			$authenticationToken->setAuthenticationStatus(TokenInterface::NO_CREDENTIALS_GIVEN);
		}
	}

	/**
	 * @param array $credentials
	 * @return Response
	 */
	protected function getAuthenticationResponse(array $credentials) {
		$uri = $this->providerOptions['crowdServerUrl'] . $this->providerOptions['apiUrls']['authenticate'] . '?username=' . $credentials['username'];
		$data = json_encode(['value' => $credentials['password']]);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Accept: application/json',
				'Content-Type: application/json'
			]
		);
		curl_setopt($ch, CURLOPT_URL, $uri);
		curl_setopt($ch, CURLOPT_USERPWD, $this->providerOptions['applicationName'] . ':' . $this->providerOptions['password']);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		try {
			$response = json_decode(curl_exec($ch), TRUE);
			$info = curl_getinfo($ch);
			return [
				'response' => $response,
				'info' => $info
			];
		} catch (\Exception $exception) {
			$this->systemLogger->log($exception->getMessage(), LOG_WARNING);
		}
		curl_close($ch);
	}

}