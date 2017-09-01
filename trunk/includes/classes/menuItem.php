<?php
// +-----------------------------------------------------------------+
// |                   PhreeBooks Open Source ERP                    |
// +-----------------------------------------------------------------+
// | Copyright(c) 2008-2015 PhreeSoft      (www.PhreeSoft.com)       |
// +-----------------------------------------------------------------+
// | This program is free software: you can redistribute it and/or   |
// | modify it under the terms of the GNU General Public License as  |
// | published by the Free Software Foundation, either version 3 of  |
// | the License, or any later version.                              |
// |                                                                 |
// | This program is distributed in the hope that it will be useful, |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of  |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the   |
// | GNU General Public License for more details.                    |
// +-----------------------------------------------------------------+
//  Path: /includes/classes/menuItem.php
//
namespace core\classes;
class menuItem {
	public $order;
	public $text;
	public $security_id = 0;
	public $link;
	public $show_in_users_settings = true;
	public $params;
	public $submenu;
	public $required_module;
	public $icon;
	
	public function __construct($order, $text, $action = NULL , $security_id = NULL, $constant = NULL){
		$this->order = $order;
		$this->text  = $text;
		$this->security_id = $security_id;
		$this->link = $action;
		$this->required_module = $constant;
	}
	
	function output($key, $first_row = false){
		if ($this->show() == false) return ;
		if (is_array($this->submenu)) {
			uasort ($this->submenu, array($this,'sortByOrder'));
			if($first_row){
				echo "  <a class='easyui-menubutton' data-options=\"menu:'#{$key}'\" href='".html_href_link(FILENAME_DEFAULT, $this->link, 'SSL')."' {$this->params}> $this->icon $this->text</a>";
				echo "<div id='$key'>";
				foreach($this->submenu as $subkey => $menu_item) $menu_item->output($key.$subkey);
				echo '</div>';
			}else{
				echo "<div id='$key' href='".html_href_link(FILENAME_DEFAULT, $this->link, 'SSL')."' {$this->params}> <span>$this->icon $this->text</span><div>";
				foreach($this->submenu as $subkey => $menu_item) $menu_item->output($key.$subkey);
				echo '</div></div>';
			}
		}else{
			if($first_row){
				echo "  <a class='easyui-linkbutton' href='".html_href_link(FILENAME_DEFAULT, $this->link, 'SSL')."' {$this->params}>";
				if ($this->text == TEXT_HOME && ENABLE_ENCRYPTION && strlen($_SESSION['ENCRYPTION_VALUE']) > 0) echo html_icon('emblems/emblem-readonly.png', TEXT_ENCRYPTION_KEY_IS_SET, 'small');
				echo "$this->icon $this->text</a>".chr(10);
			}else{
				echo "  <div href='".html_href_link(FILENAME_DEFAULT, $this->link, 'SSL')."' {$this->params}>";
				if ($this->text == TEXT_HOME && ENABLE_ENCRYPTION && strlen($_SESSION['ENCRYPTION_VALUE']) > 0) echo html_icon('emblems/emblem-readonly.png', TEXT_ENCRYPTION_KEY_IS_SET, 'small');
				echo "$this->icon $this->text</div>".chr(10);
			}
		}
	}
	
	function sortByOrder($a, $b) {
		if (is_integer($a->order) && is_integer($b->order)) return $a->order - $b->order;
		return strcmp($a->order, $b->order);
	}
	
	function show(){
		if ($this->required_module != ''){
			if (is_array($this->required_module)) {
				$temp = false;
				foreach ($this->required_module as $key) if (defined($key)) $temp = true;
				if ($temp == false ) return false;
			} else{
				if(!defined($this->required_module)) return false;
			}
		}
		if (\core\classes\user::security_level($this->security_id) == 0 ) return false;
		return true;
	}
	
}

?>