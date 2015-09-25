<?php
namespace SimplyAdmire\CrowdConnector\Service;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\Doctrine\PersistenceManager;
use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Security\AccountRepository;
use TYPO3\Party\Domain\Model\ElectronicAddress;
use TYPO3\Party\Domain\Model\Person;
use TYPO3\Party\Domain\Model\PersonName;
use TYPO3\Party\Domain\Repository\PartyRepository;

class AccountService {

	const RESULT_CODE_ACCOUNT_CREATED = 200;
	const RESULT_CODE_EXISTING_ACCOUNT = 300;
	const RESULT_CODE_ACCOUNT_UPDATED = 400;

	/**
	 * @Flow\Inject
	 * @var AccountRepository
	 */
	protected $accountRepository;

	/**
	 * @Flow\Inject
	 * @var PartyRepository
	 */
	protected $partyRepository;

	/**
	 * @Flow\Inject
	 * @var PersistenceManager
	 */
	protected $persistenceManager;

	/**
	 * Create a single crowd generated account with given name and roles
	 *
	 * @param string $username
	 * @param string $firstName
	 * @param string $lastName
	 * @param string $email
	 * @param array $roles
	 * @return string
	 */
	public function createCrowdAccount($username, $firstName, $lastName, $email, array $roles = []) {
		$account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($username, 'crowdProvider');
		if ($account instanceof Account) {
			return [
				'message' => sprintf('User with username: %s already exists', $username),
				'account' => $account,
				'code' => self::RESULT_CODE_EXISTING_ACCOUNT
			];
		}
		$account = new Account();
		$account->setAccountIdentifier($username);
		$account->setAuthenticationProviderName('crowdProvider');
		$account->setRoles($roles);

		$personName = new PersonName();
		$personName->setFirstName($firstName);
		$personName->setLastName($lastName);

		$electronicAddress = new ElectronicAddress();
		$electronicAddress->setType(ElectronicAddress::TYPE_EMAIL);
		$electronicAddress->setIdentifier($email);

		$person = new Person();
		$person->setName($personName);
		$person->setPrimaryElectronicAddress($electronicAddress);

		$person->addAccount($account);
		$account->setParty($person);

		$this->accountRepository->add($account);
		$this->partyRepository->add($person);

		$this->persistenceManager->persistAll();
		return [
			'message' => sprintf('User %s is created', $username),
			'account' => $account,
			'code' => self::RESULT_CODE_ACCOUNT_CREATED
		];
	}

	/**
	 * Update a single user (account)
	 *
	 * @param Account $account
	 * @param array $userDetails
	 * @return array
	 */
	public function updateAccount(Account $account, array $userDetails) {
		/** @var Person $person */
		$person = $this->partyRepository->findOneHavingAccount($account);
		$name = $person->getName();
		$name->setFirstName($userDetails['user']['first-name']);
		$name->setLastName($userDetails['user']['last-name']);
		$email = $person->getPrimaryElectronicAddress();
		$email->setIdentifier($userDetails['user']['email']);
		$person->setName($name);
		$person->setPrimaryElectronicAddress($email);
		$this->partyRepository->update($person);
		$this->persistenceManager->persistAll();

		return [
			'message' => sprintf('User: %s is updated', $account->getAccountIdentifier()),
			'account' => $account,
			'code' => self::RESULT_CODE_ACCOUNT_UPDATED
		];
	}

}