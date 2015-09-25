<?php
namespace SimplyAdmire\CrowdConnector\Tests\Unit\Controller;

use SimplyAdmire\CrowdConnector\Controller\AuthenticationController;
use TYPO3\Flow\Tests\UnitTestCase;

class AuthenticationControllerTest extends UnitTestCase
{

    /**
     * @var AuthenticationController
     */
    protected $authenticationController;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->authenticationController = new AuthenticationController();
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        unset($this->authenticationController);
    }

    /**
     * @test
     */
    public function testIfAuthenticationSuccessMethodWillBeCalledWhenAuthenticationSucceeds()
    {
        $authenticationControllerMock = $this->getAccessibleMock('SimplyAdmire\CrowdConnector\Controller\AuthenticationController', ['onAuthenticationSuccess', 'emitAccountAuthenticationSuccess', 'forward'], [], '', false);
        $authenticationManagerMock = $this->getMockBuilder('TYPO3\Flow\Security\Authentication\AuthenticationProviderManager')->disableOriginalConstructor()->getMock();
        $this->inject($authenticationControllerMock, 'authenticationManager', $authenticationManagerMock);

        $authenticationManagerMock->expects($this->once())->method('authenticate');
        $authenticationManagerMock->expects($this->once())->method('isAuthenticated')->willReturn(true);
        $authenticationControllerMock->expects($this->once())->method('onAuthenticationSuccess');

        $this->assertNull($authenticationControllerMock->authenticateAction());
    }

    /**
     * @test
     */
    public function testIfOnAuthenticationFailureIsCalledOnFailedAuthentication()
    {
        $authenticationControllerMock = $this->getAccessibleMock('SimplyAdmire\CrowdConnector\Controller\AuthenticationController', ['onAuthenticationFailure', 'emitAccountAuthenticationFailure', 'forward'], [], '', false);
        $authenticationManagerMock = $this->getMockBuilder('TYPO3\Flow\Security\Authentication\AuthenticationProviderManager')->disableOriginalConstructor()->getMock();

        $this->inject($authenticationControllerMock, 'authenticationManager', $authenticationManagerMock);

        $authenticationManagerMock->expects($this->once())->method('authenticate');
        $authenticationManagerMock->expects($this->once())->method('isAuthenticated')->willReturn(false);
        $authenticationControllerMock->expects($this->once())->method('onAuthenticationFailure');

        $this->assertNull($authenticationControllerMock->authenticateAction());
    }

    /**
     * @test
     */
    public function testIfMessageIsLoggedAndOnAuthenticationFailureIsCalledWhenExceptionOccurs()
    {
        $authenticationControllerMock = $this->getAccessibleMock('SimplyAdmire\CrowdConnector\Controller\AuthenticationController', ['onAuthenticationFailure', 'emitAccountAuthenticationFailure', 'forward'], [], '', FALSE);
        $authenticationManagerMock = $this->getMockBuilder('TYPO3\Flow\Security\Authentication\AuthenticationProviderManager')->disableOriginalConstructor()->getMock();
        $systemLoggerMock = $this->getMock('TYPO3\Flow\Log\Logger');

        $this->inject($authenticationControllerMock, 'authenticationManager', $authenticationManagerMock);
        $this->inject($authenticationControllerMock, 'systemLogger', $systemLoggerMock);

        $exception = new \Exception();
        $authenticationManagerMock->expects($this->once())->method('authenticate')->willThrowException($exception);
        $authenticationControllerMock->expects($this->exactly(1))->method('onAuthenticationFailure');

        $this->assertNull($authenticationControllerMock->authenticateAction());
    }

//    /**
//     * @test
//     */
//    public function testAuthenticationFailureMethod()
//    {
//        $authenticationControllerMock = $this->getAccessibleMock('SimplyAdmire\CrowdConnector\Controller\AuthenticationController', ['emitAccountAuthenticationFailure', 'forward'], [], '', FALSE);
//        $flashMessageContainerMock = $this->getMockBuilder('\TYPO3\Flow\Mvc\FlashMessageContainer')->disableOriginalConstructor()->getMock();
//
//        $this->inject($authenticationControllerMock, 'flashMessageContainer', $flashMessageContainerMock);
//
//        $flashMessageContainerMock->expects($this->exactly(1))->method('addMessage');
//        $authenticationControllerMock->expects($this->once())->method('emitAccountAuthenticationFailure');
//
//        $this->assertNull($authenticationControllerMock->onAuthenticationFailure());
//    }

    /**
     * @test
     */
    public function testEmitOnAuthenticationSuccess()
    {
        $authenticationController = new AuthenticationController();
        $this->assertNull($authenticationController->emitAccountAuthenticationSuccess());
    }

    /**
     * @test
     */
    public function testEmitOnAuthenticationFailure()
    {
        $authenticationController = new AuthenticationController();
        $this->assertNull($authenticationController->emitAccountAuthenticationFailure());
    }

}
