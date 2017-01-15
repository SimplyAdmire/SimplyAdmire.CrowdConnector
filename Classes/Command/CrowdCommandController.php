<?php
namespace SimplyAdmire\CrowdConnector\Command;

use SimplyAdmire\CrowdConnector\Service\AccountService;
use SimplyAdmire\CrowdConnector\Service\CrowdApiService;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\Doctrine\Query;
use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Security\AccountRepository;
use TYPO3\Flow\Utility\Arrays;

class CrowdCommandController extends CommandController
{

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
     * @var AccountRepository
     */
    protected $accountRepository;

    /**
     * Import the users from Crowd
     */
    public function importUsersCommand()
    {
        foreach ($this->instances as $instanceIdentifier => $instanceConfiguration) {
            $importEnabled = Arrays::getValueByPath($instanceConfiguration, 'import.enabled');
            $providerName = Arrays::getValueByPath($instanceConfiguration, 'import.providerName');

            if ($importEnabled !== true || $providerName === null) {
                continue;
            }

            $this->importInstance(
                $instanceIdentifier,
                $providerName,
                [
                    'createAccounts' => Arrays::getValueByPath($instanceConfiguration, 'import.createAccounts')
                ]
            );
        }
    }

    protected function importInstance($instanceIdentifier, $providerName, array $options)
    {
        $this->outputLine('Import %s for provider %s', [$instanceIdentifier, $providerName]);

        $crowdApiService = new CrowdApiService($instanceIdentifier);
        $activeAccountIdentifiers = [];

        foreach ($crowdApiService->getAllUsers() as $user) {
            $username = $user['name'];
            $userDetails = $crowdApiService->getUserInformation($user['name']);

            if ($userDetails === []) {
                continue;
            }

            if ($userDetails['active'] === true) {
                $activeAccountIdentifiers[] = $username;

                if ($this->accountService->accountForUsernameExists($username, $providerName)) {
                    $account = $this->accountService->getAccountForUsername($username, $providerName);
                    $this->accountService->updateAccount($account, $userDetails);

                    if ($account->isActive() === false) {
                        $this->accountService->activate($account);
                    }

                    $this->outputLine('Updated user %s', [$username]);
                } elseif ($options['createAccounts'] === true) {
                    $this->accountService->createAccount($username, $providerName, $userDetails);

                    $this->outputLine('Created user %s', [$username]);
                } else {
                    $this->outputLine('Skipped user %s because account creation is disabled', [$username]);
                }
            }
        }

        /** @var Query $query */
        $query = $this->accountRepository->findByAuthenticationProviderName($providerName)->getQuery();
        $constraint = $query->getConstraint();

        $accountsToDeactivate = $query->matching(
            $query->logicalAnd(
                $constraint,
                $query->equals('expirationDate', null),
                $query->logicalNot(
                    $query->in('accountIdentifier', $activeAccountIdentifiers)
                )
            )
        )->execute();

        /** @var Account $account */
        foreach ($accountsToDeactivate as $account) {
            $this->outputLine('Deactivate %s', [$account->getAccountIdentifier()]);
            $this->accountService->deactivate($account);
        }

        $this->outputLine('Done');
    }

}
