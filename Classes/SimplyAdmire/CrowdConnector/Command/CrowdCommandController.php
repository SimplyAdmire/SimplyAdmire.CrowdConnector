<?php
namespace SimplyAdmire\CrowdConnector\Command;

use SimplyAdmire\CrowdConnector\Command\Exception\CrowdSearchException;
use SimplyAdmire\CrowdConnector\Service\AccountService;
use SimplyAdmire\CrowdConnector\Service\CrowdApiService;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Annotations as Flow;

class CrowdCommandController extends CommandController {

	/**
	 * @Flow\Inject
	 * @var CrowdApiService
	 */
	protected $crowdApiService;

	/**
	 * @Flow\Inject
	 * @var AccountService
	 */
	protected $accountService;

	/**
	 * Import the users from Crowd
	 *
	 * @throws CrowdSearchException
	 */
	public function importUsersCommand() {
		$crowdSearch = $this->crowdApiService->getAllUsers();
		$statusCode = $crowdSearch['info']['http_code'];
		$users = $crowdSearch['users'];
		if ($statusCode === 200) {
			if (isset($users) && is_array($users)) {
				foreach($users as $user) {
					$userDetails = $this->crowdApiService->getUserInformation($user['name']);
					if ($userDetails['info']['http_code'] === 200 && $userDetails['user']['active'] === TRUE) {
						$result = $this->accountService->createCrowdAccount($user['name'], $userDetails['user']['first-name'], $userDetails['user']['last-name'], $userDetails['user']['email']);
						$this->outputLine($result['message']);
						if ($result['code'] === AccountService::RESULT_CODE_EXISTING_ACCOUNT) {
							// TODO: update most likely
						}
					}
				}
			}
		} else{
			throw new CrowdSearchException(sprintf('Search result from crowd did not return a valid response, response code was: %s', $statusCode), 12389176891);
		}
	}

}