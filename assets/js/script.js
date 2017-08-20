/**
 * Created by arkadiy on 19.04.17.
 */

function removeRow(element) {
    jQuery(element).parents('tr').remove();
}

function addScriptRow(){
    var tbody = jQuery('#jscssmanipulate-js-tbody');
    var countInput = jQuery('#jscssmanipulate-js-count');
    var count = parseInt(countInput.val())+1;
    var newRow = '<tr>' +
        '<td align="center"><span style="cursor: move;" class="sortable-handler"><span class="icon-menu"></span></span></td>'+
        '<td align="center"><input name="jform[params][scripts]['+count+'][path]" value="" type="text" style="width: 400px;"></td>' +
        '<td align="center"><input name="jform[params][scripts]['+count+'][defer]" value="1" type="checkbox"></td>' +
        '<td align="center"><input name="jform[params][scripts]['+count+'][async]" value="1" type="checkbox"></td>' +
        '<td align="center"><input name="jform[params][scripts]['+count+'][foother]" value="1" type="checkbox"></td>' ;
    if(enableJsMinify){
        newRow += '<td align="center"><input name="jform[params][scripts]['+count+'][minify]" value="1" type="checkbox">';
    }
    newRow += '<td align="center"><input name="jform[params][scripts]['+count+'][remove]" value="1" type="checkbox">' +
        '<input name="jform[params][scripts]['+count+'][remove_exceptions]" value="" type="text"></td>' +
        '<td align="center"><input class="btn btn-danger btn-small" value="X" onclick="removeRow(this);" type="button"></td>' +
        '</tr>';
    countInput.val(count);
    tbody.append(newRow).sortable();
}

function addStylesheetRow(){
    var tbody = jQuery('#jscssmanipulate-css-tbody');
    var countInput = jQuery('#jscssmanipulate-css-count');
    var count = parseInt(countInput.val())+1;
    var newRow = '<tr>' +
        '<td align="center"><span style="cursor: move;" class="sortable-handler"><span class="icon-menu"></span></span></td>'+
        '<td align="center"><input name="jform[params][css]['+count+'][path]" value="" type="text" style="width: 400px;"></td>' +
        '<td align="center"><input name="jform[params][css]['+count+'][foother]" value="1" type="checkbox"></td>' ;
    if(enableJsMinify){
        newRow += '<td align="center"><input name="jform[params][css]['+count+'][minify]" value="1" type="checkbox">';
    }
    newRow += '<td align="center"><input name="jform[params][css]['+count+'][remove]" value="1" type="checkbox">' +
        '<input name="jform[params][css]['+count+'][remove_exceptions]" value="" type="text"></td>' +
        '<td align="center"><input class="btn btn-danger btn-small" value="X" onclick="removeRow(this);" type="button"></td>' +
        '</tr>';
    countInput.val(count);
    tbody.append(newRow).sortable();
}

function addSasslessRow(){
    var tbody = jQuery('#jscssmanipulate-sassless-tbody');
    var countInput = jQuery('#jscssmanipulate-sassless-count');
    var count = parseInt(countInput.val())+1;
    var newRow = '<tr>' +
        '<td align="center"><input name="jform[params][sassless]['+count+'][path]" value="" type="text" style="width: 400px;"></td>' +
        '<td align="center"><input name="jform[params][sassless]['+count+'][css_path]" value="" type="text" style="width: 400px;"></td>' +
        '<td align="center"><input class="btn btn-danger btn-small" value="X" onclick="removeRow(this);" type="button"></td>' +
        '</tr>';
    countInput.val(count);
    tbody.append(newRow);
}