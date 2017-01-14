<?php
namespace SimplyAdmire\CrowdConnector\Provider;

use SimplyAdmire\CrowdConnector\Service\AccountService;
use SimplyAdmire\CrowdConnector\Service\CrowdApiService;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Security\AccountRepository;
use TYPO3\Flow\Http\Client\CurlEngine as HttpClient;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Authentication\Provider\PersistedUsernamePasswordProvider;
use TYPO3\Flow\Security\Authentication\TokenInterface;

class CrowdProvider extends PersistedUsernamePasswordProvider
{

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
     * @var CrowdApiService
     */
    protected $crowdApiService;

    /**
     * @Flow\Inject
     * @var AccountService
     */
    protected $accountService;

    protected function initializeObject()
    {
        $this->crowdApiService = new CrowdApiService($this->options['instance']);
    }

    /**
     * @param TokenInterface $authenticationToken
     * @return void
     */
    public function authenticate(TokenInterface $authenticationToken)
    {
        $credentials = $authenticationToken->getCredentials();

        if (!is_array($credentials) || !isset($credentials['username']) || !isset($credentials['password'])) {
            $authenticationToken->setAuthenticationStatus(TokenInterface::NO_CREDENTIALS_GIVEN);
            return;
        }

        $username = $credentials['username'];

        try {
            $this->crowdApiService->authenticate($credentials);

            if ($this->accountService->accountForUsernameExists($username, $this->name)) {
                $account = $this->accountService->getAccountForUsername($username, $this->name);
            } else {
                $account = $this->accountService->createAccount(
                    $username,
                    $this->name,
                    $this->crowdApiService->getUserInformation($username)
                );
            }

            $authenticationToken->setAuthenticationStatus(TokenInterface::AUTHENTICATION_SUCCESSFUL);
            $authenticationToken->setAccount($account);
        } catch (\Exception $exception) {
            $authenticationToken->setAuthenticationStatus(TokenInterface::WRONG_CREDENTIALS);
        }
    }


    /**
     * @param Account $account
     * @param array $crowdData
     * @return void
     * @Flow\Signal
     */
    public function emitAccountAuthenticated(Account $account, array $crowdData)
    {
    }

    /**
     * @param Account $account
     * @param array $crowdData
     * @return void
     * @Flow\Signal
     */
    public function emitRolesSet(Account $account, array $crowdData)
    {
    }

}
