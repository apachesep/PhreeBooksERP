<?php
/*
 * PhreeBooks journal class for Journal 17, Vendor Refund
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
 * @version    2.x Last Update: 2018-06-07
 * @filesource /lib/controller/module/phreebooks/journals/j17.php
 */

namespace bizuno;

require_once(BIZUNO_LIB."controller/module/phreebooks/journals/common.php");

class j17 extends jCommon
{
    public $journalID = 17;

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
    public function getDataMain(&$data, $structure, $rID=0, $cID=0)
    {
        $content = $this->action=='bulk' ? jrnlGetBulkData() : jrnlGetPaymentData($rID, $cID);
        if (sizeof($content['main']) > 0) { foreach ($content['main'] as $field => $value) { $data['fields']['main'][$field]['attr']['value'] = $value; } }
        $data['items'] = (sizeof($content['items']) > 0) ? $content['items'] : [];
    }
    
    /**
     * Tailors the structure for the specific journal
     * @param array $data - current working structure
     * @param integer $rID - Database record id of the journal main record
     * @param integer $security - Users security level
     */
    public function getDataItem(&$data, $rID=0, $cID=0, $security=0)
    {
        if (!empty($data['bulk']) || !empty($data['fields']['main']['contact_id_b']['attr']['value'])) {
            $data['fields']['main']['terminal_date']['attr']['type'] = 'hidden';
            $dgStructure= $this->action=='bulk' ? $this->dgBankingBulk('dgJournalItem') : $this->dgBanking('dgJournalItem');
            // pull out just the pmt rows to build datagrid
            $dgData = [];
            foreach ($data['items'] as $row) { if ($row['gl_type'] == 'pmt') { $dgData[] = $row; } }
            $map['credit_amount']= ['type'=>'field', 'index'=>'amount'];
            $data['jsHead']['datagridData'] = formatDatagrid($dgData, 'datagridData', $dgStructure['columns'], $map);
            unset($data['toolbars']['tbPhreeBooks']['icons']['recur']);
            unset($data['toolbars']['tbPhreeBooks']['icons']['payment']);
            if ($rID || $cID) {
                $temp = new paymentMain();
                $temp->render($data); // add payment methods and continue
            }
            if ($rID || $cID) { $data['datagrid']['item'] = $dgStructure; }
            if (isset($data['fields']['main']['waiting']['attr']['checked']) && $data['fields']['main']['waiting']['attr']['checked'] == 'checked') {
                $data['fields']['main']['waiting']= ['attr'=>  ['type'=>'hidden', 'value'=>'1']];
            } else {
                $data['fields']['main']['waiting']= ['attr'=>  ['type'=>'hidden', 'value'=>'0']];
            }
            if (isset($data['fields']['main']['closed']['attr']['checked']) && $data['fields']['main']['closed']['attr']['checked'] == 'checked') {
                $data['fields']['main']['closed']= ['attr'=>  ['type'=>'hidden', 'value'=>'1']];
            } else {
                $data['fields']['main']['closed']= ['attr'=>  ['type'=>'hidden', 'value'=>'0']];
            }
            $data['divs']['divDetail'] = ['order'=>50,'type'=>'divs','classes'=>['areaView'],'attr'=>['id'=>'pbDetail'],'divs'=>[
                'billAD'  => ['order'=>20,'type'=>'address', 'classes'=>['blockView'], 'attr'=>['id'=>'address_b'],'content'=>$this->cleanAddress($data['fields']['main'], '_b'),
                    'label'=>lang('bill_to'),'settings'=>['suffix'=>'_b','search'=>true,'copy'=>false,'validate'=>true,'fill'=>'both','required'=>true,'store'=>false]],
                'props'   => ['order'=>40,'type'=>'fields',  'classes'=>['blockView'], 'attr'=>['id'=>'pbProps'], 'fields'=>$this->getProps($data)],
                'totals'  => ['order'=>50,'type'=>'totals',  'classes'=>['blockViewR'],'attr'=>['id'=>'pbTotals'],'content'=>$data['totals_methods']],
                'payments'=> ['order'=>60,'classes'=>['blockView'],'attr'=>['id'=>'pbPayment'],'src'=>BIZUNO_LIB."view/module/payment/accPmtDetail.php"]]];
            $data['divs']['dgItems']= ['order'=>60,'type'=>'datagrid','key'=>'item'];
            $data['jsBody']['frmVal'] = "function preSubmit() {
	var items = new Array();	
	var dgData = jq('#dgJournalItem').datagrid('getData');
	for (var i=0; i<dgData.rows.length; i++) if (dgData.rows[i]['checked']) items.push(dgData.rows[i]);
	var serializedItems = JSON.stringify(items);
	jq('#item_array').val(serializedItems);
	if (!formValidate()) return false;
	return true;
}";
            $data['jsReady']['divInit'] = "ajaxForm('frmJournal'); jq('#contactSel_b').next().find('input').focus();";
        } else {
            unset($data['divs']['tbJrnl']);
            $data['divs']['divDetail']  = ['order'=>50,'type'=>'html','html'=>html5('contactSel', ['label'=>lang('search')])];
            $data['jsBody']['selVendor']= "jq('#contactSel').combogrid({width:120,panelWidth:500,delay:500,idField:'contact_id_b',textField:'primary_name_b',mode:'remote',
    url:       '".BIZUNO_AJAX."&p=phreebooks/main/managerRowsBank&jID=".JOURNAL_ID."', 
    onBeforeLoad:function (param) { var newValue = jq('#contactSel').combogrid('getValue'); if (newValue.length < 2) return false; },
    onClickRow:function (idx, row) { journalEdit(".JOURNAL_ID.", 0, row.contact_id_b); },
    columns:[[
        {field:'contact_id_b',  hidden:true},
        {field:'primary_name_b',title:'".jsLang('address_book_primary_name')."', width:200},
        {field:'city_b',        title:'".jsLang('address_book_city')."', width:100},
        {field:'state_b',       title:'".jsLang('address_book_state')."', width: 50},
        {field:'total_amount',  title:'".jsLang('total')."', width:100, align:'right', formatter:function (value) {return formatCurrency(value);} }]]
});
if (jq('#contactSel').length) jq('#contactSel').next().find('input').focus();";            
        }
    }

    /**
     * Configures the journal entry properties (other than address and items)
     * @param array $data - current working structure
     * @return array - List of fields to show with the structure
     */
    private function getProps($data)
    {
        $data['fields']['main']['sales_order_num'] = ['label'=>lang('journal_main_invoice_num_10'),'attr'=>['value'=>isset($this->soNum)?$this->soNum:'','readonly'=>'readonly']];
        return [
            'id'             => $data['fields']['main']['id'],
            'journal_id'     => $data['fields']['main']['journal_id'],
            'so_po_ref_id'   => $data['fields']['main']['so_po_ref_id'],
            'terms'          => $data['fields']['main']['terms'],
            'override_user'  => $data['override_user'],
            'override_pass'  => $data['override_pass'],
            'recur_id'       => $data['fields']['main']['recur_id'],
            'recur_frequency'=> $data['recur_frequency'],
            'item_array'     => $data['item_array'],
            'xChild'         => ['attr'=>['type'=>'hidden']],
            'xAction'        => ['attr'=>['type'=>'hidden']],
            // Displayed
            'invoice_num'    => array_merge(['break'=>true], $data['fields']['main']['invoice_num']),
            'post_date'      => array_merge(['break'=>true], $data['fields']['main']['post_date']),
            'purch_order_id' => array_merge(['break'=>true], $data['fields']['main']['purch_order_id']),
            'store_id'       => array_merge(['break'=>true], $data['fields']['main']['store_id']),
            'rep_id'         => array_merge(['break'=>true], $data['fields']['main']['rep_id']),
            'terms_text'     => $data['terms_text'],
            'currency'       => array_merge(['break'=>true], $data['fields']['main']['currency']),
            'closed'         => array_merge(['break'=>true], $data['fields']['main']['closed']),
            'waiting'        => array_merge(['break'=>true], $data['fields']['main']['waiting'])];
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
     * Get re-post records - applies to journals 2, 17, 18, 20, 22
     * @return array - empty
     */
    public function getRepostData()
    {
		msgDebug("\n  j17 - Checking for re-post records ... end check for Re-post with no action.");
        return [];
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
     * applies to journal 2, 3, 9, 17, 18, 20, 22
     * @return boolean true on success, null on error
     */
    private function postInventory()
    {
		msgDebug("\n  Posting Inventory ... end Posting Inventory not requiring any action.");
		return true;
	}

	/**
     * unPost inventory
     * applies to journal 2, 3, 9, 17, 18, 20, 22
     * @return boolean true on success, null on error
     */
	private function unPostInventory()
    {
		msgDebug("\n  unPosting Inventory ... end unPosting Inventory with no action.");
		return true;
	}

	/**
     * Checks and sets/clears the closed status of a journal entry
     * Affects journals - 17, 18, 20, 22
     * @param string $action - [default: 'post']
     * @return boolean true
     */
	private function setStatusClosed($action='post')
    {
		// closed can occur many ways including:
		//   forced closure through so/po form (from so/po journal - adjust qty on so/po)
		//   all quantities are reduced to zero (from so/po journal - should be deleted instead but it's possible)
		//   editing quantities on po/so to match the number received (from po/so journal)
		//   receiving all (or more) po/so items through one or more purchases/sales (from purchase/sales journal)
		msgDebug("\n  Checking for closed entry. action = $action");
        if ($action == 'post') {
            $temp = [];
            for ($i = 0; $i < count($this->item); $i++) { // fetch the list of paid invoices
                if (isset($this->item[$i]['item_ref_id']) && $this->item[$i]['item_ref_id']) {
                    $temp[$this->item[$i]['item_ref_id']] = true;
                }
            }
            $invoices = array_keys($temp);
            for ($i = 0; $i < count($invoices); $i++) {
                $stmt = dbGetResult("SELECT m.id, m.journal_id, SUM(i.debit_amount - i.credit_amount) AS total_amount 
                    FROM ".BIZUNO_DB_PREFIX."journal_main m JOIN ".BIZUNO_DB_PREFIX."journal_item i ON m.id=i.ref_id 
                    WHERE m.id={$invoices[$i]} AND i.gl_type<>'ttl'");
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($result['journal_id']==2) glFindAPacct($result); // special case for payables entered through general journal
                $total_billed = roundAmount($result['total_amount'], $this->rounding);
                $stmt = dbGetResult("SELECT SUM(i.debit_amount - i.credit_amount) AS total_amount 
                    FROM ".BIZUNO_DB_PREFIX."journal_main m JOIN ".BIZUNO_DB_PREFIX."journal_item i ON m.id=i.ref_id 
                    WHERE i.item_ref_id={$invoices[$i]} AND i.gl_type='pmt'");
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                $total_paid = roundAmount($result['total_amount'], $this->rounding);
                msgDebug("\n    total_billed = $total_billed and total_paid = $total_paid");
                $this->setCloseStatus($invoices[$i], $total_billed == $total_paid ? true : false); // either close or re-open
            }
        } else { // unpost - re-open the purchase/invoices affected
            for ($i = 0; $i < count($this->item); $i++) {
                if ($this->item[$i]['item_ref_id']) { $this->setCloseStatus($this->item[$i]['item_ref_id'], false); }
            }
        }
		return true;
	}
}
