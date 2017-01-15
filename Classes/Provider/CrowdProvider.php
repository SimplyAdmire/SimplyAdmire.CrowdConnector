<?php
namespace SimplyAdmire\CrowdConnector\Provider;

use SimplyAdmire\CrowdConnector\Service\AccountService;
use SimplyAdmire\CrowdConnector\Service\CrowdApiService;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Security\AccountRepository;
use TYPO3\Flow\Http\Client\CurlEngine as HttpClient;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Authentication\Provider\PersistedUsernamePasswordProvider;
use TYPO3\Flow\Security\Authentication\TokenInterface;
use TYPO3\Flow\Security\Exception\NoSuchRoleException;
use TYPO3\Flow\Security\Policy\PolicyService;
use TYPO3\Flow\Utility\Arrays;

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

    /**
     * @Flow\InjectConfiguration(path="instances")
     * @var array
     */
    protected $instances;

    /**
     * @Flow\Inject
     * @var PolicyService
     */
    protected $policyService;

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
            $userInformation = $this->crowdApiService->getUserInformation($username);

            if ($this->accountService->accountForUsernameExists($username, $this->name)) {
                $account = $this->accountService->getAccountForUsername($username, $this->name);
            } else {
                $account = $this->accountService->createAccount(
                    $username,
                    $this->name,
                    $userInformation
                );
            }

            $authenticationToken->setAuthenticationStatus(TokenInterface::AUTHENTICATION_SUCCESSFUL);
            $authenticationToken->setAccount($account);

            $defaultRoles = Arrays::getValueByPath($this->instances[$this->options['instance']], 'roles.default');
            if (\is_array($defaultRoles)) {
                foreach ($defaultRoles as $roleIdentifier)
                {
                    try {
                        $role = $this->policyService->getRole($roleIdentifier);
                        $account->addRole($role);
                    } catch (NoSuchRoleException $exception) {
                        $this->systemLogger->log('Role %s not found', [$roleIdentifier]);
                    }
                }
            }

            $roleMapping = Arrays::getValueByPath($this->instances[$this->options['instance']], 'roles.mapping');
            if (\is_array($roleMapping)) {
                $groupMembership = $this->crowdApiService->getUserGroupMembership($username);
                $groupIdentifiers = \array_map(
                    function($group) {
                        return $group['name'];
                    },
                    $groupMembership['groups']
                );

                foreach ($roleMapping as $groupIdentifier => $roleIdentifiers) {
                    if (!\in_array($groupIdentifier, $groupIdentifiers)) {
                        continue;
                    }

                    foreach ($roleIdentifiers as $roleIdentifier) {
                        try {
                            $role = $this->policyService->getRole($roleIdentifier);
                            $account->addRole($role);
                        } catch (NoSuchRoleException $exception) {
                            $this->systemLogger->log('Role %s not found', [$roleIdentifier]);
                        }
                    }
                }
            }

            $this->emitAccountAuthenticated($account, $userInformation, $groupMembership);

            $this->accountRepository->update($account);
            $this->persistenceManager->whitelistObject($account);
        } catch (\Exception $exception) {
            $authenticationToken->setAuthenticationStatus(TokenInterface::WRONG_CREDENTIALS);
        }
    }

    /**
     * @param Account $account
     * @param array $userInformation
     * @param array $groupMembership
     * @return void
     * @Flow\Signal
     */
    public function emitAccountAuthenticated(Account $account, array $userInformation, array $groupMembership)
    {
    }

}
