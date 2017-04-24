<?php
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

        if (count($minifierUrls['js']) || count($minifierUrls['css'])) {
            $this->prepareMinified($doc, $minifierUrls);
        }

        if ($debug) {
            $app->enqueueMessage($debugInfo, JText::_('PLG_JSCSSMANIPULATE_DEBUG'));
        }
        return true;
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
            $doc->_styleSheets[$fileUrl] = array(
                "mime" => "text/cs",
                "media" => null,
                "attribs" => array()
            );
        } else {
            $this->footherCss[$fileUrl] = array(
                "mime" => "text/cs",
                "media" => null,
                "attribs" => array()
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
                $defaultCssMimes = array('text/css');
                foreach ($this->footherCss as $strSrc => $strAttr) {
                    $html .= '<link href="' . $strSrc . '" rel="stylesheet"';

                    if (!is_null($strAttr['mime']) && (!$document->isHtml5() || !in_array($strAttr['mime'], $defaultCssMimes))) {
                        $html .= ' type="' . $strAttr['mime'] . '"';
                    }

                    if (!is_null($strAttr['media'])) {
                        $html .= ' media="' . $strAttr['media'] . '"';
                    }

                    if (is_array($strAttr['attribs'])) {
                        if ($temp = ArrayHelper::toString($strAttr['attribs'])) {
                            $html .= ' ' . $temp;
                        }
                    }

                    $html .= ' />';
                    $html .= "\n";
                }
            }

            if (is_array($this->footherScripts) && count($this->footherScripts)) {
                foreach ($this->footherScripts as $strSrc => $strAttr) {
                    $html .= '<script src="' . $strSrc . '"';
                    if (!is_null($strAttr['mime']) && (!$document->isHtml5() || !in_array($strAttr['mime'], $defaultJsMimes))) {
                        $html .= ' type="' . $strAttr['mime'] . '"';
                    }
                    if ($strAttr['defer']) {
                        $html .= ' defer';
                        if (!$document->isHtml5()) {
                            $html .= '="defer"';
                        }
                    }
                    if ($strAttr['async']) {
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
        $config = array('scripts' => array(), 'css' => array());
        $scripts = $this->params->get('scripts', '');
        $css = $this->params->get('css', '');
        $scripts = is_object($scripts) ? (array)$scripts : $scripts;
        $css = is_object($css) ? (array)$css : $css;

        if (is_array($scripts) && count($scripts)) {
            foreach ($scripts as $script) {
                if (!empty($script->path))
                    $config['scripts'][$script->path] = $script;
            }
        }

        if (is_array($css) && count($css)) {
            foreach ($css as $cs) {
                if (!empty($cs->path))
                    $config['css'][$cs->path] = $cs;
            }
        }

        return $config;
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