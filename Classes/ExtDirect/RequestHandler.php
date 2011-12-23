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
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function handleRequest() {
		$sequence = $this->bootstrap->buildRuntimeSequence();
		$sequence->invoke($this->bootstrap);

		$objectManager = $this->bootstrap->getObjectManager();

		$this->request = $objectManager->get('TYPO3\ExtJS\ExtDirect\RequestBuilder')->build();
		$dispatcher = $objectManager->get('TYPO3\FLOW3\MVC\Dispatcher');

		$results = array();
		foreach ($this->request->getTransactions() as $transaction) {
			$transactionRequest = $transaction->buildRequest();
			$transactionResponse = $transaction->buildResponse();

			try {
				$dispatcher->dispatch($transactionRequest, $transactionResponse);
				$results[] = array(
					'type' => 'rpc',
					'tid' => $transaction->getTid(),
					'action' => $transaction->getAction(),
					'method' => $transaction->getMethod(),
					'result' => $transactionResponse->getResult()
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function canHandleRequest() {
		return isset($_GET['TYPO3_ExtJS_ExtDirectRequest']);
	}

	/**
	 * Returns the priority - how eager the handler is to actually handle the
	 * request.
	 *
	 * @return integer The priority of the request handler
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPriority() {
		return 200;
	}

	/**
	 * Returns the top level request built by the request handler.
	 *
	 * In most cases the dispatcher or other parts of the request-response chain
	 * should be preferred for retrieving the current request, because sub requests
	 * or simulated requests are built later in the process.
	 *
	 * If, however, the original top level request is wanted, this is the right
	 * method for getting it.
	 *
	 * @return \TYPO3\FLOW3\MVC\RequestInterface The originally built web request
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * Sends the response
	 *
	 * @param array $results The collected results from the transaction requests
	 * @param \TYPO3\ExtJS\ExtDirect\Request $extDirectRequest
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
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