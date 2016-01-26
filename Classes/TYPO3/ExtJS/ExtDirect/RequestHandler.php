<?php
namespace TYPO3\ExtJS\ExtDirect;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.ExtJS".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\ExtJS\ExtDirect\Exception\InvalidExtDirectRequestException;
use TYPO3\Flow\Http\Component\ComponentChain;
use TYPO3\Flow\Http\Component\ComponentContext;
use TYPO3\Flow\Http\Uri;

/**
 * The ExtDirect request handler
 *
 * @Flow\Scope("singleton")
 */
class RequestHandler extends \TYPO3\Flow\Http\RequestHandler {

	/**
	 * Whether to expose exception information in an ExtDirect response
	 * @var boolean
	 */
	protected $exposeExceptionInformation = FALSE;

	/**
	 * @var array
	 */
	protected $flowSettings;

	/**
	 * Checks if the request handler can handle the current request.
	 *
	 * @return boolean TRUE if it can handle the request, otherwise FALSE
	 */
	public function canHandleRequest() {
		return isset($_GET['TYPO3_ExtJS_ExtDirectRequest']);
	}

	/**
	 * Returns the priority - how eager the handler is to actually handle the
	 * request.
	 *
	 * @return integer The priority of the request handler
	 */
	public function getPriority() {
		return 200;
	}

	/**
	 * Handles a raw ExtDirect request and sends the response.
	 *
	 * @return void
	 */
	public function handleRequest() {
			// Create the request very early so the Resource Management has a chance to grab it:
		$this->request = \TYPO3\Flow\Http\Request::createFromEnvironment();
		$this->response = new \TYPO3\Flow\Http\Response();

		$this->boot();
		$this->resolveDependencies();
		if (isset($this->settings['http']['baseUri'])) {
			$this->request->setBaseUri(new Uri($this->settings['http']['baseUri']));
		}

		$componentContext = new ComponentContext($this->request, $this->response);
		$this->baseComponentChain->handle($componentContext);

		$this->response->send();
		$this->bootstrap->shutdown('Runtime');
		$this->exit->__invoke();
	}

	/**
	 * Resolves a few dependencies of this request handler which can't be resolved
	 * automatically due to the early stage of the boot process this request handler
	 * is invoked at.
	 *
	 * @return void
	 */
	protected function resolveDependencies() {
		parent::resolveDependencies();

		$objectManager = $this->bootstrap->getObjectManager();

		$configurationManager = $objectManager->get('TYPO3\Flow\Configuration\ConfigurationManager');
		$this->settings = $configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.ExtJS');
		$this->flowSettings = $configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow');

		$componentChainFactory = $objectManager->get('TYPO3\Flow\Http\Component\ComponentChainFactory');

		$this->baseComponentChain = $componentChainFactory->create($this->settings['ExtDirect']['httpComponentChain']);
	}
}
