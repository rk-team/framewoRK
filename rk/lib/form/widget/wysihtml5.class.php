<?php

namespace rk\form\widget;

class wysihtml5 extends \rk\form\widget\textarea {
	
	/**
	 * @desc : display a wysiwyg html5
	 * params for widget :
	 * 		toolButtons : array
	 * 
	 * 			bold : add the bold command (true/false)
	 * 			italic : add the italic command (true/false)
	 * 
	 * 			nbColors : numbers of color choice displayed (use css for configuration)
	 * 			createLink : display the create link box (true/false)
	 * 
	 * 			orderedList : display ordered List (true/false)
	 * 			unorderedList : display unordered list (true/false)
	 * 
	 * 			tagName : set to display command that add a balise with the corresponding tagName (array that list tag)
	 * 
	 * 			code: set to display code block, the value of this params is an array that contain class to add to balise code
	 * 				  ex: [javascript, php]
	 * 
	 * 			justifyCenter : center text (true/false)
	 * 			justifyLeft : align text left (true/false)
	 * 			justifyRight : align text right (true/false)
	 * 
	 * 		editorCss : string array files to add for the editor text-area
	 * 		template : file name for template output located in ressources/forms/widgets 
	 */
	
	public function getParamsForTpl() {
		$tplParams = parent::getParamsForTpl();
		
		
		$toolButtons = $this->getParam('toolButtons');
		if ($toolButtons === false) {
			$toolButtons = array (
				'bold' 	 => true,
				'italic' => true,
				'nbColors'  => 4
			);
		}
		
		$jsParams = $this->getParam('jsParams');
		if (empty($jsParams)) {
			$jsParams = array();
		}
		
		$editorCss = '["/css/rk/wysihtml5.css"';
		$cssParams = $this->getParam('editorCss');
		if (!empty($cssParams)) {
			foreach ($cssParams as $oneCss) {
				$editorCss .= ', "' . $oneCss . '"';
			}
		}
		$editorCss .= ']';
		
		$tplParams['containerId'] = 'wysihtml5Cotnainer_' . $this->id;
		$tplParams['toolBarId'] = 'toolBar_' . $this->id;
		$tplParams['toolButtons'] = $toolButtons;
		$tplParams['editorCss'] = $editorCss;
		$tplParams['jsParams'] = $jsParams;

		return $tplParams;
	}
}
