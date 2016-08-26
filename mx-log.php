<?
/*
Plugin Name: MX Log
Description: MX Log
Version: 0.1
Author: MX Studio
Author URI: http://mxsite.ru
Plugin URI: http://mxsite.ru
*/

class mxlog {

    var $defaultSettings=array( // Default settings applied when activating plugin
        'mxlog_scope'=>0,
        'mxlog_authorized_only'=>0,
    );

    function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize wp hooks
     */
    function init_hooks() {
        register_activation_hook( __FILE__, array($this,'plugin_activate'));
        register_deactivation_hook( __FILE__, array($this,'plugin_deactivate'));
        add_action('admin_enqueue_scripts',array($this,'admin_enqueue_scripts'));
        add_action('init',array($this,'saveRequest'));
        add_action('admin_menu',array($this,'admin_menu'));
    }

    /*
     * Add scripts and styles for administration panel
     */
    function admin_enqueue_scripts() {
        wp_enqueue_style('admin_mxlog',plugins_url('asset/css/admin.min.css',__FILE__));
    }

    /*
     * Functions which is fired when activating the plugin
     */
    function plugin_activate() {
        global $wpdb;
        $wpdb->query("CREATE TABLE IF NOT EXISTS `wp_mxlog` (
  `id` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  `uri` varchar(255) NOT NULL,
  `request_get` text,
  `request_post` text,
  `user_id` int(11) DEFAULT NULL,
  `is_admin_section` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        $this->saveDefaultSettings();
    }

    /*
     * Save default settings for plugin
     */
    function saveDefaultSettings() {
        foreach($this->defaultSettings as $key=>$value) {
            update_option($key,$value);
        }
    }

    /*
     * Remove default settings for plugin
     */
    function removeDefaultSettings() {
        foreach($this->defaultSettings as $key=>$value) {
            delete_option($key);
        }
    }

    /*
     * Functions which is fired when deactivating the plugin
     */
    function plugin_deactivate() {
        global $wpdb;
        $wpdb->query("DROP TABLE {$wpdb->prefix}mxlog");
        $this->removeDefaultSettings();
    }

    /*
     * Main function for logging request data
     */
    function saveRequest() {
        global $wpdb;
        $is_admin=is_admin();
        $scope=get_option('mxlog_scope');
        $authorized_only=get_option('mxlog_authorized_only');
        $user_id=get_current_user_id();
        if (($is_admin && $scope==2) || (!$is_admin && $scope==1) || ($authorized_only && $user_id==0)) {return;} // Don't log when out of scope, defined in the Settings of plugin
        $wpdb->insert($wpdb->prefix.'mxlog',array(
            'timestamp'=>current_time('mysql'),
            'uri'=>preg_replace('|\?.*?$|','',$_SERVER['REQUEST_URI']),
            'request_get'=>count($_GET)?json_encode($_GET):Null,
            'request_post'=>count($_POST)?json_encode($_POST):Null,
            'user_id'=>$user_id,
            'is_admin_section'=>$is_admin
        ));
    }


    /**
     * Add plugin settings links to Settings menu
     */
    function admin_menu() {
        add_options_page('MX Log','MX Log', 8, basename(__FILE__),array ($this, 'settings_form') );
    }

    /**
     * Display form with plugin settings
     */
    function settings_form() {
        if (isset($_POST['Settings'])) {
            foreach($_POST['Settings'] as $key=>$value) {
                update_option($key,$value);
            }
        }
        $settingsA=array(
            'mxlog_scope'=>'',
            'mxlog_authorized_only'=>''
        );
        foreach($settingsA as $key=>$value) {
            $settingsA[$key]=get_option($key);
        }
        ?>
        <div class='wrap'>
            <h2>MX Log Settings</h2>

            <form action="" method="post" class="mxlog_settings">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Logging scope:</th>
                        <td>
                            <?
                            $scopeA=array(
                                0=>'Backend and frontend',
                                1=>'Backend only',
                                2=>'Frontend only',
                            );
                            foreach($scopeA as $index=>$value) {
                                echo '<p><input type="radio" id="mxlog_scope'.$index.'" name="Settings[mxlog_scope]" value="'.$index.'"'.($settingsA['mxlog_scope']==$index?' checked':'').'> <label for="mxlog_scope'.$index.'">'.$value.'</label></p>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Log actions of authorized users only:</th>
                        <td>
                            <input type="hidden" name="Settings[mxlog_authorized_only]" value="0">
                            <input value="1" type="checkbox" name="Settings[mxlog_authorized_only]" <?=$settingsA['mxlog_authorized_only']==1?'checked':''?>/>
                        </td>
                    </tr>
                </table>
                <button type="submit" class="button button-primary">Save</button>
            </form>
        </div>
        <?
    }
}

$mxlog=new mxlog();