<?php
namespace SimplyAdmire\CrowdConnector\Provider;

use SimplyAdmire\CrowdConnector\Service\AccountServiceInterface;
use SimplyAdmire\CrowdConnector\Service\CrowdApiService;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\AccountRepository;
use Neos\Flow\Http\Client\CurlEngine as HttpClient;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Authentication\Provider\PersistedUsernamePasswordProvider;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Exception\NoSuchRoleException;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Utility\Arrays;

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
     * @var AccountServiceInterface
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
        $instanceOptions = $this->instances[$this->options['instance']];

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

            $defaultRoles = Arrays::getValueByPath($instanceOptions, 'roles.default');
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

            $groupMembership = [];
            $roleMapping = Arrays::getValueByPath($instanceOptions, 'roles.mapping');
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

            $this->accountService->updateAccount($account, $userInformation);
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
