<?php
/*
 * PhreeBooks Totals - Subtotal by checkbox
 *
 * NOTICE OF LICENSE
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.TXT.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/OSL-3.0
 *
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Bizuno to newer
 * versions in the future. If you wish to customize Bizuno for your
 * needs please refer to http://www.phreesoft.com for more information.
 *
 * @name       Bizuno ERP
 * @author     Dave Premo, PhreeSoft <support@phreesoft.com>
 * @copyright  2008-2018, PhreeSoft
 * @license    http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @version    2.0 Last Update: 2017-08-27
 * @filesource /controller/module/phreebooks/totals/subtotalChk/subtotalChk.php
 * 
 */

namespace bizuno;

class subtotalChk {
	public $code      = 'subtotalChk';
    public $moduleID  = 'phreebooks';
    public $methodDir = 'totals';
    public $required  = true;

	public function __construct()
    {
        $this->settings= ['gl_type'=>'sub','journals'=>'[17,18,20,22]','order'=>0];
        $this->lang    = getMethLang   ($this->moduleID, $this->methodDir, $this->code);
        $usrSettings   = getModuleCache($this->moduleID, $this->methodDir, $this->code, 'settings', []);
        settingsReplace($this->settings, $usrSettings, $this->settingsStructure());
	}

    public function settingsStructure()
    {
        return [
            'gl_type'   => ['attr'=>['type'=>'hidden','value'=>$this->settings['gl_type']]],
            'journals'  => ['attr'=>['type'=>'hidden','value'=>$this->settings['journals']]],
            'order'     => ['label'=>lang('order'),'position'=>'after','attr'=>['type'=>'integer','size'=>'3','value'=>$this->settings['order']]]];
	}

	public function render(&$output)
    {
        $this->fields = [
            'totals_subtotal'=>['label'=>$this->lang['subtotal'],'format'=>'currency','attr'=>['size'=>'15','value'=>'0','style'=>'text-align:right','readonly'=>'readonly']]];
		$output['body'] .= '<div style="text-align:right">'."\n";
		$output['body'] .= html5('totals_subtotal', $this->fields['totals_subtotal']) ."\n";
		$output['body'] .= "</div>\n";
        $output['jsHead'][] = "function totals_subtotalChk(begBalance) {
    var newBalance = 0;
    var rowData = jq('#dgJournalItem').datagrid('getData');
    for (var i=0; i<rowData.rows.length; i++) if (rowData.rows[i]['checked']) {
        var amount  = parseFloat(rowData.rows[i].total);
        var discount= parseFloat(rowData.rows[i].discount);
        if (isNaN(amount))   amount   = 0;
        if (isNaN(discount)) discount = 0;
        newBalance += amount + discount;
    }
    jq('#totals_subtotal').val(formatCurrency(newBalance));
    return newBalance;
}";
	}
}
