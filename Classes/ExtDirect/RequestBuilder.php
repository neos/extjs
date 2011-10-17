<?php
namespace TYPO3\ExtJS\ExtDirect;

/*                                                                        *
 * This script belongs to the FLOW3 package "ExtJS".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * The Ext Direct request builder
 *
 */
class RequestBuilder {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\MVC\Web\Routing\RouterInterface
	 */
	protected $router;

	/**
	 * Builds an Ext Direct request
	 *
	 * @return \TYPO3\ExtJS\ExtDirect\Request The built request
	 */
	public function build() {
		$postArguments = $this->environment->getRawPostArguments();
		if (isset($postArguments['extAction'])) {
			throw new \Exception('Form Post Request building is not yet implemented.', 1281379502);
			$request = $this->buildFormPostRequest($postArguments);
		} else {
			$request = $this->buildJsonRequest();
		}

			// Explicitly configure the router like the original RequestBuilder
		$routesConfiguration = $this->configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_ROUTES);
		$this->router->setRoutesConfiguration($routesConfiguration);

		return $request;
	}

	/**
	 * Builds a Json Ext Direct request by reading the transaction data from
	 * standard input.
	 *
	 * @return \TYPO3\ExtJS\ExtDirect\Request The Ext Direct request object
	 * @throws \TYPO3\ExtJS\ExtDirect\Exception\InvalidExtRequestException
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildJsonRequest() {
		$transactionDatas = json_decode(file_get_contents('php://input'));
		if ($transactionDatas === NULL) {
			throw new \TYPO3\ExtJS\ExtDirect\Exception\InvalidExtDirectRequestException('The request is not a valid Ext Direct request', 1268490738);
		}

		if (!is_array($transactionDatas)) {
			$transactionDatas = array($transactionDatas);
		}

		$request = $this->objectManager->create('TYPO3\ExtJS\ExtDirect\Request');
		foreach ($transactionDatas as $transactionData) {
			$request->createAndAddTransaction(
				$transactionData->action,
				$transactionData->method,
				is_array($transactionData->data) ? $transactionData->data : array(),
				$transactionData->tid
			);
		}
		return $request;
	}

	/**
	 * Builds a Form Post Ext Direct Request
	 *
	 * @return \TYPO3\ExtJS\ExtDirect\Request The Ext Direct request object
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo Well... make it work, eh?
	 */
	protected function buildFormPostRequest() {
		$directRequest->setFormPost(TRUE);
		$directRequest->setFileUpload($request->getArgument('extUpload') === 'true');

		$packageKey = $request->getArgument('packageKey');
		$subpackageKey = $request->hasArgument('subpackageKey') ? $request->getArgument('subpackageKey') : '';

		$directRequest->addTransaction(
			$request->getArgument('extAction'),
			$request->getArgument('extMethod'),
			NULL,
			$request->getArgument('extTID'),
			$packageKey,
			$subpackageKey
		);
	}
}
?>