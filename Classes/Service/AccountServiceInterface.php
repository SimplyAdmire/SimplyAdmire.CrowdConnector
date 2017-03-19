<?php
declare(strict_types = 1);

namespace SimplyAdmire\CrowdConnector\Service;

use Neos\Flow\Security\Account;

interface AccountServiceInterface
{

    public function getAccountForUsername(string $username, string $providerName): Account;

    public function accountForUsernameExists(string $username, string $providerName): bool;

    public function createAccount(string $username, string $providerName, array $crowdData): Account;

    public function updateAccount(Account $account, array $crowdData);

    public function deactivate(Account $account);

    public function activate(Account $account);

}
