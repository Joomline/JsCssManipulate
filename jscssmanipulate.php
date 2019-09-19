<?php
/**
 * jscssmanipulate
 *
 * @version 	1.0.0
 * @author		Arkadiy Sedelnikov, Joomline
 * @copyright	© 2017. All rights reserved.
 * @license 	GNU/GPL v.2 or later.
 */
defined('_JEXEC') or die('Restricted access');
use Joomla\Utilities\ArrayHelper;
use MatthiasMullie\Minify;

if (version_compare(JVERSION, '3.5.0', 'ge')) {
    if (!class_exists('StringHelper1')) {
        class StringHelper1 extends \Joomla\String\StringHelper
        {
        }
    }
} else {
    if (!class_exists('StringHelper1')) {
        jimport('joomla.string.string');

        class StringHelper1 extends JString
        {
        }
    }
}
jimport('joomla.filesystem.folder');

class plgSystemJsCssManipulate extends JPlugin
{
    private $footherScripts, $footherCss, $minifiedPath, $minifiedUrl;
	private static $config;

    function __construct($subject, array $config = array())
    {
        parent::__construct($subject, $config);
        $this->minifiedPath = JPATH_ROOT . '/cache/plg_system_jscssmanipulate';
        $this->minifiedUrl = JUri::root() . 'cache/plg_system_jscssmanipulate';
        if (!is_dir($this->minifiedPath)) {
            JFolder::create($this->minifiedPath);
        }
    }

    function onBeforeCompileHead()
    {
        $app = JFactory::getApplication();
        if ($app->isAdmin()) {
            return true;
        }

        $doc = JFactory::getDocument();

        $config = $this->prepareConfig();

        $debug = $this->params->get('debug', '0');
        $minify = $this->params->get('minify', 0);
        $cutScript = $this->params->get('cut_script', '');
        $minifierUrls = array('js' => array(), 'css' => array());

        if ($minify) {
            require_once __DIR__ . '/lib/minify/includes.php';
            require_once __DIR__ . '/lib/path-converter/includes.php';
        }

        if ($debug) {
            $lang = JFactory::getLanguage();
            $lang->load('plg_system_jscssmanipulate', JPATH_SITE . '/plugins/system/jscssmanipulate');

        }

        $debugInfo = '';

        if (count($config['scripts'])) {

            $debug && $debugInfo .= '<ul><h3>' . JText::_('PLG_JSCSSMANIPULATE_SCRIPTS') . ':</h3>';

            $this->footherScripts = array();
            foreach ($doc->_scripts as $searchUrl => $scriptparams) {
                if (isset($config['scripts'][$searchUrl])) {
                    $params = $config['scripts'][$searchUrl];

                    $debug && $debugInfo .= '<li>' . $searchUrl . ' ==> ';

                    if (!empty($params->remove) && !$this->checkExceptions($params->remove_exceptions, $debug, $debugInfo)) {
                        $debug && $debugInfo .= '<span class="label label-warning">REMOVED</span>';
                        unset($doc->_scripts[$searchUrl]);
                    } else if ($minify && $params->minify) {
                        $minifierUrls['js'][] = $searchUrl;
                        $debug && $debugInfo .= '<span class="label label-inverse">MINIFIED</span>';
                        unset($doc->_scripts[$searchUrl]);
                    } else {
                        if (!empty($params->defer)) {
                            $debug && $debugInfo .= '<span class="label label-success">DEFER</span>';
                            $doc->_scripts[$searchUrl]['defer'] = true;
                        }
                        if (!empty($params->async)) {
                            $debug && $debugInfo .= '<span class="label label-success">ASYNC</span>';
                            $doc->_scripts[$searchUrl]['async'] = true;
                        }

                        if (!empty($params->foother)) {
                            $debug && $debugInfo .= '<span class="label label-danger">MOVED TO FOOTHER</span>';
                            $this->footherScripts[$searchUrl] = $doc->_scripts[$searchUrl];
                            unset($doc->_scripts[$searchUrl]);
                        }
                    }

                    $debug && $debugInfo .= '</li>';
                } else {
                    $debug && $debugInfo .= '<li>' . $searchUrl . ' ==><span class="label label-important">NOT CHANGED</span></li>';
                }
            }
            $debug && $debugInfo .= '</ul>';
        }

        if (count($config['css'])) {

            $debug && $debugInfo .= '<ul><h3>' . JText::_('PLG_JSCSSMANIPULATE_CSS') . ':</h3>';

            $this->footherCss = array();
            foreach ($doc->_styleSheets as $searchUrl => $scriptparams) {
                if (isset($config['css'][$searchUrl])) {
                    $params = $config['css'][$searchUrl];

                    $debug && $debugInfo .= '<li>' . $searchUrl . ' ==> ';

                    if (!empty($params->remove) && !$this->checkExceptions($params->remove_exceptions, $debug, $debugInfo)) {
                        $debug && $debugInfo .= '<span class="label label-danger">REMOVED</span>';
                        unset($doc->_styleSheets[$searchUrl]);
                    } else if ($minify && $params->minify) {
                        $minifierUrls['css'][] = $searchUrl;
                        $debug && $debugInfo .= '<span class="label label-inverse">MINIFIED</span>';
                        unset($doc->_styleSheets[$searchUrl]);
                    } else if (!empty($params->foother)) {
                        $debug && $debugInfo .= '<span class="label label-danger">MOVED TO FOOTHER</span>';
                        $this->footherCss[$searchUrl] = $doc->_styleSheets[$searchUrl];
                        unset($doc->_styleSheets[$searchUrl]);
                    }

                    $debug && $debugInfo .= '</li>';
                } else {
                    $debug && $debugInfo .= '<li>' . $searchUrl . ' ==><span class="label label-important">NOT CHANGED</span></li>';
                }
            }
            $debug && $debugInfo .= '</ul>';
        }

        if (count($config['sassless'])) {

            $debug && $debugInfo .= '<ul><h3>' . JText::_('PLG_JSCSSMANIPULATE_SASSLESS') . ':</h3>';


            foreach ($config['sassless'] as $from => $to)
            {
                $fromPath = $this->getFullFilePath($from);
                if(is_file($fromPath)){
                    $fileInfo = stat($fromPath);
                    $fromFileTime = $fileInfo['mtime'];

                    $toPath =  $this->getFullFilePath($to);
                    $compile = true;

                    if(is_file($toPath)){
                        $fileInfo = stat($toPath);
                        $toFileTime = $fileInfo['mtime'];
                        if($fromFileTime < $toFileTime){
                            $compile = false;
                        }
                    }

                    if($compile){
                        $ext = JFile::getExt($fromPath);
                        $this->compileSassLess($ext, $fromPath, $toPath, $debug, $debugInfo);
                    }
                    else{
                        $debug && $debugInfo .= '<li>' . $fromPath . ' <span class="label label-important">NOT CHANGED</span></li>';
                    }
                }
                else{
                    $debug && $debugInfo .= '<li>' . $toPath . ' <span class="label label-inverse">does not exist</span></li>';
                }
            }

            $debug && $debugInfo .= '</ul>';
        }

        if (count($minifierUrls['js']) || count($minifierUrls['css'])) {
            $this->prepareMinified($doc, $minifierUrls);
        }

        if (!empty($cutScript)) {
            $cutScript = explode("\n", $cutScript);
            $cutScript = array_map("trim", $cutScript);
            foreach ($cutScript as $k => $v){
                if(empty($v)){
                    unset($cutScript[$k]);
                }
            }

            if(count($cutScript)){
                $debug && $debugInfo .= '<ul><h3>' . JText::_('PLG_JSCSSMANIPULATE_SCRIPT') . ':</h3>';
                $debug && $debugInfo .= '<li><h4>' . JText::_('PLG_JSCSSMANIPULATE_SCRIPT_EXPR') . ':</h4>
                    <pre>'.print_r($cutScript, true).'</pre></li>';
                $debug && $debugInfo .= '<li><h4>' . JText::_('PLG_JSCSSMANIPULATE_SCRIPT_BEFORE') . ':</h4>
                    <pre>'.print_r($doc->_script['text/javascript'], true).'</pre></li>';
                $doc->_script['text/javascript'] = preg_replace($cutScript, '', $doc->_script['text/javascript']);
                $debug && $debugInfo .= '<li><h4>' . JText::_('PLG_JSCSSMANIPULATE_SCRIPT_AFTER') . ':</h4>
                    <pre>'.print_r($doc->_script['text/javascript'], true).'</pre></li>';
                $debug && $debugInfo .= '</ul>';
            }
        }

        if ($debug) {
            $app->enqueueMessage($debugInfo, JText::_('PLG_JSCSSMANIPULATE_DEBUG'));
        }
        return true;
    }

    private function compileSassLess($ext, $fromPath, $toPath, $debug, &$debugInfo){
        jimport('joomla.filesystem.file');
        if($ext == 'less'){
            require_once __DIR__.'/lib/lessphp/lessc.inc.php';
            $less = new lessc;
            try{
                $css = $less->compileFile($fromPath);
            }
            catch (Exception $e){
                $debug && $debugInfo .= '<li>' . $fromPath . ' <span class="label label-inverse">' . $e->getMessage() . '</span></li>';
                return;
            }
            if(!JFile::write($toPath, $css)){
                $debug && $debugInfo .= '<li>' . $toPath . ' <span class="label label-inverse">error writing file</span></li>';
            }
            else{
                $debug && $debugInfo .= '<li>' . $fromPath . ' => ' . $toPath . ' <span class="label label-danger">COMPILED</span></li>';
            }
        }
        else if($ext == 'scss' || $ext == 'sass'){
            require_once __DIR__.'/lib/scssphp/scss.inc.php';
            $scssc = new scssc;
            $content = file_get_contents($fromPath);
            try{
                $css = $scssc->compile($content);
            }
            catch (Exception $e){
                $debug && $debugInfo .= '<li>' . $fromPath . ' <span class="label label-inverse">' . $e->getMessage() . '</span></li>';
                return;
            }
            if(!JFile::write($toPath, $css)){
                $debug && $debugInfo .= '<li>' . $toPath . ' <span class="label label-inverse">error writing file</span></li>';
            }
            else{
                $debug && $debugInfo .= '<li>' . $fromPath . ' => ' . $toPath . ' <span class="label label-danger">COMPILED</span></li>';
            }
        }
        else{
            $debug && $debugInfo .= '<li>' . $toPath . ' <span class="label label-inverse">File *.'.$ext.' not handled</span></li>';
        }
    }

    private function getFullFilePath($path){
        $filePath =  StringHelper1::trim($path);
        if(StringHelper1::strpos($filePath, DIRECTORY_SEPARATOR) !== 0){
            $filePath = JPATH_ROOT . DIRECTORY_SEPARATOR . $filePath;
        }
        else{
            $filePath = JPATH_ROOT . $filePath;
        }
        return $filePath;
    }

    private function prepareMinified(&$doc, $minifierUrls)
    {
        $app = JFactory::getApplication();
        if (count($minifierUrls['js'])) {
            $filename = md5(implode(',', $minifierUrls['js'])) . '.js';
            $filePath = $this->minifiedPath . '/' . $filename;
            $fileUrl = $this->minifiedUrl . '/' . $filename;
            if (is_file($filePath)) {
                $this->addMinifiedJs($doc, $fileUrl);
            } else {
                $minifier = new Minify\JS();
                foreach ($minifierUrls['js'] as $url) {
                    $path = $this->preparePath($url);
                    if ($path === false) {
                        $app->enqueueMessage('Error converting url ' . $url . ' to filepath.', 'error');
                        continue;
                    }
                    $minifier->add($path);
                }

                $minifier->minify($filePath);

                if (is_file($filePath)) {
                    $this->addMinifiedJs($doc, $fileUrl);
                } else {
                    $app->enqueueMessage('Error create minified js file' . $fileUrl);
                }
            }
        }

        if (count($minifierUrls['css'])) {
            $filename = md5(implode(',', $minifierUrls['css'])) . '.css';
            $filePath = $this->minifiedPath . '/' . $filename;
            $fileUrl = $this->minifiedUrl . '/' . $filename;

            if (is_file($filePath)) {
                $this->addMinifiedCss($doc, $fileUrl);
            } else {
                $minifier = new Minify\CSS();
                foreach ($minifierUrls['css'] as $url) {
                    $path = $this->preparePath($url);
                    if ($path === false) {
                        $app->enqueueMessage('Error converting url ' . $url . ' to filepath.', 'error');
                        continue;
                    }
                    $minifier->add($path);
                }

                $minifier->minify($filePath);

                if (is_file($filePath)) {
                    $this->addMinifiedCss($doc, $fileUrl);
                } else {
                    $app->enqueueMessage('Error create minified css file' . $fileUrl);
                }
            }
        }
    }

    private function addMinifiedCss(&$doc, $fileUrl)
    {
        if ($this->params->get('minify_css_position', 'head') == 'head') {
            $doc->_styleSheets[$fileUrl] = array (
            "type" => "text/css",
	        "options" => array()
	        );
        } else {
            $this->footherCss[$fileUrl] = array(
	            "type" => "text/css",
	            "options" => array()
            );
        }
    }

    private function addMinifiedJs(&$doc, $fileUrl)
    {
        if ($this->params->get('minify_js_position', 'head') == 'head') {
            $doc->_scripts[$fileUrl] = array(
                "mime" => "text/javascript",
                "defer" => false,
                "async" => false
            );
        } else {
            $this->footherScripts[$fileUrl] = array(
                "mime" => "text/javascript",
                "defer" => false,
                "async" => false
            );
        }
    }

    private function preparePath($url)
    {
        $siteRoot = JUri::root();
        if ((StringHelper1::strpos($url, 'http') === 0 && StringHelper1::strpos($url, $siteRoot) === false)
            || StringHelper1::strpos($url, '//') === 0
        ) {
            return false;
        }
        if (StringHelper1::strpos($url, $siteRoot) === false) {
            $url = StringHelper1::strpos($url, '/') === 0 ? $siteRoot . StringHelper1::substr($url, 1) : $siteRoot . $url;
        }
        $parts = parse_url($url);
        if (empty($parts["path"])) {
            return false;
        }
        if (!is_file(JPATH_ROOT . $parts["path"])) {
            return false;
        }
        return JPATH_ROOT . $parts["path"];
    }

    public function onAfterRender()
    {
        if (!is_array($this->footherScripts) || !count($this->footherScripts)) {
            return;
        }

        $app = JFactory::getApplication();
        $document = JFactory::getDocument();
        $buffer = $app->getBody();
        if ($buffer !== null) {
            $defaultJsMimes = array('text/javascript', 'application/javascript', 'text/x-javascript', 'application/x-javascript');
            $html = '';

            if (is_array($this->footherCss) && count($this->footherCss)) {

	            if($this->params->get('enable_foother_css_ordering',0)){
		            $config = $this->prepareConfig();
		            if(is_array($config['css']) && count($config['css'])){
			            $aNewCss = array();
			            foreach ($config['css'] as $key => $css){
				            if(isset($this->footherCss[$key])){
					            $aNewCss[$key] = $this->footherCss[$key];
				            }
			            }
			            $this->footherCss = $aNewCss;
		            }
	            }

                $defaultCssMimes = array('text/css');
                foreach ($this->footherCss as $strSrc => $strAttr) {
                    $html .= '<link href="' . $strSrc . '" rel="stylesheet"';

                    if (!empty($strAttr['mime']) && (!$document->isHtml5() || !in_array($strAttr['mime'], $defaultCssMimes))) {
                        $html .= ' type="' . $strAttr['mime'] . '"';
                    }

                    if (!empty($strAttr['media'])) {
                        $html .= ' media="' . $strAttr['media'] . '"';
                    }

                    if (isset($strAttr['attribs']) && is_array($strAttr['attribs'])) {
                        if ($temp = ArrayHelper::toString($strAttr['attribs'])) {
                            $html .= ' ' . $temp;
                        }
                    }

                    $html .= ' />';
                    $html .= "\n";
                }
            }

            if (is_array($this->footherScripts) && count($this->footherScripts)) {

            	if($this->params->get('enable_foother_scripts_ordering',0)){
		            $config = $this->prepareConfig();
		            if(is_array($config['scripts']) && count($config['scripts'])){
		            	$aNewScripts = array();
			            foreach ($config['scripts'] as $scriptKey => $script){
							if(isset($this->footherScripts[$scriptKey])){
								$aNewScripts[$scriptKey] = $this->footherScripts[$scriptKey];
							}
			            }
			            $this->footherScripts = $aNewScripts;
		            }
	            }

                foreach ($this->footherScripts as $strSrc => $strAttr) {
                    $html .= '<script src="' . $strSrc . '"';
                    if (!empty($strAttr['mime']) && (!$document->isHtml5() || !in_array($strAttr['mime'], $defaultJsMimes))) {
                        $html .= ' type="' . $strAttr['mime'] . '"';
                    }
                    if (!empty($strAttr['defer'])) {
                        $html .= ' defer';
                        if (!$document->isHtml5()) {
                            $html .= '="defer"';
                        }
                    }
                    if (!empty($strAttr['async'])) {
                        $html .= ' async';
                        if (!$document->isHtml5()) {
                            $html .= '="async"';
                        }
                    }

                    $html .= '></script>';
                    $html .= "\n";
                }
            }

            if (!empty($html)) {
                $html .= '</body>';
                $buffer = StringHelper1::str_ireplace('</body>', $html, $buffer, 1);
            }

            $app->setBody($buffer);
        }
    }

    private function prepareConfig()
    {
    	if(!is_array(self::$config))
    	{
		    self::$config = array('scripts' => array(), 'css' => array(), 'sassless' => array());
		    $scripts = $this->params->get('scripts', '');
		    $css = $this->params->get('css', '');
		    $sassless = $this->params->get('sassless', '');
		    $scripts = is_object($scripts) ? (array)$scripts : $scripts;
		    $css = is_object($css) ? (array)$css : $css;
		    $sassless = is_object($sassless) ? (array)$sassless : $sassless;

		    if (is_array($scripts) && count($scripts)) {
			    foreach ($scripts as $script) {
				    if (!empty($script->path))
					    self::$config['scripts'][$script->path] = $script;
			    }
		    }

		    if (is_array($css) && count($css)) {
			    foreach ($css as $cs) {
				    if (!empty($cs->path))
					    self::$config['css'][$cs->path] = $cs;
			    }
		    }

		    if (is_array($sassless) && count($sassless)) {

			    foreach ($sassless as $sl) {
				    if (!empty($sl->path))
					    self::$config['sassless'][$sl->path] = $sl->css_path;
			    }
		    }
	    }

        return self::$config;
    }

    private function checkExceptions($removeExceptions, $debug, &$debugInfo)
    {
        $removeExceptions = trim($removeExceptions);
        if (empty($removeExceptions)) {
            return false;
        }
        $removeExceptions = explode('&', $removeExceptions);
        if (!count($removeExceptions)) {
            return false;
        }

        $input = JFactory::getApplication()->input;
        $aCheck = array();
        foreach ($removeExceptions as $removeException) {
            $removeException = explode('=', $removeException);
            if (empty($removeException[1])) {
                return false;
            }
            $values = explode(',', $removeException[1]);
            $aCheck[] = (int)in_array($input->getString($removeException[0], ''), $values);
        }

        $exception = !in_array(0, $aCheck);

        if ($exception) {
            $debug && $debugInfo .= '<span class="label label-primary">EXCEPTION</span>';
        }

        return $exception;
    }
}