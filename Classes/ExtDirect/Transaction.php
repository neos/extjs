<?php
declare(ENCODING = 'utf-8');
namespace F3\ExtJS\ExtDirect;

/*                                                                        *
 * This script belongs to the FFLOW3 package "ExtJS".                     *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 *
 * @version $Id: EmptyView.php 2813 2009-07-16 14:02:34Z k-fish $
 */

/**
 * An Ext Direct transaction
 *
 * @version $Id: EmptyView.php 2813 2009-07-16 14:02:34Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Transaction {

	/**
	 * @inject
	 * @var \F3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * The direct request this transaction belongs to
	 *
	 * @var \F3\ExtJS\ExtDirect\Request
	 */
	protected $request;

	/**
	 * The controller / class to use
	 *
	 * @var string
	 */
	protected $action;

	/**
	 * The action / method to execute
	 *
	 * @var string
	 */
	protected $method;

	/**
	 * The arguments to be passed to the method
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * The transaction ID to associate with this request
	 *
	 * @var int
	 */
	protected $tid;

	/**
	 *
	 * @param Request $request The direct request this transaction belongs to
	 */
	public function __construct(\F3\ExtJS\ExtDirect\Request $request, $action, $method, $data, $tid) {
		$this->request = $request;
		$this->action = $action;
		$this->method = $method;
		$this->data = $data;
		$this->tid = $tid;
	}

	/**
	 * @return string The action
	 */
	public function getAction() {
		return $this->action;
	}

	public function getMethod() {
		return $this->method;
	}

	public function getData() {
		return $this->data;
	}

	public function getType() {
		return 'rpc';
	}

	public function getTid() {
		return $this->tid;
	}


	public function getControllerObjectName() {
		return 'F3\\' . str_replace('_', '\\', $this->action);
	}

	/**
	 * Ext Direct does not provide named arguments by now, so we have
	 * to map them by reflecting on the action parameters.
	 *
	 * @return array The mapped arguments
	 */
	protected function getArguments() {
		$arguments = array();
		if (!$this->request->isFormPost()) {
			$parameters = $this->reflectionService->getMethodParameters($this->getControllerObjectName(), $this->method . 'Action');

			// TODO Add checks for parameters
			foreach ($parameters as $name => $options) {
				$parameterIndex = $options['position'];
				$arguments[$name] = $this->data[$parameterIndex];
			}

		} else {
			// TODO Reuse setArgumentsFromRawRequestData from Web/RequestBuilder
		}
		return $arguments;
	}


}
?>