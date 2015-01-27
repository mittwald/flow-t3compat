<?php
namespace Mw\T3Compat\ContentObject;

use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Utility\Files;

class ContentObjectRenderer {

	public function stdWrap($value, array $options) {
		// Fuck you, I'm not going to re-implement fucking stdWrap!
		return $value;
	}

	public function wrap($content, $wrap) {
		list($left, $right) = Arrays::trimExplode('|', $wrap);
		return $left . $content . $right;
	}

	public function fileResource($filename) {
		if (!file_Exists($filename)) {
			throw new \Exception('File ' . $filename . ' does not exist!');
		}
		return Files::getFileContents($filename);
	}

	public function getSubpart($template, $subpart) {
		return HtmlParser::getSubpart($template, $subpart);
	}

	public function substituteMarkerArrayCached($template, array $marker) {
		return HtmlParser::substituteMarkerArray($template, $marker);
	}

	public function substituteMarkerArray($template, array $marker) {
		return HtmlParser::substituteMarkerArray($template, $marker);
	}

	public function substituteSubpart($template, $marker, $content) {
		return HtmlParser::substituteSubpart($template, $marker, $content);
	}

}