<?php
namespace SimplyAdmire\CrowdConnector\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\AccountRepository;
use Neos\Flow\Utility\Now;

/**
 * @Flow\Scope("singleton")
 */
class AccountService implements AccountServiceInterface
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
    public function getAccountForUsername(string $username, string $providerName): Account
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

    public function accountForUsernameExists(string $username, string $providerName): bool
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
    public function createAccount(string $username, string $providerName, array $crowdData): Account
    {
        $account = new Account();
        $account->setAccountIdentifier($username);
        $account->setAuthenticationProviderName($providerName);

        $this->persistenceManager->whitelistObject($account);
        $this->accountRepository->add($account);
        $this->persistenceManager->persistAll();

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
        $this->accountRepository->update($account);
        $this->persistenceManager->whitelistObject($account);
        $this->persistenceManager->persistAll();

        $this->emitAccountUpdated($account, $crowdData);
    }

    public function deactivate(Account $account)
    {
        $account->setExpirationDate($this->now);
        $this->emitAccountDeactivated($account);
        $this->accountRepository->update($account);
    }

    public function activate(Account $account)
    {
        $account->setExpirationDate(null);
        $this->emitAccountActivated($account);
        $this->accountRepository->update($account);
    }

    /**
     * @param Account $account
     * @param array $userInformation
     * @return void
     * @Flow\Signal
     */
    public function emitAccountCreated(Account $account, array $userInformation)
    {
    }

    /**
     * @param Account $account
     * @param array $userInformation
     * @return void
     * @Flow\Signal
     */
    public function emitAccountUpdated(Account $account, array $userInformation)
    {
    }

    /**
     * @param Account $account
     * @return void
     * @Flow\Signal
     */
    public function emitAccountActivated(Account $account)
    {
    }

    /**
     * @param Account $account
     * @return void
     * @Flow\Signal
     */
    public function emitAccountDeactivated(Account $account)
    {
    }

}
