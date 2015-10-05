<?php
namespace SimplyAdmire\CrowdConnector\Tests\Unit\Command;

use TYPO3\Flow\Tests\UnitTestCase;

class CrowdCommandControllerTest extends UnitTestCase {

    /**
     * @test
     */
    public function testIfANewlyImportedUserIsCreatedCorrectly()
    {
        $crowdCommandControllerMock = $this->getAccessibleMock('SimplyAdmire\CrowdConnector\Command\CrowdCommandController', ['dummy', 'outputLine', 'emitNewUserCreated'], [], '', false);
        $crowdApiServiceMock = $this->getMock('SimplyAdmire\CrowdConnector\Service\CrowdApiService', [], [], '', false);
        $this->inject($crowdCommandControllerMock, 'crowdApiService', $crowdApiServiceMock);
        $accountServiceMock = $this->getMock('SimplyAdmire\CrowdConnector\Service\AccountService', [], [], '', false);
        $this->inject($crowdCommandControllerMock, 'accountService', $accountServiceMock);
        $mockAccount = $this->getMock('TYPO3\Flow\Security\Account', [], [], '', false);

        $searchResult = [
            'info' => [
                'http_code' => 200
            ],
            'users' => [
                'user1' => [
                    'name' => 'john doe'
                ]
            ]
        ];

        $detailResult = [
            'info' => [
                'http_code' => 200
            ],
            'user' => [
                'active' => true,
                'first-name' => 'john',
                'last-name' => 'doe',
                'email' => 'no@email.com'
            ]
        ];

        $createdResult = [
            'message' => 'User john doe is created',
            'code' => 200,
            'account' => $mockAccount
        ];

        $crowdApiServiceMock->expects($this->once())->method('getAllUsers')->willReturn($searchResult);
        $crowdApiServiceMock->expects($this->once())->method('getUserInformation')->willReturn($detailResult);
        $accountServiceMock->expects($this->once())->method('createCrowdAccount')->willReturn($createdResult);
        $crowdCommandControllerMock->expects($this->once())->method('outputLine');
        $crowdCommandControllerMock->expects($this->once())->method('emitNewUserCreated');

        $crowdCommandControllerMock->importUsersCommand();
    }


    /**
     * @test
     */
    public function testIfAImportedUserThatAlreadyExistIsUpdated()
    {
        $crowdCommandControllerMock = $this->getAccessibleMock('SimplyAdmire\CrowdConnector\Command\CrowdCommandController', ['dummy', 'outputLine', 'emitNewUserCreated', 'emitUserExists', 'emitUserUpdated'], [], '', false);
        $crowdApiServiceMock = $this->getMock('SimplyAdmire\CrowdConnector\Service\CrowdApiService', [], [], '', false);
        $this->inject($crowdCommandControllerMock, 'crowdApiService', $crowdApiServiceMock);
        $accountServiceMock = $this->getMock('SimplyAdmire\CrowdConnector\Service\AccountService', [], [], '', false);
        $this->inject($crowdCommandControllerMock, 'accountService', $accountServiceMock);
        $mockAccount = $this->getMock('TYPO3\Flow\Security\Account', [], [], '', false);

        $searchResult = [
            'info' => [
                'http_code' => 200
            ],
            'users' => [
                'user1' => [
                    'name' => 'john doe'
                ]
            ]
        ];

        $detailResult = [
            'info' => [
                'http_code' => 200
            ],
            'user' => [
                'active' => true,
                'first-name' => 'john',
                'last-name' => 'doe',
                'email' => 'no@email.com'
            ]
        ];

        $createdResult = [
            'message' => 'User with username: john doe already exists',
            'code' => 300,
            'account' => $mockAccount
        ];

        $updateResult = [
            'code' => 400,
            'message' => 'User: john doe is updated'
        ];

        $crowdApiServiceMock->expects($this->once())->method('getAllUsers')->willReturn($searchResult);
        $crowdApiServiceMock->expects($this->once())->method('getUserInformation')->willReturn($detailResult);
        $accountServiceMock->expects($this->once())->method('createCrowdAccount')->willReturn($createdResult);
        $crowdCommandControllerMock->expects($this->exactly(2))->method('outputLine');
        $crowdCommandControllerMock->expects($this->never())->method('emitNewUserCreated');
        $crowdCommandControllerMock->expects($this->once())->method('emitUserExists');
        $accountServiceMock->expects($this->once())->method('updateAccount')->with($mockAccount, $detailResult)->willReturn($updateResult);
        $crowdCommandControllerMock->expects($this->once())->method('emitUserUpdated');

        $crowdCommandControllerMock->importUsersCommand();
    }

    /**
     * @test
     */
    public function testIfInitialStatusCodeIsNotCorrectAnExceptionIsThrown()
    {
        $crowdCommandControllerMock = $this->getAccessibleMock('SimplyAdmire\CrowdConnector\Command\CrowdCommandController', ['dummy'], [], '', false);
        $crowdApiServiceMock = $this->getMock('SimplyAdmire\CrowdConnector\Service\CrowdApiService', [], [], '', false);
        $this->inject($crowdCommandControllerMock, 'crowdApiService', $crowdApiServiceMock);


        $searchResult = [
            'users' => [

            ],
            'info' => [
                'http_code' => 0
            ]
        ];

        $crowdApiServiceMock->expects($this->once())->method('getAllUsers')->willReturn($searchResult);

        $this->setExpectedException('SimplyAdmire\CrowdConnector\Command\Exception\CrowdSearchException');
        $crowdCommandControllerMock->importUsersCommand();
    }

    /**
     * @test
     */
    public function testIfEmitNewUserCreatedReturnsNull()
    {
        $crowdCommandControllerMock = $this->getAccessibleMock('SimplyAdmire\CrowdConnector\Command\CrowdCommandController', ['dummy'], [], '', false);
        $mockAccount = $this->getMock('TYPO3\Flow\Security\Account', [], [], '', false);
        $this->assertNull($crowdCommandControllerMock->emitNewUserCreated($mockAccount));
    }

    /**
     * @test
     */
    public function testIfEmitUserExistsReturnsNull()
    {
        $crowdCommandControllerMock = $this->getAccessibleMock('SimplyAdmire\CrowdConnector\Command\CrowdCommandController', ['dummy'], [], '', false);
        $mockAccount = $this->getMock('TYPO3\Flow\Security\Account', [], [], '', false);
        $this->assertNull($crowdCommandControllerMock->emitUserExists($mockAccount));
    }

    /**
     * @test
     */
    public function testIfEmitUserUpdatedReturnsNull()
    {
        $crowdCommandControllerMock = $this->getAccessibleMock('SimplyAdmire\CrowdConnector\Command\CrowdCommandController', ['dummy'], [], '', false);
        $mockAccount = $this->getMock('TYPO3\Flow\Security\Account', [], [], '', false);
        $this->assertNull($crowdCommandControllerMock->emitUserUpdated($mockAccount));
    }


}