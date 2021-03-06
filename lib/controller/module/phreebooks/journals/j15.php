<?php
/*
 * PhreeBooks journal class for Journal 15, Inventory Store Transfer
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
 * @copyright  2008-2018, PhreeSoft Inc.
 * @license    http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @version    2.x Last Update: 2018-05-24
 * @filesource /lib/controller/module/phreebooks/journals/j15.php
 */

namespace bizuno;

require_once(BIZUNO_LIB."controller/module/phreebooks/journals/common.php");

class j15 extends jCommon
{
    protected $journalID = 15;

	function __construct($main=[], $item=[])
    {
		parent::__construct();
        $this->main = $main;
		$this->item = $item;
	}

/*******************************************************************************************************************/
// START Edit Methods
/*******************************************************************************************************************/
    /**
     * Pulls the data for the specified journal and populates the structure
     * @param array $data - current working structure
     * @param array $structure - table structures
     * @param integer $rID - record id of the transaction to load from the database
     */
    public function getDataMain(&$data, $structure, $rID=0)
    {
        $dbMain = dbGetRow(BIZUNO_DB_PREFIX.'journal_main', "id='$rID'");
        dbStructureFill($data['fields']['main'], $dbMain);
        $data['items'] = dbGetMulti(BIZUNO_DB_PREFIX.'journal_item', "ref_id='$rID'");
        $dbData = [];
        if (sizeof($data['items']) > 0) { // calculate some form fields that are not in the db
            foreach ($data['items'] as $key => $row) {
                if ($row['gl_type'] <> 'adj') { continue; } // not an adjustment record
                $values = dbGetRow(BIZUNO_DB_PREFIX."inventory", "sku='{$row['sku']}'");
                $row['qty_stock'] = $values['qty_stock']-$row['qty'];
                $row['balance']   = $values['qty_stock'];
                $row['price']     = viewFormat($values['item_cost'], 'currency');
                $dbData[$key]     = $row;
            }
        }
        $map['credit_amount'] = ['type'=>'field','index'=>'total'];
        $data['jsHead']['datagridData'] = formatDatagrid($dbData, 'datagridData', $structure['journal_item'], $map);
    }
    
    /**
     * Tailors the structure for the specific journal
     * @param array $data - current working structure
     * @param integer $rID - Database record id of the journal main record
     * @param integer $security - Users security level
     */
    public function getDataItem(&$data, $rID=0, $cID=0, $security=0)
    {
        if (!$rID && getModuleCache('extShipping', 'properties', 'status')) { $data['fields']['main']['waiting']['attr']['value'] ='1'; } // to be seen by ship manager to print label
        $data['datagrid']['item'] = $this->dgAdjust('dgJournalItem');
        $data['fields']['main']['waiting']['attr']['type'] = '1'; // for ship manager
        $data['fields']['main']['so_po_ref_id']['values']  = getModuleCache('bizuno', 'stores');
        $data['fields']['main']['so_po_ref_id']['attr']['type'] = 'select';
        $data['fields']['main']['so_po_ref_id']['events']['onChange'] = "jq('#dgJournalItem').edatagrid('endEdit', curIndex); crmDetail(this.value, '_b');";
        $data['datagrid']['item']['events']['onBeforeEdit']= "function(rowIndex) {
    var edtURL = jq(this).edatagrid('getColumnOption','sku');
    edtURL.editor.options.url = bizunoAjax+'&p=inventory/main/managerRows&clr=1&bID='+jq('#so_po_ref_id').val();
}";
        $data['datagrid']['item']['columns']['qty']['events']['editor']  = "{type:'numberbox',options:{min:0,onChange:function(){ adjCalc('qty'); } } }";
        $data['datagrid']['item']['columns']['total']['events']['editor']= "{type:'numberbox',options:{disabled:true}}";
        $choices = [['id'=>'', 'text'=>lang('select')]];
        if (sizeof(getModuleCache('extShipping', 'carriers'))) {
            foreach (getModuleCache('extShipping', 'carriers') as $settings) {
                if ($settings['status'] && isset($settings['settings']['services'])) {
                    $choices = array_merge_recursive($choices, $settings['settings']['services']);
                }
            }
        }
        $shipMeth = $data['fields']['main']['method_code']['attr']['value'];
        $data['fields']['main']['method_code'] = ['label'=>lang('journal_main_method_code'),'values'=>$choices,'attr'=>['value'=>$shipMeth,'type'=>'select']];
        unset($data['toolbars']['tbPhreeBooks']['icons']['print']);
        unset($data['toolbars']['tbPhreeBooks']['icons']['recur']);
        unset($data['toolbars']['tbPhreeBooks']['icons']['payment']);
        $isWaiting = isset($data['fields']['main']['waiting']['attr']['checked']) && $data['fields']['main']['waiting']['attr']['checked'] ? '1' : '0';
        $data['fields']['main']['waiting'] = ['attr'=>['type'=>'hidden','value'=>$isWaiting]];
        $data['divs']['divDetail'] = ['order'=>50,'type'=>'divs','classes'=>['areaView'],'attr'=>['id'=>'pbDetail'],'divs'=>[
            'props' => ['order'=>40,'type'=>'fields','classes'=>['blockView'],'attr'=>['id'=>'pbProps'],'fields'=>$this->getProps($data)],
            'totals'=> ['order'=>50,'type'=>'totals','classes'=>['blockViewR'],'attr'=>['id'=>'pbTotals'],'content'=>$data['totals_methods']]]];
        $data['divs']['dgItems']= ['order'=>60,'type'=>'datagrid','key'=>'item'];
    }

    /**
     * Configures the journal entry properties (other than address and items)
     * @param array $data - current working structure
     * @return array - List of fields to show with the structure
     */
    private function getProps($data)
    {
        return ['id'         => $data['fields']['main']['id'],
            'journal_id'     => $data['fields']['main']['journal_id'],
            'recur_id'       => $data['fields']['main']['recur_id'],
            'recur_frequency'=> $data['recur_frequency'],
            'item_array'     => $data['item_array'],
            'invoice_num'    => array_merge(['break'=>true], $data['fields']['main']['invoice_num']),
            'post_date'      => array_merge(['break'=>true], $data['fields']['main']['post_date']),
            'method_code'    => array_merge(['break'=>true], $data['fields']['main']['method_code']),
            'so_po_ref_id'   => array_merge(['break'=>true], $data['fields']['main']['so_po_ref_id'])]; // source store ID, destination store added in extension
    }

/*******************************************************************************************************************/
// START Post Journal Function
/*******************************************************************************************************************/
	public function Post()
    {
        msgDebug("\n/********* Posting Journal main ... id = {$this->main['id']} and journal_id = {$this->main['journal_id']}");
        $this->setItemDefaults(); // makes sure the journal_item fields have a value
        $this->unSetCOGSRows(); // they will be regenerated during the post
        $this->postMain();
        $this->postItem();
        if (!$this->postInventory())         { return; }
        if (!$this->postJournalHistory())    { return; }
        if (!$this->setStatusClosed('post')) { return; }
        msgDebug("\n*************** end Posting Journal ******************* id = {$this->main['id']}\n\n");
		return true;
	}

	public function unPost()
    {
        msgDebug("\n/********* unPosting Journal main ... id = {$this->main['id']} and journal_id = {$this->main['journal_id']}");
        if (!$this->unPostJournalHistory())    { return; }	// unPost the chart values before inventory where COG rows are removed
        if (!$this->unPostInventory())         { return; }
		$this->unPostMain();
        $this->unPostItem();
        if (!$this->setStatusClosed('unPost')) { return; } // check to re-open predecessor entries 
        msgDebug("\n*************** end unPosting Journal ******************* id = {$this->main['id']}\n\n");
		return true;
	}

    /**
     * Get re-post records - applies to journals 6, 7, 12, 13, 14, 15, 16, 19, 21
     * @return array - journal id's that need to be re-posted as a result of this post
     */
    public function getRepostData()
    {
		msgDebug("\n  j15 - Checking for re-post records ... ");
        $out1 = [];
        $out2 = array_merge($out1, $this->getRepostInv());
        $out3 = array_merge($out2, $this->getRepostInvCOG());
//      $out4 = array_merge($out3, $this->getRepostInvAsy());
        $out5 = array_merge($out3, $this->getRepostPayment());
        msgDebug("\n  j15 - End Checking for Re-post.");
        return $out5;
	}

	/**
     * Post journal item array to journal history table
     * applies to journal 2, 6, 7, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22
     * @return boolean - true
     */
    private function postJournalHistory()
    {
		msgDebug("\n  Posting Chart Balances...");
        if ($this->setJournalHistory()) { return true; }
	}

	/**
     * unPosts journal item array from journal history table
     * applies to journal 2, 6, 7, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22
     * @return boolean - true
     */
	private function unPostJournalHistory() {
		msgDebug("\n  unPosting Chart Balances...");
        if ($this->unSetJournalHistory()) { return true; }
	}

	/**
     * Post inventory
     * @return boolean true on success, null on error
     */
	private function postInventory()
    {
		msgDebug("\n  Posting Inventory ...");
		$ref_field = false;
        $ref_closed= false;
		$str_field = 'qty_stock';
		// adjust inventory stock status levels (also fills inv_list array)
		$item_rows_to_process = count($this->item); // NOTE: variable needs to be here because $this->item may grow within for loop (COGS)
// the cogs rows are added after this loop ..... the code below needs to be rewritten
		for ($i = 0; $i < $item_rows_to_process; $i++) {
            if (!in_array($this->item[$i]['gl_type'], ['itm','adj','asy','xfr'])) { continue; }
			if (isset($this->item[$i]['sku']) && $this->item[$i]['sku'] <> '') {
				$inv_list = $this->item[$i];
                $inv_list['price'] = $this->item[$i]['qty'] ? (($this->item[$i]['debit_amount'] + $this->item[$i]['credit_amount']) / $this->item[$i]['qty']) : 0;
				if (!$this->calculateCOGS($inv_list)) { return; }
			}
		}
		// update inventory status
		foreach ($this->item as $row) {
            if (!isset($row['sku']) || !$row['sku']) { continue; } // skip all rows without a SKU
			$item_cost = $full_price = 0;
            if (!$this->setInvStatus($row['sku'], $str_field, $row['qty'], $item_cost, $row['description'], $full_price)) { return false; }
		}
		// build the cogs item rows
        $this->setInvCogItems();
		msgDebug("\n  end Posting Inventory.");
		return true;
	}

	/**
     * unPost inventory
     * @return boolean true on success, null on error
     */
	private function unPostInventory()
    {
		msgDebug("\n  unPosting Inventory ...");
        if (!$this->rollbackCOGS()) { return false; }
		for ($i = 0; $i < count($this->item); $i++) {
            if (!isset($this->item[$i]['sku']) || !$this->item[$i]['sku']) { continue; }
            if (!$this->setInvStatus($this->item[$i]['sku'], 'qty_stock', -$this->item[$i]['qty'])) { return; }
        }
        if ($this->main['so_po_ref_id'] > 0) { $this->setInvRefBalances($this->main['so_po_ref_id'], false); }
		dbGetResult("DELETE FROM ".BIZUNO_DB_PREFIX."inventory_history WHERE ref_id = {$this->main['id']}");
		dbGetResult("DELETE FROM ".BIZUNO_DB_PREFIX."journal_cogs_usage WHERE journal_main_id={$this->main['id']}");
        dbGetResult("DELETE FROM ".BIZUNO_DB_PREFIX."journal_cogs_owed  WHERE journal_main_id={$this->main['id']}");
		msgDebug("\n  end unPosting Inventory.");
		return true;
	}

	/**
     * Checks and sets/clears the closed status of a journal entry
     * Affects journals - 3, 7, 9, 13, 14, 15, 16
     * @param string $action - [default: 'post']
     * @return boolean true
     */
	private function setStatusClosed($action='post')
    {
		msgDebug("\n  Checking for closed entry. action = $action, returning with no action.");
		return true;
	}

    /**
     * Creates the datagrid structure for inventory adjustments line items
     * @param string $name - DOM field name
     * @return array - datagrid structure
     */
	private function dgAdjust($name)
    {
		$on_hand  = jsLang('inventory', 'qty_stock');
		$on_order = jsLang('inventory', 'qty_po');
        $store_id = getUserCache('profile', 'store_id', false, 0);
		return [
            'id'   => $name,
			'type' => 'edatagrid',
			'attr' => ['toolbar'=>"#{$name}Toolbar",'rownumbers'=>true,'singleSelect'=>true,'idField'=>'id'],
			'events' => [
                'data'         => "datagridData",
				'onLoadSuccess'=> "function(row) { totalUpdate(); }",
				'onClickCell'  => "function(rowIndex) {
					switch (icnAction) {
						case 'trash': jq('#$name').edatagrid('destroyRow', rowIndex); break;
					}
					icnAction = '';
				}",
				'onClickRow'   => "function(rowIndex) { curIndex = rowIndex; }",
                'onBeforeEdit' => "function(rowIndex) {
    var edtURL = jq(this).edatagrid('getColumnOption','sku');
    edtURL.editor.options.url = '".BIZUNO_AJAX."&p=inventory/main/managerRows&clr=1&f0=a&bID='+jq('#store_id').val();
}",
				'onBeginEdit'  => "function(rowIndex) { curIndex = rowIndex; jq('#$name').edatagrid('editRow', rowIndex); }",
				'onDestroy'    => "function(rowIndex) { totalUpdate(); curIndex = undefined; }",
				'onAdd'        => "function(rowIndex) { setFields(rowIndex); }"],
			'source' => [
                'actions' => ['newItem' =>['order'=>10,'html'=>['icon'=>'add','events'=>['onClick'=>"jq('#$name').edatagrid('addRow');"]]]]],
			'columns'=> [
                'id'         => ['order'=>0, 'attr'=>  ['hidden'=>true]],
				'gl_account' => ['order'=>0, 'attr'=>  ['hidden'=>true]],
				'unit_cost'  => ['order'=>0, 'attr'=>  ['editor'=>'text', 'hidden'=>true]],
				'action'     => ['order'=>1, 'label'=>lang('action'), 'attr'=>  ['width'=>50],
					'events' => ['formatter'=>"function(value,row,index){ return ".$name."Formatter(value,row,index); }"],
					'actions'=> ['trash' => ['icon'=>'trash','order'=>20,'size'=>'small','events'=>  ['onClick'=>"icnAction='trash';"]]]],
				'sku'=> ['order'=>20, 'label'=>lang('sku'),'attr'=>['width'=>120,'sortable'=>true,'resizable'=>true,'align'=>'center'],
					'events'=>  ['editor'=>"{type:'combogrid',options:{
						width: 150, panelWidth: 540, delay: 500, idField: 'sku', textField: 'sku', mode: 'remote',
						url:        '".BIZUNO_AJAX."&p=inventory/main/managerRows&clr=1&f0=a&bID=$store_id',
						onClickRow: function (idx, data) { adjFill(data); },
						columns:[[{field:'sku',              title:'".jsLang('sku')."',width:100},
								  {field:'description_short',title:'".jsLang('description')."',width:200},
								  {field:'qty_stock',        title:'$on_hand', align:'right',width:90},
								  {field:'qty_po',           title:'$on_order',align:'right',width:90}]]
					}}"]],
				'qty_stock' => ['order'=>30,'label'=>$on_hand,'attr'=>['width'=>100,'disabled'=>true,'resizable'=>true,'align'=>'center'],
					'events'=>  ['editor'=>"{type:'numberbox',options:{disabled:true}}"]],
				'qty' => ['order'=>40,'label'=>lang('journal_item_qty', $this->journalID),'attr' =>['width'=>100,'resizable'=>true,'align'=>'center'],
					'events'=>  ['editor'=>"{type:'numberbox',options:{onChange:function(){ adjCalc('qty'); } } }"]],
				'balance' => ['order'=>50, 'label'=>lang('balance'),'styles'=>['text-align'=>'right'],
					'attr' => ['width'=>100, 'disabled'=>true, 'resizable'=>true, 'align'=>'center'],
					'events'=>  ['editor'=>"{type:'numberbox',options:{disabled:true}}"]],
				'total' => ['order'=>60, 'label'=>lang('total'),'format'=>'currency',
                    'attr'=>['width'=>120,'resizable'=>true,'align'=>'center'],
					'events'=>['editor'=>"{type:'numberbox'}"]],
				'description' => ['order'=>70,'label'=>lang('description'),'attr'=>['width'=>250,'editor'=>'text','resizable'=>true]]]];
	}
}
