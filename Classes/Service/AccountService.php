<?php
namespace SimplyAdmire\CrowdConnector\Service;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Persistence\Doctrine\PersistenceManager;
use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Security\AccountRepository;
use TYPO3\Flow\Utility\Now;

class AccountService
{

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
     * @Flow\Inject(lazy=false)
     * @var Now
     */
    protected $now;

    /**
     * @param string $username
     * @throws |Exception
     * @return Account
     */
    public function getAccountForUsername($username, $providerName)
    {
        $account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName(
            $username,
            $providerName
        );

        if (!$account instanceof Account) {
            throw new \Exception('Account not found');
        }

        return $account;
    }

    public function accountForUsernameExists($username, $providerName)
    {
        try {
            return $this->getAccountForUsername($username, $providerName) instanceof Account;
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * Create a single crowd generated account with given name and roles
     *
     * @param string $username
     * @param string $providerName
     * @param array $crowdData
     * @return string
     */
    public function createAccount($username, $providerName, array $crowdData)
    {
        $account = new Account();
        $account->setAccountIdentifier($username);
        $account->setAuthenticationProviderName($providerName);

        $this->persistenceManager->whitelistObject($account);
        $this->accountRepository->add($account);

        $this->emitAccountCreated($account, $crowdData);

        return $account;
    }

    /**
     * Update a single user (account)
     *
     * @param Account $account
     * @param array $crowdData
     * @return array
     */
    public function updateAccount(Account $account, array $crowdData)
    {
        $this->emitAccountUpdated($account, $crowdData);
    }

    public function deactivate(Account $account)
    {
        $account->setExpirationDate($this->now);
        $this->accountRepository->update($account);
    }

    public function activate(Account $account)
    {
        $account->setExpirationDate(null);
        $this->accountRepository->update($account);
    }

    /**
     * @param Account $account
     * @param array $crowdData
     * @return void
     * @Flow\Signal
     */
    public function emitAccountCreated(Account $account, array $crowdData)
    {
    }

    /**
     * @param Account $account
     * @param array $crowdData
     * @return void
     * @Flow\Signal
     */
    public function emitAccountUpdated(Account $account, array $crowdData)
    {
    }

}
