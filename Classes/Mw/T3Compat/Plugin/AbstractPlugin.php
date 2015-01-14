<?php
namespace Mw\T3Compat\Plugin;

use Mw\T3Compat\ContentObject\ContentObjectRenderer;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\ActionRequest;

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
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 * @Flow\Inject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\Flow\Mvc\Routing\UriBuilder
	 * @Flow\Inject
	 */
	protected $uriBuilder;

	/**
	 * @var \TYPO3\Flow\Mvc\ActionRequest
	 */
	protected $request;

	public function main($content, $conf) { }

	public function __flowBridge_initialize(array $configuration, ActionRequest $request) {
		$this->config  = $configuration;
		$this->conf    = $configuration;
		$this->cObj    = new ContentObjectRenderer();
		$this->request = $request;
	}

	protected function pi_setPiVarDefaults() {
		// TODO: Determine what this function is supposed to be doing!
	}

	protected function pi_loadLL() {
		// TODO: Implement me!
	}

	protected function pi_wrapInBaseClass($content) {
		return '<!-- Wrapped by Mw.TYPO3.PluginBridge package. -->' . PHP_EOL . $content;
	}

	protected function pi_getPageLink($pageId, $target = '_self', array $parameters = array()) {
		$this->uriBuilder->setRequest($this->request);

		return $this->uriBuilder
			->setArguments($parameters)
			->uriFor('index');
	}

}
