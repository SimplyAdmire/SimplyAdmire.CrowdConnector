<?php
namespace SimplyAdmire\CrowdConnector\Service;

use SimplyAdmire\CrowdConnector\Command\Exception\CrowdSearchException;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;

class CrowdApiService
{

    /**
     * @Flow\InjectConfiguration(path="instances")
     * @var array
     */
    protected $instances;

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @var string
     */
    protected $instanceIdentifier;

    /**
     * @var array
     */
    protected $providerOptions;

    public function __construct($instanceIdentifier)
    {
        $this->instanceIdentifier = $instanceIdentifier;
    }

    protected function initializeObject()
    {
        if (\array_key_exists($this->instanceIdentifier, $this->instances)) {
            $this->providerOptions = $this->instances[$this->instanceIdentifier];
        }
    }

    /**
     * Retrieves all users from crowd
     *
     * @return array
     * @throws CrowdSearchException
     */
    public function getAllUsers()
    {
        $users = [];
        $url = $this->providerOptions['url'] . $this->providerOptions['apiUrls']['search'] . '?entity-type=user';
        $curlHandle = \curl_init();
        curl_setopt_array($curlHandle, [
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_URL => $url,
            CURLOPT_USERPWD => $this->providerOptions['applicationName'] . ':' . $this->providerOptions['password'],
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_POST => 0,
            CURLOPT_RETURNTRANSFER => true
        ]);
        try {
            $response = \json_decode(\curl_exec($curlHandle), true);
            $info = \curl_getinfo($curlHandle);
            $users = [
                'users' => $response['users'],
                'info' => $info
            ];
        } catch (\Exception $exception) {
            $this->systemLogger->log($exception->getMessage(), LOG_WARNING);
        }
        \curl_close($curlHandle);
        return $users;
    }

    /**
     * @param string $username
     * @return array
     */
    public function getUserInformation($username)
    {
        $uri = $this->providerOptions['url'] . $this->providerOptions['apiUrls']['user'] . '?username=' . $username;
        $curlHandle = \curl_init();
        \curl_setopt_array($curlHandle, [
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_URL => $uri,
            CURLOPT_USERPWD => $this->providerOptions['applicationName'] . ':' . $this->providerOptions['password'],
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_POST => 0,
            CURLOPT_RETURNTRANSFER => true
        ]);
        try {
            $response = \json_decode(\curl_exec($curlHandle), true);
            $info = \curl_getinfo($curlHandle);
            return [
                'user' => $response,
                'info' => $info
            ];
        } catch (\Exception $exception) {
            $this->systemLogger->log($exception->getMessage(), LOG_WARNING);
        }
        \curl_close($curlHandle);
    }

    /**
     * @param array $credentials
     * @return array
     */
    public function getAuthenticationResponse(array $credentials)
    {
        $uri = $this->providerOptions['url'] . $this->providerOptions['apiUrls']['authenticate'] . '?username=' . $credentials['username'];
        $data = \json_encode(['value' => $credentials['password']]);
        $curlHandle = \curl_init();
        \curl_setopt($curlHandle, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Content-Type: application/json'
            ]
        );
        \curl_setopt_array($curlHandle, [
            CURLOPT_URL => $uri,
            CURLOPT_USERPWD => $this->providerOptions['applicationName'] . ':' . $this->providerOptions['password'],
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_POST => 1,
            CURLOPT_RETURNTRANSFER => true
        ]);
        try {
            $response = \json_decode(\curl_exec($curlHandle), true);
            $info = \curl_getinfo($curlHandle);
            return [
                'response' => $response,
                'info' => $info
            ];
        } catch (\Exception $exception) {
            $this->systemLogger->log($exception->getMessage(), LOG_WARNING);
        }
        \curl_close($curlHandle);
    }

}
