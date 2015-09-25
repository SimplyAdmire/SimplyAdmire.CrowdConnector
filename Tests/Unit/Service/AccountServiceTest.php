<?php
namespace SimplyAdmire\CrowdConnector\Tests\Unit\Service;

use SimplyAdmire\CrowdConnector\Service\AccountService;
use TYPO3\Flow\Tests\UnitTestCase;

class AccountServiceTest extends UnitTestCase
{

    /**
     * @var AccountService
     */
    protected $accountService;

    public function setUp()
    {
        $this->accountService = new AccountService();
        $mockRepository = $this->getMockBuilder('TYPO3\Flow\Security\AccountRepository')->disableOriginalConstructor()->getMock();
        $this->inject($this->accountService, 'accountRepository', $mockRepository);
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

}