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
 * The Ext Direct request handler
 *
 * @FLOW3\Scope("singleton")
 */
class RequestHandler implements \TYPO3\FLOW3\Core\RequestHandlerInterface {

	/**
	 * @var \TYPO3\FLOW3\Core\Bootstrap
	 */
	protected $bootstrap;

	/**
	 * @var \TYPO3\ExtJS\ExtDirect\Request
	 */
	protected $request;

	/**
	 * Whether to expose exception information in an ExtDirect response
	 * @var boolean
	 */
	protected $exposeExceptionInformation = FALSE;

	/**
	 * @var \TYPO3\FLOW3\Mvc\ActionRequest
	 */
	protected $requestOfCurrentTransaction;

	/**
	 * Constructor
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 */
	public function __construct(\TYPO3\FLOW3\Core\Bootstrap $bootstrap) {
		$this->bootstrap = $bootstrap;
	}

	/**
	 * Handles a raw Ext Direct request and sends the respsonse.
	 *
	 * @return void
	 */
	public function handleRequest() {
		$sequence = $this->bootstrap->buildRuntimeSequence();
		$sequence->invoke($this->bootstrap);

		$objectManager = $this->bootstrap->getObjectManager();

		$securityContext = $objectManager->get('TYPO3\FLOW3\Security\Context');

		$this->request = $objectManager->get('TYPO3\ExtJS\ExtDirect\RequestBuilder')->build();
		$dispatcher = $objectManager->get('TYPO3\FLOW3\MVC\Dispatcher');

		$results = array();
		foreach ($this->request->getTransactions() as $transaction) {
			$requestOfCurrentTransaction = $transaction->buildRequest();
			$this->requestOfCurrentTransaction = $requestOfCurrentTransaction;
			$responseOfCurrentTransaction = $transaction->buildResponse();

			$securityContext->initialize();

			try {
				$dispatcher->dispatch($requestOfCurrentTransaction, $responseOfCurrentTransaction);
				$results[] = array(
					'type' => 'rpc',
					'tid' => $transaction->getTid(),
					'action' => $transaction->getAction(),
					'method' => $transaction->getMethod(),
					'result' => $responseOfCurrentTransaction->getResult()
				);
			} catch (\Exception $exception) {
				$systemLogger = $objectManager->get('TYPO3\FLOW3\Log\SystemLoggerInterface');
				$systemLogger->logException($exception);

					// As an exception happened, we now need to check whether detailed exception reporting was enabled.
				$configurationManager = $objectManager->get('TYPO3\FLOW3\Configuration\ConfigurationManager');
				$settings = $configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.ExtJS');
				$exposeExceptionInformation = ($settings['ExtDirect']['exposeExceptionInformation'] === TRUE);

				$exceptionWhere = ($exception instanceof \TYPO3\FLOW3\Exception) ? ' (ref ' . $exception->getReferenceCode() . ')' : '';
				$exceptionMessage = $exposeExceptionInformation ? 'Uncaught exception #' . $exception->getCode() . $exceptionWhere : 'An internal error occured';
				$results[] = array(
					'type' => 'exception',
					'tid' => $transaction->getTid(),
					'message' => $exceptionMessage,
					'where' => $exceptionWhere
				);
			}
		}

		$this->sendResponse($results, $this->request);
		$this->bootstrap->shutdown('Runtime');
	}

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
	 * Returns the request of the currently running transaction.
	 *
	 * WARNING: We do NOT return the top-level ExtDirect request here, as we want
	 * Security to use the MVC Web Request, as the ExtDirectRequest just batches
	 * numerous MVC requests for improving performance.
	 *
	 * @return \TYPO3\FLOW3\Mvc\RequestInterface The originally built web request
	 */
	public function getRequest() {
		return $this->requestOfCurrentTransaction;
	}

	/**
	 * Sends the response
	 *
	 * @param array $results The collected results from the transaction requests
	 * @param \TYPO3\ExtJS\ExtDirect\Request $extDirectRequest
	 * @return void
	 */
	protected function sendResponse(array $results, \TYPO3\ExtJS\ExtDirect\Request $extDirectRequest) {
		$response = json_encode(count($results) === 1 ? $results[0] : $results);
		if ($extDirectRequest->isFormPost() && $extDirectRequest->isFileUpload()) {
			header('Content-Type: text/html');
			echo '<html><body><textarea>' . $response . '</textarea></body></html>';
		} else {
			header('Content-Type: text/javascript');
			echo $response;
		}
	}
}
?>