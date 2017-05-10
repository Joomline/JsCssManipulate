<?php

defined('JPATH_BASE') or die();

class JFormFieldSassless extends JFormField {

    public function getInput()	{
        JHtml::_('jquery.framework');
        JFactory::getDocument()->addScript(JUri::root().'plugins/system/jscssmanipulate/assets/js/script.js');
        $value  = $this->value;

        $html = '<table width="100%">';
        $html .= '<thead>';
        $html .= '<th>'.JText::_('PLG_JSCSSMANIPULATE_SASSLESS_FILE').'</th>';
        $html .= '<th>'.JText::_('PLG_JSCSSMANIPULATE_SASSLESS_CSS_FILE').'</th>';
        $html .= '<th></th>';
        $html .= '</thead>';
        $html .= '<tbody id="jscssmanipulate-sassless-tbody">';
        if(!is_array($value) || !count($value)){
            $countValue = 0;
        }
        else{
            $countValue = count($value);
            $k = 0;
            foreach ($value as $v){
                $html .= '<tr>';
                $html .= '<td align="center"><input style="width: 400px;" type="text" name="'.$this->name.'['.$k.'][path]" value="'.$v['path'].'"></td>';
                $html .= '<td align="center"><input style="width: 400px;" type="text" name="'.$this->name.'['.$k.'][css_path]" value="'.$v['css_path'].'"></td>';
                $html .= '<td align="center"><input type="button" class="btn btn-danger btn-small" value="X" onclick="removeRow(this);"></td>';
                $html .= '</tr>';
                $k++;
            }
        }
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '<br>';
        $html .= '<input type="hidden" value="'.$countValue.'" id="jscssmanipulate-sassless-count">';
        $html .= '<input type="button" class="btn btn-success btn-small" value="'.JText::_('PLG_JSCSSMANIPULATE_ADD_ROW').'" onclick="addSasslessRow();">';
        return $html;
    }
}

