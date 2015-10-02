<?php
namespace SimplyAdmire\CrowdConnector\Tests\Unit\Service;

use SimplyAdmire\CrowdConnector\Service\AccountService;
use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Tests\UnitTestCase;

class AccountServiceTest extends UnitTestCase
{

    /**
     * @return void
     */
    protected $accountService;

    public function setUp()
    {
        $this->accountService = $this->getAccessibleMock('SimplyAdmire\CrowdConnector\Service\AccountService', ['dummy'], [], '', false);
    }

    /**
     * return void
     */
    public function tearDown()
    {
        unset($this->accountService);
    }

    /**
     * @test
     */
    public function testIfUserIsCreatedCorrectly()
    {
        $this->accountService = $this->getAccessibleMock('SimplyAdmire\CrowdConnector\Service\AccountService', ['getNewAccount', 'getNewPersonName', 'getNewElectronicAddress', 'getNewPerson'], [], '', false);
        $mockAccountRepository = $this->getMockBuilder('TYPO3\Flow\Security\AccountRepository')->disableOriginalConstructor()->getMock();
        $mockAccount = $this->getMock('TYPO3\Flow\Security\Account', ['setParty', 'setAccountIdentifier', 'setAuthenticationProviderName', 'setRoles', 'isRegistered'], [], '', false);
        $mockPersonName = $this->getMock('TYPO3\Party\Domain\Model\PersonName', ['setFirstName', 'setLastName'], [], '', false);
        $mockElectronicAddress = $this->getMock('TYPO3\Party\Domain\Model\ElectronicAddress', ['setIdentifier', 'setType'], [], '', false);
        $mockPerson = $this->getMockBuilder('TYPO3\Party\Domain\Model\Person')->disableOriginalConstructor()->getMock();
        $mockPartyRepository = $this->getMockBuilder('TYPO3\Party\Domain\Repository\PartyRepository')->disableOriginalConstructor()->getMock();
        $mockPersistenceManager = $this->getMockBuilder('TYPO3\Flow\Persistence\Doctrine\PersistenceManager')->disableOriginalConstructor()->getMock();

        $this->inject($this->accountService, 'accountRepository', $mockAccountRepository);
        $this->inject($this->accountService, 'partyRepository', $mockPartyRepository);
        $this->inject($this->accountService, 'persistenceManager', $mockPersistenceManager);

        $mockAccountRepository->expects($this->once())->method('findByAccountIdentifierAndAuthenticationProviderName');

        $this->accountService->expects($this->once())->method('getNewAccount')->will($this->returnValue($mockAccount));
        $mockAccount->expects($this->once())->method('setAccountIdentifier');
        $mockAccount->expects($this->once())->method('setAuthenticationProviderName');
        $mockAccount->expects($this->once())->method('setRoles');

        $this->accountService->expects($this->once())->method('getNewPersonName')->will($this->returnValue($mockPersonName));
        $mockPersonName->expects($this->once())->method('setFirstName');
        $mockPersonName->expects($this->once())->method('setLastName');

        $this->accountService->expects($this->once())->method('getNewElectronicAddress')->will($this->returnValue($mockElectronicAddress));
        $mockElectronicAddress->expects($this->once())->method('setType');
        $mockElectronicAddress->expects($this->once())->method('setIdentifier');

        $this->accountService->expects($this->once())->method('getNewPerson')->will($this->returnValue($mockPerson));
        $mockPerson->expects($this->once())->method('addAccount');

        $mockAccount->expects($this->once())->method('setParty');

        $mockAccountRepository->expects($this->once())->method('add');
        $mockPartyRepository->expects($this->once())->method('add');

        $mockPersistenceManager->expects($this->once())->method('persistAll');

        $result = $this->accountService->createCrowdAccount('foo', 'bar', 'baz', 'foo@bar.baz');

        $this->assertEquals(
            'User foo is created',
            $result['message']
        );
    }

    /**
     * @test
     */
    public function testIfErrorMessageAndCodeAreGivenWhenTryingToCreateUserWithExistingUsername()
    {
        $mockAccountRepository = $this->getMockBuilder('TYPO3\Flow\Security\AccountRepository')->disableOriginalConstructor()->getMock();
        $this->inject($this->accountService, 'accountRepository', $mockAccountRepository);
        $account = new Account();
        $mockAccountRepository->expects($this->once())->method('findByAccountIdentifierAndAuthenticationProviderName')->willReturn($account);

        $result = [
            'message' => 'User with username: foo already exists',
            'account' => $account,
            'code' => AccountService::RESULT_CODE_EXISTING_ACCOUNT
        ];
        $this->assertSame(
            $result,
            $this->accountService->createCrowdAccount('foo', 'bar', 'baz', 'foo@bar.baz')
        );
    }

    /**
     * @test
     */
    public function updatingAnAccountWithCorrectDataReturnResultSuccessCode()
    {
        $userData = [
            'user' => [
                'first-name' => 'John',
                'last-name' => 'Doe',
                'email' => 'john@doe.com'
            ]
        ];

        $mockPersistenceManager = $this->getMockBuilder('TYPO3\Flow\Persistence\Doctrine\PersistenceManager')->disableOriginalConstructor()->getMock();
        $this->inject($this->accountService, 'persistenceManager', $mockPersistenceManager);

        $mockPartyRepository = $this->getMockBuilder('TYPO3\Party\Domain\Repository\PartyRepository')->disableOriginalConstructor()->getMock();
        $this->inject($this->accountService, 'partyRepository', $mockPartyRepository);

        $mockAccount = $this->getMockBuilder('TYPO3\Flow\Security\Account')->disableOriginalConstructor()->getMock();
        $mockPerson = $this->getMock('TYPO3\Party\Domain\Model\Person');
        $mockPersonName = $this->getMock('TYPO3\Party\Domain\Model\PersonName');
        $mockEmail = $this->getMock('TYPO3\Party\Domain\Model\ElectronicAddress');

        $mockPartyRepository->expects($this->once())->method('findOneHavingAccount')->with($mockAccount)->will($this->returnValue($mockPerson));
        $mockPerson->expects($this->once())->method('getName')->will($this->returnValue($mockPersonName));
        $mockPersonName->expects($this->once())->method('setFirstName')->with($userData['user']['first-name']);
        $mockPersonName->expects($this->once())->method('setLastName')->with($userData['user']['last-name']);
        $mockPerson->expects($this->once())->method('getPrimaryElectronicAddress')->will($this->returnValue($mockEmail));
        $mockAccount->expects($this->once())->method('getAccountIdentifier')->will($this->returnValue('john@doe.com'));


        $expectedMessage = 'User: john@doe.com is updated';
        $expectedCode = AccountService::RESULT_CODE_ACCOUNT_UPDATED;
        $result = $this->accountService->updateAccount($mockAccount, $userData);
        $this->assertSame($expectedMessage, $result['message']);
        $this->assertEquals($expectedCode, $result['code']);
    }

    /**
     * @test
     */
    public function testIfGetNewAccountReturnsAnAccountInstance()
    {
        $this->assertInstanceOf(
            'TYPO3\Flow\Security\Account',
            $this->accountService->getNewAccount()
        );
    }

    /**
     * @test
     */
    public function testIfGetNewPersonReturnsAnPersonInstance()
    {
        $this->assertInstanceOf(
            'TYPO3\Party\Domain\Model\Person',
            $this->accountService->getNewPerson()
        );
    }

    /**
     * @test
     */
    public function testIfGetNewPersonNameReturnsAnPersonNameInstance()
    {
        $this->assertInstanceOf(
            'TYPO3\Party\Domain\Model\PersonName',
            $this->accountService->getNewPersonName()
        );
    }

    /**
     * @test
     */
    public function testIfGetNewElectronicAddressNameReturnsAnElectronicAddressInstance()
    {
        $this->assertInstanceOf(
            'TYPO3\Party\Domain\Model\ElectronicAddress',
            $this->accountService->getNewElectronicAddress()
        );
    }

}