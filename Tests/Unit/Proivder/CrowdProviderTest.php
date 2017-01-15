<?php
namespace SimplyAdmire\CrowdConnector\Tests\Unit\Provider;

use SimplyAdmire\CrowdConnector\Provider\CrowdProvider;
use SimplyAdmire\CrowdConnector\Service\CrowdApiService;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Tests\UnitTestCase;

class CrowdProviderTest extends UnitTestCase
{

    /**
     * @var CrowdProvider
     */
    protected $crowdProvider;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->crowdProvider = $this->getAccessibleMock('SimplyAdmire\CrowdConnector\Provider\CrowdProvider', ['dummy'], [], '', false);
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        unset($this->crowdProvider);
    }

    /**
     * @test
     */
    public function testIfNoCredentialsCodeIsGivenWhenNoCredentialsAreSet()
    {
        $this->markTestSkipped('must be revisited.');
        $mockTokenInterface = $this->getMock('Neos\Flow\Security\Authentication\Token\UsernamePassword', ['getCredentials'], [], '', false);
        $mockTokenInterface->expects($this->exactly(1))->method('getCredentials')->willReturn(null);
        $crowdApiService = $this->getMock('SimplyAdmire\CrowdConnector\Service\CrowdApiService', ['getAuthenticationResponse'], [], '', false);
        $this->inject($this->crowdProvider, 'crowdApiService', $crowdApiService);
        $crowdApiService->expects($this->never())->method('getAuthenticationResponse');
        $this->crowdProvider->authenticate($mockTokenInterface);
    }

    /**
     * @test
     */
    public function testWrongCredentialsCodeIsSetIfNameDoesNotEqualResponseName()
    {
        $mockTokenInterface = $this->getAccessibleMock('Neos\Flow\Security\Authentication\Token\UsernamePassword', ['getCredentials', 'setAuthenticationStatus'], [], '', false);

        $credentials = [
            'username' => 'foo',
            'password' => 'bar'
        ];
        $authenticationResponse = [
            'info' => [
                'http_code' => 200
            ],
            'response' => [
                'name' => 'baz'
            ]
        ];
        $crowdApiService = $this->getMock('SimplyAdmire\CrowdConnector\Service\CrowdApiService', ['getAuthenticationResponse'], [], '', false);
        $this->inject($this->crowdProvider, 'crowdApiService', $crowdApiService);

        $mockTokenInterface->expects($this->exactly(1))->method('getCredentials')->willReturn($credentials);
        $crowdApiService->expects($this->exactly(1))->method('getAuthenticationResponse')->with($credentials)->willReturn($authenticationResponse);
        $mockTokenInterface->expects($this->exactly(1))->method('setAuthenticationStatus');

        $this->assertNull($this->crowdProvider->authenticate($mockTokenInterface));
    }

    /**
     * @test
     */
    public function testIfErrorCodeIsSetWhenUsernameIsCorrectButNoUserFound()
    {
        $mockTokenInterface = $this->getAccessibleMock('Neos\Flow\Security\Authentication\Token\UsernamePassword', ['getCredentials', 'setAuthenticationStatus'], [], '', false);
        $mockAccountRepository = $this->getMockBuilder('Neos\Flow\Security\AccountRepository')->disableOriginalConstructor()->getMock();
        $credentials = [
            'username' => 'foo',
            'password' => 'bar'
        ];
        $authenticationResponse = [
            'info' => [
                'http_code' => 200
            ],
            'response' => [
                'name' => 'foo'
            ]
        ];
        $crowdApiService = $this->getMock('SimplyAdmire\CrowdConnector\Service\CrowdApiService', ['getAuthenticationResponse'], [], '', false);
        $this->inject($this->crowdProvider, 'crowdApiService', $crowdApiService);
        $this->inject($this->crowdProvider, 'accountRepository', $mockAccountRepository);

        $mockTokenInterface->expects($this->exactly(1))->method('getCredentials')->willReturn($credentials);
        $crowdApiService->expects($this->exactly(1))->method('getAuthenticationResponse')->with($credentials)->willReturn($authenticationResponse);
        $mockTokenInterface->expects($this->exactly(1))->method('setAuthenticationStatus');
        $mockAccountRepository->expects($this->once())->method('findActiveByAccountIdentifierAndAuthenticationProviderName');

        $this->assertNull($this->crowdProvider->authenticate($mockTokenInterface));
    }
    /**
     * @test
     */
    public function testIfUserIsSetWhenUserIfFound()
    {
        $mockTokenInterface = $this->getAccessibleMock('Neos\Flow\Security\Authentication\Token\UsernamePassword', ['setAccount', 'getCredentials', 'setAuthenticationStatus'], [], '', false);
        $mockAccountRepository = $this->getMockBuilder('Neos\Flow\Security\AccountRepository')->disableOriginalConstructor()->getMock();
        $account = new Account();

        $credentials = [
            'username' => 'foo',
            'password' => 'bar'
        ];
        $authenticationResponse = [
            'info' => [
                'http_code' => 200
            ],
            'response' => [
                'name' => 'foo'
            ]
        ];
        $crowdApiService = $this->getMock('SimplyAdmire\CrowdConnector\Service\CrowdApiService', ['getAuthenticationResponse'], [], '', false);
        $this->inject($this->crowdProvider, 'crowdApiService', $crowdApiService);
        $this->inject($this->crowdProvider, 'accountRepository', $mockAccountRepository);

        $mockTokenInterface->expects($this->exactly(1))->method('getCredentials')->willReturn($credentials);
        $crowdApiService->expects($this->exactly(1))->method('getAuthenticationResponse')->with($credentials)->willReturn($authenticationResponse);
        $mockTokenInterface->expects($this->exactly(1))->method('setAuthenticationStatus');
        $mockAccountRepository->expects($this->once())->method('findActiveByAccountIdentifierAndAuthenticationProviderName')->willReturn($account);
        $mockTokenInterface->expects($this->once())->method('setAccount');

        $this->assertNull($this->crowdProvider->authenticate($mockTokenInterface));
    }

}
