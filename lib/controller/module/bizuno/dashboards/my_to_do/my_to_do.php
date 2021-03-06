<?php
/*
 * Bizuno dashboard - My ToDo
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
 * @version    2.x Last Update: 2017-06-01
 * @filesource /lib/controller/module/bizuno/dashboards/my_to_do/my_to_do.php
 * 
 */

namespace bizuno;

define('DASHBOARD_MY_TO_DO_VERSION','1.0');

class my_to_do
{
    public $moduleID = 'bizuno';
    public $methodDir= 'dashboards';
    public $code     = 'my_to_do';
    public $category = 'general';
	
	function __construct($settings)
    {
		$this->security= 4;
        $this->lang    = getMethLang($this->moduleID, $this->methodDir, $this->code);
        $defaults      = ['users'=>'-1','roles'=>'-1'];
        $this->settings= array_replace_recursive($defaults, $settings);
	}

    public function settingsStructure()
    {
        return [
            'users' => ['label'=>lang('users'), 'position'=>'after','values'=>listUsers(),'attr'=>['type'=>'select','value'=>$this->settings['users'],'size'=>10, 'multiple'=>'multiple']],
            'roles' => ['label'=>lang('groups'),'position'=>'after','values'=>listRoles(),'attr'=>['type'=>'select','value'=>$this->settings['roles'],'size'=>10, 'multiple'=>'multiple']]];
	}

    public function render()
    {
        $data = [
            $this->code.'_0' => ['label' => lang('note'),
                'classes'=> ['easyui-validatebox'],
                'attr'   => ['type'=>'text', 'required'=>'true', 'size'=>50]],
            $this->code.'_button' => [
                'attr'   => ['type'   => 'button', 'value' => lang('new')],
                'styles' => ['cursor' => 'pointer'],
                'events' => ['onClick'=> "dashboardAttr('$this->moduleID:$this->code', 0);"]],
            'delete_icon' => ['icon'=>'trash', 'size'=>'small']];
        $html  = '<div>';
        $html .= '  <div id="'.$this->code.'_attr" style="display:none">';
        $html .= '    <form id="'.$this->code.'Form" action="">';
        $html .= '      <div style="white-space:nowrap">'.html5($this->code.'_0',      $data[$this->code.'_0']).'</div>';
        $html .= '      <div style="text-align:right;">' .html5($this->code.'_button', $data[$this->code.'_button']).'</div>';
        $html .= '    </form>';
        $html .= '  </div>';
        // Build content box
        $index = 1;
        if (!isset($this->settings['data'])) { unset($this->settings['users']); unset($this->settings['roles']); $this->settings=['data'=>$this->settings]; } // OLD WAY
        if (!empty($this->settings['data'])) {
            foreach ($this->settings['data'] as $entry) {
                $data['delete_icon']['events'] = ['onClick'=>"if (confirm('".jsLang('msg_confirm_delete')."')) dashboardAttr('$this->moduleID:$this->code', $index);"];
                $html .= '  <div>';
                $html .= '    <div style="float:right;height:16px;">'.html5('delete_icon', $data['delete_icon']).'</div>';
                $html .= '    <div style="min-height:16px;">&#9679; '.$entry.'</div>';
                $html .= '  </div>';
                $index++;
            }
        } else {
            $html .= '  <div>'.lang('no_results').'</div>'."\n";
        }
        $html .= '</div><div style="min-height:4px;">&nbsp;</div>'."\n";
        return $html;
    }

    public function save()
    {
        $menu_id  = clean('menuID', 'cmd', 'get');
        $rmID     = clean('rID', 'integer', 'get');
        $add_entry= clean($this->code.'_0', 'text', 'post');
        if (!$rmID && $add_entry == '') { return; } // do nothing if no title or url entered
        // fetch the current settings
        $result = dbGetRow(BIZUNO_DB_PREFIX."users_profiles", "user_id=".getUserCache('profile', 'admin_id', false, 0)." AND menu_id='$menu_id' AND dashboard_id='$this->code'");
        $settings = json_decode($result['settings'], true);
        if (!isset($settings['data'])) { unset($settings['users']); unset($settings['roles']); $settings=['data'=>$settings]; } // OLD WAY
        if ($rmID) { array_splice($settings['data'], $rmID - 1, 1); }
        else       { $settings['data'][] = $add_entry; }
        dbWrite(BIZUNO_DB_PREFIX."users_profiles", ['settings'=>json_encode($settings)], 'update', "user_id=".getUserCache('profile', 'admin_id', false, 0)." AND dashboard_id='$this->code' AND menu_id='$menu_id'");
        return $result['id'];
    }
}
