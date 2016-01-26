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
use TYPO3\ExtJS\ExtDirect\Exception\InvalidExtDirectRequestException;
use TYPO3\Flow\Http\Component\ComponentContext;
use TYPO3\Flow\Http\Request as HttpRequest;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Log\SystemLoggerInterface;

/**
 *
 */
class DispatchComponent extends \TYPO3\Flow\Mvc\DispatchComponent
{
    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\Inject(package="TYPO3.ExtJS", setting="ExtDirect")
     * @var array
     */
    protected $extDirectSettings;

    /**
     * Create an action request from stored route match values and dispatch to that
     *
     * @param ComponentContext $componentContext
     * @return void
     */
    public function handle(ComponentContext $componentContext)
    {
        $originalResponse = $componentContext->getHttpResponse();
        try {
            $extDirectRequest = $this->buildJsonRequest($componentContext->getHttpRequest());
            $results = [];
            /** @var Transaction $transaction */
            foreach ($extDirectRequest->getTransactions() as $transaction) {
                $requestOfCurrentTransaction = $transaction->buildRequest($extDirectRequest);
                $responseOfCurrentTransaction = $transaction->buildResponse();

                try {
                    $this->securityContext->setRequest($requestOfCurrentTransaction);
                    $this->securityContext->initialize();

                    $this->dispatcher->dispatch($requestOfCurrentTransaction, $responseOfCurrentTransaction);
                    $results[] = [
                        'type' => 'rpc',
                        'tid' => $transaction->getTid(),
                        'action' => $transaction->getAction(),
                        'method' => $transaction->getMethod(),
                        'result' => $responseOfCurrentTransaction->getResult()
                    ];
                } catch (\Exception $exception) {
                    $results[] = $this->handleException($exception, $transaction->getTid());
                }
            }
            $combinedResponse = $this->prepareResponse($originalResponse, $results, $extDirectRequest);
        } catch (InvalidExtDirectRequestException $exception) {
            $results[] = $this->handleException($exception);
            $combinedResponse = $this->prepareResponse($originalResponse, $results);
        }

        $componentContext->replaceHttpResponse($combinedResponse);
    }

    /**
     * Builds a Json ExtDirect request by reading the transaction data from
     * the HTTP request body.
     *
     * @param HttpRequest $httpRequest The HTTP request
     * @return Request The Ext Direct request object
     * @throws InvalidExtDirectRequestException
     */
    protected function buildJsonRequest(HttpRequest $httpRequest)
    {
        $allTransactionData = json_decode($httpRequest->getContent());
        if ($allTransactionData === null) {
            throw new InvalidExtDirectRequestException('The request is not a valid Ext Direct request', 1268490738);
        }

        if (!is_array($allTransactionData)) {
            $allTransactionData = [$allTransactionData];
        }

        $extDirectRequest = new Request($httpRequest);
        foreach ($allTransactionData as $singleTransactionData) {
            $extDirectRequest->createAndAddTransaction(
                $singleTransactionData->action,
                $singleTransactionData->method,
                is_array($singleTransactionData->data) ? $singleTransactionData->data : [],
                $singleTransactionData->tid
            );
        }

        return $extDirectRequest;
    }

    /**
     * Prepares the response by setting content and content type as needed.
     *
     * @param Response $originalResponse
     * @param array $results The collected results from the transaction requests
     * @param Request $extDirectRequest
     * @return Response
     */
    protected function prepareResponse(Response $originalResponse, array $results, Request $extDirectRequest = null)
    {
        $responseData = json_encode(count($results) === 1 ? $results[0] : $results);

        if ($extDirectRequest !== null && $extDirectRequest->isFormPost() && $extDirectRequest->isFileUpload()) {
            $originalResponse->setContent('<html><body><textarea>' . $responseData . '</textarea></body></html>');
            return $originalResponse;
        }

        $originalResponse->setHeader('Content-Type', 'text/javascript');
        $originalResponse->setContent($responseData);

        return $originalResponse;
    }

    /**
     * Return an array with data from the given exception, suitable for being returned
     * in an ExtDirect response.
     *
     * The excpetion is logged to the system logger as well.
     *
     * @param \Exception $exception
     * @param string $transactionId
     * @return array
     */
    protected function handleException(\Exception $exception, $transactionId = null)
    {
        $this->systemLogger->logException($exception);

        // As an exception happened, we now need to check whether detailed exception reporting was enabled.
        $exposeExceptionInformation = ($this->extDirectSettings['exposeExceptionInformation'] === true);

        $exceptionWhere = ($exception instanceof \TYPO3\Flow\Exception) ? ' (ref ' . $exception->getReferenceCode() . ')' : '';
        $exceptionMessage = $exposeExceptionInformation ? 'Uncaught exception #' . $exception->getCode() . $exceptionWhere . ' - ' . $exception->getMessage() : 'An internal error occured';

        return [
            'type' => 'exception',
            'tid' => $transactionId,
            'message' => $exceptionMessage,
            'where' => $exceptionWhere
        ];
    }
}