<?php

/**
 * jscssmanipulate
 *
 * @version 	1.0.0
 * @author		Arkadiy Sedelnikov, Joomline
 * @copyright	© 2017. All rights reserved.
 * @license 	GNU/GPL v.2 or later.
 */

defined('JPATH_BASE') or die();

class JFormFieldStylesheet extends JFormField {

    public function getInput()	{

		$isEnabled = JPluginHelper::isEnabled('system', 'jscssmanipulate');
		$plugin = JPluginHelper::getPlugin('system', 'jscssmanipulate');		
		$plgParams = $isEnabled ? new JRegistry($plugin->params) : new JRegistry();
		$enableMinify = $plgParams->get('minify',0);

        JHtml::_('jquery.framework');
	    JHtml::_('jquery.ui', array('core', 'sortable'));
        JFactory::getDocument()->addScript(JUri::root().'plugins/system/jscssmanipulate/assets/js/script.js');
        JFactory::getDocument()->addScriptDeclaration('
            var enableCssMinify = '.$enableMinify.';
            jQuery(function($) {
                $( ".sortable-css" ).sortable();
            });
        ');

        $value  = $this->value;

        $html = '<table width="100%">';
        $html .= '<thead>';
	    $html .= '<th><span class="icon-menu-2"></span></th>';
        $html .= '<th>'.JText::_('PLG_JSCSSMANIPULATE_STYLESHEET').'</th>';
        $html .= '<th>'.JText::_('PLG_JSCSSMANIPULATE_TO_FOOTHER').'</th>';
        if($enableMinify){
            $html .= '<th>'.JText::_('PLG_JSCSSMANIPULATE_MINIFY').'</th>';
        }
        $html .= '<th>'.JText::_('PLG_JSCSSMANIPULATE_REMOVE').'</th>';
        $html .= '<th></th>';
        $html .= '</thead>';
        $html .= '<tbody id="jscssmanipulate-css-tbody" class="sortable-css">';
        if(!is_array($value) || !count($value)){
            $countValue = 0;
        }
        else{
            $countValue = count($value);
            $k = 0;
            foreach ($value as $v){
                $checkedFoother = (!empty($v['foother'])) ? ' checked' : '';
                $checkedRemove = (!empty($v['remove'])) ? ' checked' : '';
                $checkedMinify = (!empty($v['minify'])) ? ' checked' : '';
                $html .= '<tr>';
	            $html .= '
				<td align="center">
                	<span style="cursor: move;" class="sortable-handler">
					    <span class="icon-menu"></span>
					</span>
                </td>';
                $html .= '<td align="center"><input style="width: 400px;" type="text" name="'.$this->name.'['.$k.'][path]" value="'.$v['path'].'"></td>';
                $html .= '<td align="center"><input type="checkbox" name="'.$this->name.'['.$k.'][foother]" value="1"'.$checkedFoother.'></td>';
                if($enableMinify){
                    $html .= '<td align="center"><input type="checkbox" name="'.$this->name.'['.$k.'][minify]" value="1"'.$checkedMinify.'></td>';
                }
                $html .= '<td align="center"><input type="checkbox" name="'.$this->name.'['.$k.'][remove]" value="1"'.$checkedRemove.'>';
                $html .= '<input type="text" name="'.$this->name.'['.$k.'][remove_exceptions]" value="'.$v['remove_exceptions'].'"></td>';
                $html .= '<td align="center"><input type="button" class="btn btn-danger btn-small" value="X" onclick="removeRow(this);"></td>';
                $html .= '</tr>';
                $k++;
            }
        }
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '<br>';
        $html .= '<input type="hidden" value="'.$countValue.'" id="jscssmanipulate-css-count">';
        $html .= '<input type="button" class="btn btn-success btn-small" value="'.JText::_('PLG_JSCSSMANIPULATE_ADD_ROW').'" onclick="addStylesheetRow();">';
        return $html;
    }
}

