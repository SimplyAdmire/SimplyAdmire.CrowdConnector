<?php
namespace SimplyAdmire\CrowdConnector\Controller;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface;
use TYPO3\Flow\Security\Authentication\Controller\AbstractAuthenticationController;

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
	 * @return void
	 */
	public function authenticateAction() {
		try {
			$this->authenticationManager->authenticate();
			$this->onAuthenticationSuccess();
		} catch (\Exception $exception) {
			$message = $exception->getMessage();
			$this->systemLogger->log($message, LOG_AUTH);
			$this->onAuthenticationFailure();
		}
	}

	/**
	 * @param ActionRequest|NULL $request
	 * @return void
	 */
	public function onAuthenticationSuccess(ActionRequest $request = NULL) {
		$this->emitAccountAuthenticationSuccess();
	}

	/**
	 * @param ActionRequest|NULL $request
	 * @return void
	 */
	public function onAuthenticationFailure(ActionRequest $request = NULL) {
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