<?php
namespace SimplyAdmire\CrowdConnector\Service;

use SimplyAdmire\CrowdConnector\Command\Exception\CrowdSearchException;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;

class CrowdApiService {

	/**
	 * @Flow\InjectConfiguration(path="security.authentication.providers.crowdProvider.providerOptions", package="TYPO3.Flow")
	 * @var array
	 */
	protected $providerOptions;

	/**
	 * @Flow\Inject
	 * @var SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * Retrieves all users from crowd
	 *
	 * @return array|void
	 * @throws CrowdSearchException
	 */
	public function getAllUsers() {
		$uri = $this->providerOptions['crowdServerUrl'] . $this->providerOptions['apiUrls']['search'] . '?entity-type=user';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
		curl_setopt($ch, CURLOPT_URL, $uri);
		curl_setopt($ch, CURLOPT_USERPWD, $this->providerOptions['applicationName'] . ':' . $this->providerOptions['password']);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_POST, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		try {
			$response = json_decode(curl_exec($ch), TRUE);
			$info = curl_getinfo($ch);
			return [
				'users' => $response['users'],
				'info' => $info
			];
		} catch (\Exception $exception) {
			$this->systemLogger->log($exception->getMessage(), LOG_WARNING);
		}
		curl_close($ch);
	}

	/**
	 * @param string $username
	 * @return array
	 */
	public function getUserInformation($username) {
		$uri = $this->providerOptions['crowdServerUrl'] . $this->providerOptions['apiUrls']['user'] . '?username=' . $username;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
		curl_setopt($ch, CURLOPT_URL, $uri);
		curl_setopt($ch, CURLOPT_USERPWD, $this->providerOptions['applicationName'] . ':' . $this->providerOptions['password']);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_POST, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		try {
			$response = json_decode(curl_exec($ch), TRUE);
			$info = curl_getinfo($ch);
			return [
				'user' => $response,
				'info' => $info
			];
		} catch (\Exception $exception) {
			$this->systemLogger->log($exception->getMessage(), LOG_WARNING);
		}
		curl_close($ch);
	}

}