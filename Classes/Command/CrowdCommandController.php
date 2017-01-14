<?php
namespace SimplyAdmire\CrowdConnector\Command;

use SimplyAdmire\CrowdConnector\Service\AccountService;
use SimplyAdmire\CrowdConnector\Service\CrowdApiService;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Account;
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
     * Import the users from Crowd
     */
    public function importUsersCommand()
    {
        foreach ($this->instances as $instanceIdentifier => $instanceConfiguration) {
            $this->importInstance($instanceIdentifier);
        }
    }

    protected function importInstance($instanceIdentifier)
    {
        $crowdApiService = new CrowdApiService($instanceIdentifier);

        foreach ($crowdApiService->getAllUsers() as $user) {
            $userDetails = $crowdApiService->getUserInformation($user['name']);
            if ($userDetails === []) {
                continue;
            }

            if ($userDetails['active'] === true) {
                $result = $this->accountService->createCrowdAccount(
                    $user['name'],
                    $userDetails['first-name'],
                    $userDetails['last-name'],
                    $userDetails['email']
                );

                $this->outputLine($result['message']);

                /** @var Account $account */
                $account = $result['account'];

                if ($result['code'] === AccountService::RESULT_CODE_EXISTING_ACCOUNT) {
                    $updateResult = $this->accountService->updateAccount($account, $userDetails);

                    if ($updateResult['code'] === AccountService::RESULT_CODE_ACCOUNT_UPDATED) {
                        $this->outputLine($updateResult['message']);
                    }
                } elseif ($result['code'] === AccountService::RESULT_CODE_ACCOUNT_CREATED) {
                }
            }
        }
    }

}
