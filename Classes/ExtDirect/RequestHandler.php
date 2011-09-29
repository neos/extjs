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

/**
 * The Ext Direct request handler
 *
 */
class RequestHandler implements \TYPO3\FLOW3\MVC\RequestHandlerInterface {

	/**
	 * @var \TYPO3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var \TYPO3\FLOW3\MVC\Dispatcher
	 */
	protected $dispatcher;

	/**
	 * @var \TYPO3\ExtJS\ExtDirect\RequestBuilder
	 */
	protected $requestBuilder;

	/**
	 * @var \TYPO3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * Whether to expose exception information in an ExtDirect response
	 * @var boolean
	 */
	protected $exposeExceptionInformation = FALSE;

	/**
	 * Constructs the Ext Direct Request Handler
	 *
	 * @param \TYPO3\FLOW3\Utility\Environment $utilityEnvironment A reference to the environment
	 * @param \TYPO3\FLOW3\MVC\Dispatcher $dispatcher The request dispatcher
	 * @param \TYPO3\ExtJS\ExtDirect\RequestBuilder $requestBuilder
	 * @param \TYPO3\FLOW3\Log\SystemLoggerInterface $systemLogger
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(
			\TYPO3\FLOW3\Utility\Environment $utilityEnvironment,
			\TYPO3\FLOW3\MVC\Dispatcher $dispatcher,
			\TYPO3\ExtJS\ExtDirect\RequestBuilder $requestBuilder,
			\TYPO3\FLOW3\Log\SystemLoggerInterface $systemLogger) {
		$this->environment = $utilityEnvironment;
		$this->dispatcher = $dispatcher;
		$this->requestBuilder = $requestBuilder;
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Inject the settings
	 *
	 * @param array $settings The settings
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function injectSettings(array $settings) {
		if (!isset($settings['ExtDirect'])) return;

		$this->exposeExceptionInformation = ($settings['ExtDirect']['exposeExceptionInformation'] === TRUE);
	}

	/**
	 * Handles a raw Ext Direct request and sends the respsonse.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function handleRequest() {
		$extDirectRequest = $this->requestBuilder->build();

		$results = array();
		foreach ($extDirectRequest->getTransactions() as $transaction) {
			$transactionRequest = $transaction->buildRequest();
			$transactionResponse = $transaction->buildResponse();

			try {
				$this->dispatcher->dispatch($transactionRequest, $transactionResponse);
				$results[] = array(
					'type' => 'rpc',
					'tid' => $transaction->getTid(),
					'action' => $transaction->getAction(),
					'method' => $transaction->getMethod(),
					'result' => $transactionResponse->getResult()
				);
			} catch (\Exception $exception) {
				$this->systemLogger->logException($exception);
				$exceptionMessage = $this->exposeExceptionInformation ? $exception->getMessage() : 'An internal error occured';
				$exceptionWhere = $this->exposeExceptionInformation ? $exception->getTraceAsString() : '';
				$results[] = array(
					'type' => 'exception',
					'tid' => $transaction->getTid(),
					'message' => $exceptionMessage,
					'where' => $exceptionWhere
				);
			}
		}

		$this->sendResponse($results, $extDirectRequest);
	}

	/**
	 * Checks if the request handler can handle the current request.
	 *
	 * @return boolean TRUE if it can handle the request, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function canHandleRequest() {
		$getArguments = $this->environment->getRawGetArguments();
		return isset($getArguments['TYPO3_ExtJS_ExtDirectRequest']);
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