<?php
/*
Plugin Name: Snap-O-Meter for WordPress
Plugin URI:  http://www.getsnappy.com/blog/snap-o-meter-wordpress-plug-in/
Description: Add Snap-O-Meter web performance monitoring and reporting to your WordPress site
Author: Brian Gardner
Version: 0.1
Requires at least: 2.8
Author URI: http://www.getsnappy.com
License: GPL
*/

// Determine the location
//function gapp_plugin_path() {
//	return plugins_url('', __FILE__).'/';
//}

/*
 * Admin User Interface
 */

if ( ! class_exists( 'SnapOMeter_Plugin' ) ) {
	class SnapOMeter_Plugin {
		var $hook 		= 'snap-o-meter';
		var $filename	= 'snap-o-meter/snap-o-meter.php';
		var $longname	= 'Snap-O-Meter Configuration';
		var $shortname	= 'Snap-O-Meter';
		var $optionname = 'Snap_O_Meter_Analytics';
		var $homepage	= 'http://getsnappy.com/wordpress/snap-o-meter/';
		var $toc		= '';
      var $accesslvl = 'edit_users';


		function SnapOMeter_Plugin() {
			$this->upgrade();
			
			add_action( 'admin_menu', array(&$this, 'register_settings_page') );
			add_filter( 'plugin_action_links', array(&$this, 'add_action_link'), 10, 2 );
			add_action('admin_footer', array(&$this,'warning'));
			add_action('admin_init', array(&$this,'save_settings'));

         add_action('wp_head', array(&$this,'snap_o_meter_init_analytics'),1);
         add_action('wp_footer', array(&$this,'snap_o_meter_perform_analytics'),99999);
		}

      function plugin_options_url() {
         return admin_url( 'options-general.php?page='.$this->hook );
      }

      function add_action_link( $links, $file ) {
         static $this_plugin;
         if( empty($this_plugin) ) $this_plugin = $this->filename;
         if ( $file == $this_plugin ) {
            $settings_link = '<a href="' . $this->plugin_options_url() . '">' . __('Settings') . '</a>';
            array_unshift( $links, $settings_link );
         }
         return $links;
      }

      function register_settings_page() {
         add_options_page($this->longname, $this->shortname, $this->accesslvl, $this->hook, array(&$this,'config_page'));
      }

      /**
       * Create a Text input field
       */
      function textinput($id) {
         $options = get_option( $this->optionname );
         return '<input class="text" type="text" id="'.$id.'" name="'.$id.'" size="30" value="'.$options[$id].'"/>';
      }


      /**
       * Create a potbox widget
       */
      function postbox($id, $title, $content) {
      ?>
         <div id="<?php echo $id; ?>" class="postbox">
            <div class="handlediv" title="Click to toggle"><br /></div>
            <h3 class="hndle"><span><?php echo $title; ?></span></h3>
            <div class="inside">
               <?php echo $content; ?>
            </div>
         </div>
      <?php
      }


      /**
       * Create a form table from an array of rows
       */
      function form_table($rows) {
         $content = '<table class="form-table">';
         $i = 1;
         foreach ($rows as $row) {
            $class = '';
            if ($i > 1) {
               $class .= 'yst_row';
            }
            if ($i % 2 == 0) {
               $class .= ' even';
            }
            $content .= '<tr id="'.$row['id'].'_row" class="'.$class.'"><th valign="top" scrope="row">';
            if (isset($row['id']) && $row['id'] != '')
               $content .= '<label for="'.$row['id'].'">'.$row['label'].':</label>';
            else
               $content .= $row['label'];
            $content .= '</th><td valign="top">';
            $content .= $row['content'];
            $content .= '</td></tr>';
            if ( isset($row['desc']) && !empty($row['desc']) ) {
               $content .= '<tr class="'.$class.'"><td colspan="2" class="yst_desc"><small>'.$row['desc'].'</small></td></tr>';
            }

            $i++;
         }
         $content .= '</table>';
         return $content;
      }


		
		function config_page_head() {
		}
				
		function toc( $modules ) {
			$output = '<ul>';
			foreach ($modules as $module => $key) {
				$output .= '<li class="'.$key.'"><a href="#'.$key.'">'.$module.'</a></li>';
			}
			$output .= '</ul>';
			return $output;
		}
		
		function save_settings() {
			$options = get_option( $this->optionname );
			
			if ( isset($_REQUEST['reset']) && $_REQUEST['reset'] == "true" && isset($_REQUEST['plugin']) && $_REQUEST['plugin'] == 'snap-o-meter') {
				$options = $this->set_defaults();
				$options['msg'] = "<div class=\"updated\"><p>Snap-O-Meter settings reset.</p></div>\n";
			} elseif ( isset($_POST['submit']) && isset($_POST['plugin']) && $_POST['plugin'] == 'snap-o-meter') {
				if (!current_user_can('manage_options')) die(__('You cannot edit the Snap-O-Meter for WordPress options.'));
				
				foreach (array('snap_o_meter_site_identity') as $option_name) {
					if (isset($_POST[$option_name]))
						$options[$option_name] = $_POST[$option_name];
					else
						$options[$option_name] = '';
				}
				
				$cache = '';
				if ( function_exists('w3tc_pgcache_flush') ) {
					w3tc_pgcache_flush();
					$cache = ' and <strong>W3TC Page Cache cleared</strong>';
				} else if ( function_exists('wp_cache_clear_cache') ) {
					wp_cache_clear_cache();
					$cache = ' and <strong>WP Super Cache cleared</strong>';
				}
										
				$options['msg'] = "<div id=\"updatemessage\" class=\"updated fade\"><p>Snap-O-Meter <strong>settings updated</strong>$cache.</p></div>\n";
				$options['msg'] .= "<script type=\"text/javascript\">setTimeout(function(){jQuery('#updatemessage').hide('slow');}, 3000);</script>";
			}
			update_option($this->optionname, $options);
		}
		
		function save_button() {
			return '<div class="alignright"><input type="submit" class="button-primary" name="submit" value="Update Snap-O-Meter Settings &raquo;" /></div><br class="clear"/>';
		}
		
		function upgrade() {
			$options = get_option($this->optionname);
         # don't do anything quite yet
			update_option($this->optionname, $options);
		}

		function config_page() {
			$options = get_option($this->optionname);
			echo $options['msg'];
			$options['msg'] = '';
			update_option($this->optionname, $options);
			$modules = array();
			
			?>
			<div class="wrap">
				<a href="http://getsnappy.com/"><div id="getsnappy-icon" style="background: url(<?php echo plugin_dir_url(__FILE__) ?>images/ga-icon-32x32.png) no-repeat;" class="icon32"><br /></div></a>
				<h2>Snap-O-Meter for WordPress Configuration</h2>
				<div class="postbox-container" style="width:65%;">
					<div class="metabox-holder">	
						<div class="meta-box-sortables">
							<form action="<?php echo $this->plugin_options_url(); ?>" method="post" id="analytics-conf">
								<input type="hidden" name="plugin" value="snap-o-meter"/>
								<?php
									$rows[] = array(
										'id' => 'snap_o_meter_site_identity',
										'label' => 'Snap-O-Meter Identity',
										'desc' => 'Enter your Snap-O-Meter Identity after registering at <a href="http://www.getsnappy.com/sign-up.html">http://www.getsnappy.com/sign-up.html</a>',
										'content' => $this->textinput('snap_o_meter_site_identity')
									);
                           $this->postbox('customgetsnappysettings','Snap-O-Meter Settings',$pre_content.$this->form_table($rows).$this->save_button());

									?>
					</form>
					<form action="<?php echo $this->plugin_options_url(); ?>" method="post" onsubmit="javascript:return(confirm('Do you really want to reset all settings?'));">
						<input type="hidden" name="reset" value="true"/>
						<input type="hidden" name="plugin" value="snap-o-meter"/>
						<div class="submit"><input type="submit" value="Reset All Settings &raquo;" /></div>
					</form>
				</div>
			</div>
		</div>
	</div>
			<?php
		} 
		
		function set_defaults() {
			$options = array(
				'snap_o_meter_site_identity' 		=> '',
				'ignore_userlevel' 		=> '11',
			);
			update_option($this->optionname,$options);
			return $options;
		}
		
		function warning() {
			$options = get_option($this->optionname);
			if (!isset($options['snap_o_meter_site_identity']) || empty($options['snap_o_meter_site_identity'])) {
				echo "<div id='message' class='error'><p><strong>Snap-O-Meter is not active.</strong> You must <a href='".$this->plugin_options_url()."'>provide your Snap-O-Meter Identity to track</a> before it will work.</p></div>";
			}
		} // end warning()



      function snap_o_meter_do_tracking() {
			$options = get_option($this->optionname);
	      if ( !$options["snap_o_meter_site_identity"] )
		      return false;

	      $current_user = wp_get_current_user();
	

	      if (!$current_user)
		      return true;
	
	
#	      if ( ($current_user->user_level >= $options["ignore_userlevel"]) )
#		      return false;
#	      else
	      return true;
      }


      function snap_o_meter_plugin_dir_uri($file) {
         $url = plugin_dir_url($file);
         return parse_url($url, PHP_URL_PATH);
      }

      function snap_o_meter_init_analytics() {
         if (!$this->snap_o_meter_do_tracking()) 
            return;
?>
   <script type="text/javascript">var ts=new Date().getTime();</script>
<?php
}
      function snap_o_meter_perform_analytics() {
         if (!$this->snap_o_meter_do_tracking()) 
            return;

	      $options = get_option($this->optionname);
?>

   <script type="text/javascript" src="<?php echo $this->snap_o_meter_plugin_dir_uri(__FILE__); ?>scripts/gsn-v1_2.js"></script>
   <script type="text/javascript">
      GSN.setIdentity(decodeURIComponent('<?php echo rawurlencode($options["snap_o_meter_site_identity"]); ?>')); 
      GSN.setServerName(decodeURIComponent('<?php echo rawurlencode(php_uname('n')); ?>'));
      GSN.setImageUri(decodeURIComponent('<?php echo $this->snap_o_meter_plugin_dir_uri(__FILE__); ?>images/GetSnappyNow.gif'));
      GSN.track(ts); 
   </script>

<?php
      }
	} // end class SnapOMeter_Plugin

	$getsnappy_admin = new SnapOMeter_Plugin();
} //endif

?>
