<?php
namespace SimplyAdmire\CrowdConnector\Service;

use SimplyAdmire\CrowdConnector\Crowd\Response;
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
     */
    public function getAllUsers()
    {
        $response = $this->doRequest(
            $this->providerOptions['apiUrls']['search'] . '?entity-type=user'
        );

        if ($response->isSuccess()) {
            $data = $response->getData();
            return $data['users'];
        }

        return [];
    }

    /**
     * @param string $username
     * @return array
     */
    public function getUserInformation($username)
    {
        $response = $this->doRequest(
            $this->providerOptions['apiUrls']['user'] . '?username=' . $username
        );

        if ($response->isSuccess()) {
            return $response->getData();
        }

        return [];
    }

    /**
     * @param array $credentials
     * @return array
     */
    public function getAuthenticationResponse(array $credentials)
    {
        $response = $this->doRequest(
            $this->providerOptions['apiUrls']['authenticate'] . '?username=' . $credentials['username'],
            ['value' => $credentials['password']]
        );
        return [
            'response' => $response->getData(),
            'info' => $response->getResponseInfo()
        ];
    }

    /**
     * @param string $uri
     * @param array $data
     * @return Response
     * @throws \Exception
     */
    protected function doRequest($uri, array $data = [])
    {
        try {
            $curlHandle = \curl_init();

            \curl_setopt_array(
                $curlHandle,
                [
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json',
                        'Content-Type: application/json'
                    ],
                    CURLOPT_URL => $this->providerOptions['url'] . $uri,
                    CURLOPT_USERPWD => $this->providerOptions['applicationName'] . ':' . $this->providerOptions['password'],
                    CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => 0
                ]
            );

            if ($data !== []) {
                \curl_setopt_array(
                    $curlHandle,
                    [
                        CURLOPT_POSTFIELDS => \json_encode($data),
                        CURLOPT_POST => 1
                    ]
                );
            }

            $result = \curl_exec($curlHandle);
            $response = Response::createFromResponseContent($result, \curl_getinfo($curlHandle));
            \curl_close($curlHandle);

            return $response;
        } catch (\Exception $exception) {
            $this->systemLogger->log($exception->getMessage(), LOG_WARNING);
            throw $exception;
        }
    }

}
