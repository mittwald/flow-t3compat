<?php
namespace Mw\T3Compat\Utility;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Package\PackageInterface;
use TYPO3\Flow\Package\PackageManagerInterface;

class ExtensionManagementUtility {

	/**
	 * @var PackageManagerInterface
	 * @Flow\Inject
	 */
	protected $packageManager;

	public function isLoaded($key, $exitOnError = FALSE) {
		$result = $this->packageManager->isPackageActive($key);
		if ($exitOnError === TRUE && $result === FALSE) {
			throw new \Exception("Package {$key} was not loaded!");
		}
		return $result;
	}

	public function extRelPath($key) {
		return $this->extPath($key);
	}

	public function extPath($key, $script = '') {
		$package = $this->packageManager->getPackage($key);
		if (NULL === $package) {
			return NULL;
		}

		return $package->getPackagePath() . $script;
	}

	public function siteRelPath($key) {
		return "_Resources/Static/{$key}";
	}

	public function getCN($key) {
		return str_replace(' ', '\\', ucwords(str_replace('_', ' ', $key)));
	}

	public function getExtensionKeyByPrefix($prefix) {
		return str_replace('\\', '.', $prefix);
	}

	public function clearExtensionKeyMap() { }

	public function getExtensionVersion($key) { }

	public function removeCacheFiles() { }

	public function getLoadedExtensionListArray() {
		return array_map(
			function (PackageInterface $p) {
				return $p->getPackageKey();
			},
			$this->packageManager->getActivePackages()
		);
	}

	public function getRequiredExtensionListArray() {
		return array_map(
			function (PackageInterface $p) {
				return $p->getPackageKey();
			},
			$this->packageManager->getFilteredPackages('active', 'Framework')
		);
	}
}