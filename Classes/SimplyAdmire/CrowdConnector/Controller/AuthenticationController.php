<?php
namespace SimplyAdmire\CrowdConnector\Controller;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error\Error;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface;
use TYPO3\Flow\Security\Authentication\Controller\AbstractAuthenticationController;
use TYPO3\Flow\Security\Exception\AuthenticationRequiredException;

class AuthenticationController extends AbstractAuthenticationController {

	/**
	 * @Flow\Inject
	 * @var AuthenticationManagerInterface
	 */
	protected $authenticationManager;

	/**
	 * Displays the login screen
	 *
	 * @return void
	 */
	public function loginAction() {
	}

	/**
	 * Actually authenticate with given credentials
	 * Sends a signal when authentication is successful or not
	 * slots can be filled as desired
	 *
	 * @Flow\SkipCsrfProtection
	 * @return void
	 */
	public function authenticateAction() {
		try {
			$this->authenticationManager->authenticate();
			if ($this->authenticationManager->isAuthenticated()) {
				$this->onAuthenticationSuccess();
			} else {
				$this->onAuthenticationFailure();
			}
		} catch (\Exception $exception) {
			$message = $exception->getMessage();
			$this->systemLogger->log($message, LOG_AUTH);
			$this->onAuthenticationFailure();
		}
	}

	/**
	 * @param ActionRequest|NULL $request
	 * @return string
	 */
	protected function onAuthenticationSuccess(ActionRequest $request = NULL) {
		$this->emitAccountAuthenticationSuccess();
	}

	/**
	 * @param AuthenticationRequiredException|NULL $exception
	 * @return void
	 */
	protected function onAuthenticationFailure(AuthenticationRequiredException $exception = NULL) {
		$this->flashMessageContainer->addMessage(new Error('Authentication failed!', ($exception === NULL ? 1347016771 : $exception->getCode())));
		$this->emitAccountAuthenticationFailure();
		$this->forward('login');
	}

	/**
	 * @Flow\Signal
	 * @return void
	 */
	public function emitAccountAuthenticationSuccess() {
	}

	/**
	 * @Flow\Signal
	 * @return void
	 */
	public function emitAccountAuthenticationFailure() {
	}

}