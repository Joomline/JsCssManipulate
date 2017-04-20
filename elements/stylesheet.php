<?php
/*------------------------------------------------------------------------
# author    Jeremy Magne
# copyright Copyright (C) 2010 Daycounts.com. All Rights Reserved.
# Websites: http://www.daycounts.com
# Technical Support: http://www.daycounts.com/en/contact/
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
-------------------------------------------------------------------------*/

defined('JPATH_BASE') or die();

class JFormFieldStylesheet extends JFormField {

    public function getInput()	{

        JHtml::_('jquery.framework');
        JFactory::getDocument()->addScript(JUri::root().'plugins/system/jscssmanipulate/assets/js/script.js');

        $value  = $this->value;

        $html = '<table width="100%">';
        $html .= '<thead>';
        $html .= '<th>'.JText::_('PLG_JSCSSMANIPULATE_STYLESHEET').'</th>';
        $html .= '<th>'.JText::_('PLG_JSCSSMANIPULATE_TO_FOOTHER').'</th>';
        $html .= '<th>'.JText::_('PLG_JSCSSMANIPULATE_REMOVE').'</th>';
        $html .= '<th></th>';
        $html .= '</thead>';
        $html .= '<tbody id="jscssmanipulate-css-tbody">';
        if(!is_array($value) || !count($value)){
            $countValue = 0;
        }
        else{
            $countValue = count($value);
            $k = 0;
            foreach ($value as $v){
                $checkedFoother = (!empty($v['foother'])) ? ' checked' : '';
                $checkedRemove = (!empty($v['remove'])) ? ' checked' : '';
                $html .= '<tr>';
                $html .= '<td align="center"><input style="width: 400px;" type="text" name="'.$this->name.'['.$k.'][path]" value="'.$v['path'].'"></td>';
                $html .= '<td align="center"><input type="checkbox" name="'.$this->name.'['.$k.'][foother]" value="1"'.$checkedFoother.'></td>';
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

