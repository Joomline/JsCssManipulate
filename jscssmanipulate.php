<?php
defined('_JEXEC') or die('Restricted access');
use Joomla\Utilities\ArrayHelper;

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

class plgSystemJsCssManipulate extends JPlugin
{
    private $footherScripts, $footherCss;


    function onBeforeCompileHead()
    {
        $app = JFactory::getApplication();
        if ($app->isAdmin()) {
            return;
        }

        $doc = JFactory::getDocument();

        $config = $this->prepareConfig();

        $debug = $this->params->get('debug', '0');

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

                    if (!empty($params->remove)) {
                        if($this->checkExceptions($params->remove_exceptions)){
                            $debug && $debugInfo .= '<span class="label label-primary">EXCEPTIONS</span>';
                        }
                        else{
                            $debug && $debugInfo .= '<span class="label label-danger">REMOVED</span>';
                            unset($doc->_scripts[$searchUrl]);
                        }
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

                    if (!empty($params->remove)) {
                        if($this->checkExceptions($params->remove_exceptions)){
                            $debug && $debugInfo .= '<span class="label label-primary">EXCEPTIONS</span>';
                        }
                        else{
                            $debug && $debugInfo .= '<span class="label label-danger">REMOVED</span>';
                            unset($doc->_styleSheets[$searchUrl]);
                        }
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

        if ($debug) {
            $app->enqueueMessage($debugInfo, JText::_('PLG_JSCSSMANIPULATE_JS_DEBUG'));
        }
        return true;
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

    private function checkExceptions($removeExceptions){
        $return = false;
        $removeExceptions = trim($removeExceptions);
        if(empty($removeExceptions)){
            return false;
        }
        $removeExceptions = explode('&', $removeExceptions);
        if(!count($removeExceptions)){
            return false;
        }

        $input = JFactory::getApplication()->input;
        $aCheck = array();
        foreach ($removeExceptions as $removeException) {
            $removeException = explode('=', $removeException);
            if(empty($removeException[1])){
                return false;
            }
            $values = explode(',', $removeException[1]);
            $aCheck[] = (int)in_array($input->getString($removeException[0], ''), $values);
        }

        return !in_array(0, $aCheck);
    }
}