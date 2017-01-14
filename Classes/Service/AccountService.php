<?php
namespace SimplyAdmire\CrowdConnector\Service;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Persistence\Doctrine\PersistenceManager;
use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Security\AccountRepository;

class AccountService
{

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
     * @var PersistenceManager
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

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
    public function createCrowdAccount($username, $firstName, $lastName, $email, array $roles = [])
    {
        $account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($username,
            'crowdProvider');
        if ($account instanceof Account) {
            return [
                'message' => \sprintf('User with username: %s already exists', $username),
                'account' => $account,
                'code' => self::RESULT_CODE_EXISTING_ACCOUNT
            ];
        }
        $account = new Account();
        $account->setAccountIdentifier($username);
        $account->setAuthenticationProviderName('crowdProvider');
        $account->setRoles($roles);
        $this->persistenceManager->whitelistObject($account);
        $this->accountRepository->add($account);

        if (\class_exists('TYPO3\Party\Domain\Repository\PartyRepository')) {
            $partyRepository = $this->objectManager->get('TYPO3\Party\Domain\Repository\PartyRepository');
            $partyService = $this->objectManager->get('TYPO3\Party\Domain\Service\PartyService');

            $personName = $this->objectManager->get('TYPO3\Party\Domain\Model\PersonName');
            $personName->setFirstName($firstName);
            $personName->setLastName($lastName);
            $this->persistenceManager->whitelistObject($personName);

            $electronicAddress = $this->objectManager->get('TYPO3\Party\Domain\Model\ElectronicAddress');
            $electronicAddress->setType(\TYPO3\Party\Domain\Model\ElectronicAddress::TYPE_EMAIL);
            $electronicAddress->setIdentifier($email);
            $this->persistenceManager->whitelistObject($electronicAddress);

            $person = $this->objectManager->get('TYPO3\Party\Domain\Model\Person');
            $person->setName($personName);
            $person->setPrimaryElectronicAddress($electronicAddress);
            $this->persistenceManager->whitelistObject($person);

            $partyRepository->add($person);
            $partyService->assignAccountToParty($account, $person);
        }

        return [
            'message' => \sprintf('User %s is created', $username),
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
    public function updateAccount(Account $account, array $userDetails)
    {
        if (\class_exists('TYPO3\Party\Domain\Repository\PartyRepository')) {
            $partyRepository = $this->objectManager->get('TYPO3\Party\Domain\Repository\PartyRepository');
            $partyService = $this->objectManager->get('TYPO3\Party\Domain\Service\PartyService');

            $person = $partyService->getAssignedPartyOfAccount($account);
            $person->getName()->setFirstName($userDetails['user']['first-name']);
            $person->getName()->setLastName($userDetails['user']['last-name']);
            $person->getPrimaryElectronicAddress()->setIdentifier($userDetails['user']['email']);
            $partyRepository->update($person);
            $this->persistenceManager->whitelistObject($person);
        }

        return [
            'message' => \sprintf('User: %s is updated', $account->getAccountIdentifier()),
            'account' => $account,
            'code' => self::RESULT_CODE_ACCOUNT_UPDATED
        ];
    }

}
