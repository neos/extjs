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
 * A transparent view that extends JsonView and passes on the prepared array
 * to the Ext Direct response.
 *
 */
class View extends \TYPO3\FLOW3\MVC\View\JsonView {
	/**
	 * Renders the Ext Direct view by delegating to the JsonView
	 * for rendering a serializable array.
	 *
	 * @return string An empty string
	 */
	public function render() {
		$result = $this->renderArray();
		$this->controllerContext->getResponse()->setResult($result);
		$this->controllerContext->getResponse()->setSuccess(TRUE);
	}

	/**
	 * Assigns errors to the view and converts them to a format that Ext JS
	 * understands.
	 *
	 * @param \TYPO3\FLOW3\Error\Result $result Errors e.g. from mapping results
	 */
	public function assignErrors(\TYPO3\FLOW3\Error\Result $result) {
		$errors = $result->getFlattenedErrors();
		$output = array();
		foreach ($errors as $propertyPath => $propertyErrors) {
			$message = '';
			foreach ($propertyErrors as $propertyError) {
				$message .= $propertyError->getMessage();
			}
			$output[$propertyPath] = $message;
		}
		$this->assign('value', array(
			'errors' => $output,
			'success' => FALSE
		));
	}
}
?>