<?php
namespace core\classes;
    /**
     * This is our page object
     * It is a seperate object to allow some interesting extra functionality to be added
     * Some ideas: passwording pages, adding page specific css/js files, etc
     */

class outputPage  {

	// header elements
    private $css_files				= array();
    private $css;
    private $js_files				= array();
    private $include_php_js_files 	= array();
    private $js;
    private $js_override_files		= array();
    private $js_override;
    private $menu_send				= false;
    // page elements
    public  $title 					= '';
    public  $custom_html			= false;
    public  $include_footer			= true;
    public  $include_template		= '';
    private $ModuleAndPage			= "phreedom/main";
    public  $page_title				= TEXT_PHREEBOOKS_ERP;

    /**
     * Constructor...
     */
    function __construct() {
    	\core\classes\messageStack::debug_log("executing ".__METHOD__ );
       	$this->include_template = DIR_FS_ADMIN .'modules/phreedom/pages/main/template_main.php';
       	$this->js_files[] = "includes/jquery.dataTables.min.js";// @todo only nessecery
    }
    
    function send_header (\core\classes\basis $basis){
    	//header_remove();
    	if (!defined('CHARSET')) define('CHARSET', 'UTF-8');
    	header("Content-type: text/html; charset=".CHARSET);
    	if ($force_reset_cache) { header("Cache-Control: no-cache, must-revalidate"); header("Expires: ".date('D, j M \2\0\0\0 G:i:s T')); }
    	echo "<!DOCTYPE html>";
    	echo "<html ".HTML_PARAMS.">";
    	echo "<head>";
    	echo "<meta http-equiv='Content-Type' content='text/html; charset=".CHARSET."' />";
    	echo "<title>".TEXT_PHREEBOOKS_ERP."</title>";
    	echo "<link rel='stylesheet' type='text/css' href='".DIR_WS_THEMES.'css/'.MY_COLORS.'/stylesheet.css'."' />". chr(13);
    	echo "<link rel='stylesheet' type='text/css' href='".DIR_WS_THEMES.'css/'.MY_COLORS.'/easyui.css'."' />". chr(13);
    	echo "<link rel='stylesheet' type='text/css' href='".DIR_WS_THEMES.'css/icon.css'."' />". chr(13);
    	echo "<script type='text/javascript' src='includes/common.js'></script>". chr(13);
    	echo "<script type='text/javascript' src='includes/easyui/jquery.min.js'></script>". chr(13);
    	echo "<script type='text/javascript' src='includes/easyui/easyloader.js'></script>". chr(13);
    	echo "<script type='text/javascript' src='includes/easyui/jquery.easyui.min.js'></script>". chr(13);
//    	echo "<script type='text/javascript' src='https://www.google.com/jsapi'></script>". chr(13);
		echo "</head><body class='easyui-layout'>";
    	ob_flush();
    }
    
    function send_constants (\core\classes\basis $basis){?>
    	<script type="text/javascript">
    	//setting easyui defaults.
	    	$.fn.validatebox.defaults.invalidMessage = '<?php echo TEXT_INVALID_VALUE?>';
			$.fn.validatebox.defaults.missingMessage = '<?php echo TEXT_THIS_FIELD_IS_REQUIRED?>';
			$.fn.datebox.defaults.formatter = function(date){
				var Y = date.getFullYear();
				var m = date.getMonth()+1;
				var d = date.getDate();
				return <?php echo str_replace(DATE_DELIMITER ,'+"'.DATE_DELIMITER.'"+',DATE_FORMAT)?>;
			}
			$.fn.datebox.defaults.parser = function(s) {
				if (s) {
					var a = s.split('<?php echo DATE_DELIMITER?>');
					var d = new Number(a[<?php echo array_search('d',explode(DATE_DELIMITER,DATE_FORMAT))?>]);
					var m = new Number(a[<?php echo array_search('m',explode(DATE_DELIMITER,DATE_FORMAT))?>]);
					var y = new Number(a[<?php echo array_search('Y',explode(DATE_DELIMITER,DATE_FORMAT))?>]);
					var dd = new Date(y, m-1, d);
					return dd;
				} else {
					return new Date();
				}
			};
	    	var date_format         = '<?php echo DATE_FORMAT; ?>';
	    	var date_delimiter      = '<?php echo DATE_DELIMITER; ?>';
	    	var inactive_text_color = '#cccccc';
	    	var decimal_places      = <?php  echo $basis->currencies->currencies[DEFAULT_CURRENCY]['decimal_places']; ?>;
	    	var decimal_precise     = <?php  echo $basis->currencies->currencies[DEFAULT_CURRENCY]['decimal_precise']; ?>;
    	  	var decimal_point       = "<?php echo $basis->currencies->currencies[DEFAULT_CURRENCY]['decimal_point']; ?>"; // leave " for ' separator
    	  	var thousands_point     = "<?php echo $basis->currencies->currencies[DEFAULT_CURRENCY]['thousands_point']; ?>";
    	  	var symbol_left			= "<?php echo $basis->currencies->currencies[DEFAULT_CURRENCY]['symbol_left']; ?>";
    	  	var symbol_right		= "<?php echo $basis->currencies->currencies[DEFAULT_CURRENCY]['symbol_right']; ?>";
    	  	var formatted_zero      = "<?php echo $basis->currencies->format(0); ?>";
    	  	var icon_path			= '<?php echo DIR_WS_ICONS;?>';
    	</script> <?php 
    }
    
    function send_menu (\core\classes\basis $basis){
    	if ($this->menu_send) return;
    	uasort($basis->mainmenu, array($this,'sortByOrder'));
    	echo '<!-- Pull Down Menu -->' . chr(10);
    	echo '<div id="headermenu" class="easyui-linkbutton" data-options="region:\'north\'" style="margin-bottom:5px">'; 
    	foreach($basis->mainmenu as $key => $menu_item)	$menu_item->output($key, true);
    	echo '</div>';
    	ob_flush();
    	$this->menu_send = true;
    }

	function sortByOrder($a, $b) {
		if (is_integer($a->order) && is_integer($b->order)) return $a->order - $b->order;
		return strcmp($a->order, $b->order);
	}
     
    public function print_js_includes (\core\classes\basis $basis){
    	\core\classes\messageStack::debug_log("executing ".__METHOD__ );
       	//first normal js files
       	foreach($this->js_files as $file){
       		if($file) echo "<script type='text/javascript' src='$file'></script>". chr(13);
       	}
    	foreach($basis->js_files as $file){
       		if($file) echo "<script type='text/javascript' src='$file'></script>". chr(13);
       	}
       	foreach($this->include_php_js_files as $file){
       		if($file) include_once ($file);
       	}
       	foreach($basis->include_php_js_files as $file){
       		if($file) include_once ($file);
       	}
       	//then the override files
       	foreach($basis->js_override_files as $file){
       		if($file) echo "<script type='text/javascript' src='$file'></script>". chr(13);
       	}
       	if (SESSION_AUTO_REFRESH == '1'){
       		echo '  <script type="text/javascript">addLoadEvent(refreshSessionClock);</script>' . chr(10);
       		echo '<script type="text/javascript">addLoadEvent(init);addUnloadEvent(clearSessionClock);</script>'. chr(13);
       	}
       	if($this->js)  echo "  <script type='text/javascrip'>$this->js</script>" . chr(10);
       	if($basis->js) echo "  <script type='text/javascrip'>$basis->js</script>" . chr(10);
    }

    public function print_menu (\core\classes\basis $basis){
    	\core\classes\messageStack::debug_log("executing ".__METHOD__ );
       	if($basis->include_header){
       		require_once(DIR_FS_ADMIN . DIR_WS_THEMES . '/menu.php');
       	} else{
       		echo "<div>\n";
       	}
    }

	public function send_footer (\core\classes\basis $basis){
    	\core\classes\messageStack::debug_log("executing ".__METHOD__ );
       	$image_path = defined('FOOTER_LOGO') ? FOOTER_LOGO : (DIR_WS_ADMIN . 'modules/phreedom/images/phreesoft_logo.png');
       	echo '<div id="page_footer" data-options="region:\'south\'" style="text-align:center;overflow:hidden;font-size:9px;height:95px">';
       	echo "<a href='http://www.PhreeSoft.com' target='_blank'>". html_image($image_path, TEXT_PHREEDOM_INFO, NULL, '64') ."</a><br />";
       	echo COMPANY_NAME.' | '.TEXT_ACCOUNTING_PERIOD.': '.CURRENT_ACCOUNTING_PERIOD.' | '.TEXT_PHREEDOM_INFO." ({$basis->classes['phreedom']->version}) ";
       	if ($basis->module <> 'phreedom') echo "({$basis->module} {$basis->classes[$basis->module]->version}) ";
       	echo '<br />' . TEXT_COPYRIGHT .  ' &copy;' . date('Y') . ' <a href="http://www.PhreeSoft.com" target="_blank">PhreeSoft&trade;</a>';
       	echo '(' . (int)(1000 * (microtime(true) - PAGE_EXECUTION_START_TIME)) . ' ms) ' . $basis->DataBase->count_queries . ' SQLs (' . (int)($basis->DataBase->total_query_time * 1000).' ms)</div>';
       	ob_flush();
    }
    /**
     * this method is called by the basis object when it is done with all actions.
     */

    public function update (\core\classes\basis $basis) {
    	\core\classes\messageStack::debug_log("executing ".__METHOD__ );
    }

    /**
     * returns the current template
     * @return string
     */
    function get_template(){
    	if (empty($this->include_template) || $this->include_template == '' ){
    		return DIR_FS_ADMIN .'modules/phreedom/pages/main/template_main.php';
    	}
		return $this->include_template;
    }

    /**
     * this is called when class loses focus
     * it will store common variales in session data
     */

    function __destruct(){
    	\core\classes\messageStack::debug_log("executing ".__METHOD__ );
    	$_SESSION[$this->ModuleAndPage]['sf']    = $_REQUEST['sf'];
    	$_SESSION[$this->ModuleAndPage]['so']    = $_REQUEST['so'];
    	$_SESSION[$this->ModuleAndPage]['list']  = $_REQUEST['list'];
    	$_SESSION[$this->ModuleAndPage]['search']= $_REQUEST['search_text'];
    	$_SESSION[$this->ModuleAndPage]['period']= $_REQUEST['search_period'];
    	$_SESSION[$this->ModuleAndPage]['date']  = $_REQUEST['search_date'];
    }
}

?>
