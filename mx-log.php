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
  `admin_mode` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }

    /*
     * Functions which is fired when deactivating the plugin
     */
    function plugin_deactivate() {
        global $wpdb;
        $wpdb->query("DROP TABLE {$wpdb->prefix}mxlog");
    }

    /*
     * Main function for logging request data
     */
    function saveRequest() {
        global $wpdb;
        $user_id=get_current_user_id();

        $wpdb->insert($wpdb->prefix.'mxlog',array(
            'timestamp'=>current_time('mysql'),
            'uri'=>preg_replace('|\?.*?$|','',$_SERVER['REQUEST_URI']),
            'request_get'=>count($_GET)?json_encode($_GET):Null,
            'request_post'=>count($_POST)?json_encode($_POST):Null,
            'user_id'=>get_current_user_id(),
            'admin_mode'=>is_admin()
        ));
        //echo "<pre>";var_dump($_REQUEST);echo "</pre>";
    }
}

$mxlog=new mxlog();
add_action('init',array($mxlog,'saveRequest'));
register_activation_hook( __FILE__, array($mxlog,'plugin_activate'));
register_deactivation_hook( __FILE__, array($mxlog,'plugin_deactivate'));