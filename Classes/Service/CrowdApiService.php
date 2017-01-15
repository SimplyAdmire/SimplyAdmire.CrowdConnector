<?php
namespace SimplyAdmire\CrowdConnector\Service;

use SimplyAdmire\CrowdConnector\Crowd\Response;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Utility\Arrays;

/**
 * https://developer.atlassian.com/display/CROWDDEV/Crowd+REST+Resources#CrowdRESTResources-UserResource
 */
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
        $response = $this->doRequest('search?entity-type=user');

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
        $response = $this->doRequest('user?username=' . $username);

        if ($response->isSuccess()) {
            return $response->getData();
        }

        return [];
    }

    public function getUserGroupMembership($username)
    {
        $response = $this->doRequest('user/group/direct?username=' . $username);

        if ($response->isSuccess()) {
            return $response->getData();
        }

        return [];
    }

    /**
     * @param array $credentials
     * @throws \Exception
     * @return void
     */
    public function authenticate(array $credentials)
    {
        $response = $this->doRequest(
            'authentication?username=' . $credentials['username'],
            ['value' => $credentials['password']]
        );

        if (!$response->isSuccess()) {
            throw new \Exception('Authentication failed');
        }

        $data = $response->getData();
        $usernameInResponse = Arrays::getValueByPath($data, 'name');
        if ($usernameInResponse !== $credentials['username']) {
            throw new \Exception('Authentication failed');
        }
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
            $url = \sprintf(
                '%s/rest/usermanagement/%s/%s',
                \rtrim($this->providerOptions['url'], '/'),
                $this->providerOptions['version'],
                \ltrim($uri, '/')
            );
            $auth = $this->providerOptions['applicationName'] . ':' . $this->providerOptions['password'];

            $curlHandle = \curl_init();

            \curl_setopt_array(
                $curlHandle,
                [
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json',
                        'Content-Type: application/json'
                    ],
                    CURLOPT_URL => $url,
                    CURLOPT_USERPWD => $auth,
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
