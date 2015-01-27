<?php
namespace Mw\T3Compat\Plugin;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Mw.T3Compat".           *
 *                                                                        *
 * (C) 2015 Martin Helmich <m.helmich@mittwald.de>                        *
 *          Mittwald CM Service GmbH & Co. KG                             *
 *                                                                        */

use Mw\T3Compat\ContentObject\ContentObjectRenderer;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Controller\ControllerContext;

/**
 * Abstract base class for plugins.
 *
 * @package Mw\T3Compat
 * @subpackage Plugin
 */
abstract class AbstractPlugin {

	/**
	 * @var array
	 */
	protected $config, $conf;

	/**
	 * @var ContentObjectRenderer
	 */
	public $cObj;

	/**
	 * @var array
	 */
	public $piVars = [];

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 * @Flow\Inject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\Flow\Mvc\ActionRequest
	 */
	protected $request;

	/**
	 * @var ControllerContext
	 */
	private $context;

	public function __construct() {
		// Can be empty; but some plugins might call parent::__construct() inside their constructors.
	}

	public function main($content, $conf) { }

	/**
	 * @param array             $configuration
	 * @param ActionRequest     $request
	 * @param ControllerContext $context
	 */
	public function __plugin_initialize(array $configuration, ActionRequest $request, ControllerContext $context) {
		$this->config  = $configuration;
		$this->conf    = $configuration;
		$this->cObj    = new ContentObjectRenderer();
		$this->request = $request;
		$this->context = $context;
	}

	protected function pi_setPiVarDefaults() {
		// TODO: Determine what this function is supposed to be doing!
	}

	protected function pi_loadLL() {
		// TODO: Implement me!
	}

	protected function pi_wrapInBaseClass($content) {
		return '<!-- Wrapped by Mw.T3Compat package. -->' . PHP_EOL . $content;
	}

	/**
	 * @param        $pageId
	 * @param string $target
	 * @param array  $parameters
	 * @return string
	 */
	protected function pi_getPageLink($pageId, $target = '_self', array $parameters = array()) {
		return $this->context->getUriBuilder()
			->reset()
			->setArguments($parameters)
			->uriFor('index');
	}

	/**
	 * @param $string
	 * @param $pm_id
	 * @param $param
	 * @param $array
	 * @return string
	 */
	protected function pi_linkToPage($string, $pm_id, $param, $array) {
		// TODO
	}

}
