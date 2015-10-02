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
		$curlHandle = curl_init();
		curl_setopt_array($curlHandle, [
			CURLOPT_HTTPHEADER => ['Accept: application/json'],
			CURLOPT_URL => $uri,
			CURLOPT_USERPWD => $this->providerOptions['applicationName'] . ':' . $this->providerOptions['password'],
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_POST => 0,
			CURLOPT_RETURNTRANSFER => TRUE
		]);
		try {
			$response = json_decode(curl_exec($curlHandle), TRUE);
			$info = curl_getinfo($curlHandle);
			return [
				'users' => $response['users'],
				'info' => $info
			];
		} catch (\Exception $exception) {
			$this->systemLogger->log($exception->getMessage(), LOG_WARNING);
		}
		curl_close($curlHandle);
	}

	/**
	 * @param string $username
	 * @return array
	 */
	public function getUserInformation($username) {
		$uri = $this->providerOptions['crowdServerUrl'] . $this->providerOptions['apiUrls']['user'] . '?username=' . $username;
		$curlHandle = curl_init();
		curl_setopt_array($curlHandle, [
			CURLOPT_HTTPHEADER => ['Accept: application/json'],
			CURLOPT_URL => $uri,
			CURLOPT_USERPWD => $this->providerOptions['applicationName'] . ':' . $this->providerOptions['password'],
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_POST => 0,
			CURLOPT_RETURNTRANSFER => TRUE
		]);
		try {
			$response = json_decode(curl_exec($curlHandle), TRUE);
			$info = curl_getinfo($curlHandle);
			return [
				'user' => $response,
				'info' => $info
			];
		} catch (\Exception $exception) {
			$this->systemLogger->log($exception->getMessage(), LOG_WARNING);
		}
		curl_close($curlHandle);
	}

	/**
	 * @param array $credentials
	 * @return array
	 */
	public function getAuthenticationResponse(array $credentials) {
		$uri = $this->providerOptions['crowdServerUrl'] . $this->providerOptions['apiUrls']['authenticate'] . '?username=' . $credentials['username'];
		$data = json_encode(['value' => $credentials['password']]);
		$curlHandle = curl_init();
		curl_setopt($curlHandle, CURLOPT_HTTPHEADER, [
				'Accept: application/json',
				'Content-Type: application/json'
			]
		);
		curl_setopt_array($curlHandle, [
			CURLOPT_URL => $uri,
			CURLOPT_USERPWD => $this->providerOptions['applicationName'] . ':' . $this->providerOptions['password'],
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_POSTFIELDS => $data,
			CURLOPT_POST => 1,
			CURLOPT_RETURNTRANSFER => TRUE
		]);
		try {
			$response = json_decode(curl_exec($curlHandle), TRUE);
			$info = curl_getinfo($curlHandle);
			return [
				'response' => $response,
				'info' => $info
			];
		} catch (\Exception $exception) {
			$this->systemLogger->log($exception->getMessage(), LOG_WARNING);
		}
		curl_close($curlHandle);
	}

}