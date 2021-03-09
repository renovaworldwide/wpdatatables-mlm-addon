<?php
/**
 * @package wpDataTables MLM Addon
 * @version 1.0
 */
/*
  Plugin Name: wpDataTables MLM Addon
  Plugin URI: https://renovaworldwide.com/
  Description: Add MLM Period Filter to datatable
  Version: 1.0
  Author: Randy Moller
  Author URI: https://renovaworldwide.com/
  Text Domain: wpdatatables-mlm-addon
  Domain Path: /languages
 */


if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WPDataTable_Customization')) {

    class WPDataTable_Customization {

        public function __construct() {

            $this->mlm_wpdt_init_constants();

            add_action('wp_enqueue_scripts', array($this, 'mlm_wpdt_scripts'));
            add_action('admin_enqueue_scripts', array($this, 'mlm_wpdt_scripts'));
            add_action('wp_enqueue_scripts', array($this, 'wpdatatables_config_scripts'));

            add_action('wpdatatables_before_table', array($this, 'monthly_period_filter'), 10, 1);
            add_action('wpdatatables_before_table', array($this, 'weekly_period_filter'), 10, 1);

            add_action('wp_ajax_filter_commission_period_results', array($this, 'filter_commission_period_results'));
            add_action('wp_ajax_nopriv_filter_commission_period_results', array($this, 'filter_commission_period_results'));

            add_filter('wpdatatables_filter_query_before_limit', array($this, 'resolve_commission_period_placeholder'), 10, 2);

            //Add additional backend Setting to wpdatatables
            add_action('wdt_add_sorting_and_filtering_element', array($this, 'commision_period_filter_display'));

            add_action('wdt_add_table_configuration_tabpanel', array($this, 'display_commission_period_namespace'), 10);
            
            add_filter('wpdatatables_filter_insert_table_array', array($this, 'modify_wpdt_display_options'), 10, 1);
            add_filter('wpdatatables_curl_get_data',array($this,'set_curl_cookie_option'),10,3);
            
            add_action('admin_head',array($this,'show_placeholder_tags'));
        }

        public function mlm_wpdt_init_constants() {

            $this->define('MLM_WPDT_PLUGIN_PATH', plugin_dir_path(__FILE__));
            $this->define('MLM_WPDT_PLUGIN_URL', plugin_dir_url(__FILE__));
        }

        private function define($name, $value) {
            if (!defined($name)) {
                define($name, $value);
            }
        }

        public function mlm_wpdt_scripts() {

            //Adding csa frontend script       
            wp_register_script('mlm-wpdt-script', MLM_WPDT_PLUGIN_URL . 'js/wpdatatables_mlm.js', array('jquery'), '4.9.8', true);
            wp_enqueue_script('mlm-wpdt-script');
            wp_localize_script('mlm-wpdt-script', 'mlm_wpdt', array(
                'ajax_url' => admin_url('admin-ajax.php')
            ));
        }

        public function wpdatatables_config_scripts() {
            //Adding csa frontend script       
            wp_register_script('wpdt-config-script', WDT_JS_PATH . 'wpdatatables/admin/table-settings/table_config_object.js', array('jquery'), '4.9.7', true);
            wp_enqueue_script('wpdt-config-script');
            wp_localize_script('wpdt-config-script', 'wpdt_config', array(
                'ajax_url' => admin_url('admin-ajax.php')
            ));
        }

        public function monthly_period_filter($table_id) {
            
            $mothly_settings = $this->get_advanced_settings($table_id);
            $current_date = date('F Y');
//            var_dump($mothly_settings);
            if ($mothly_settings == 1) {
                $commision_periods = $this->get_commision_periods();
                ?>
                <style type="text/css">
                    @media (max-width: 750px){
                        .commision_period_filter {
                            position: relative !important;
                            top: auto !important;
                            left: auto !important;
                            margin-bottom: 20px;
                            z-index: 99999;
                        }
                    }
                    .wpdt-c{
                        position: relative;
                    }

                </style>
                <div class="commision_period_filter" style="position: absolute; left: 21%; top: 55px;z-index: 9999">
                    <!-- <center> -->
                    <label for="filter_by_commission_period">Monthly:</label>
                    <select class="btn-default btn-group period_filter" id="filter_by_commission_period" data-dt-id="<?php echo $table_id; ?>" data-period="monthly">
                        <?php
                        foreach ($commision_periods as $commision_data) {
                            $commision_month = date('F', strtotime($commision_data['start_date']));
                            $commision_year = date('Y', strtotime($commision_data['start_date']));
                            $commision_start_dt = date('d', strtotime($commision_data['start_date']));
                            $commision_end_dt = date('d', strtotime($commision_data['end_date']));

                            $commision_date = $commision_month . " " . $commision_year;
                            $current = "";

                            if ($commision_date == $current_date) {
                                $current = "(Current)";
                            }
                            ?>  
                            <option value="<?php echo $commision_data['commission_period_id']; ?>" > 
                                <?php echo $commision_month . '&nbsp;' . $commision_start_dt . "-" . $commision_end_dt . ", " . $commision_year . '&nbsp;' . $current; ?>
                            </option>
                            <?php
                        }
                        ?>
                    </select>
                    <!-- </center> -->
                </div> <?php
            }
        }
        
        public function weekly_period_filter($table_id) {
            
            $weekly_settings = $this->get_advanced_settings($table_id,'weekly');
            $current_date = date('W',time());
            
             if ($weekly_settings == 1) {
                $commision_periods = $this->get_commision_periods('weekly');
                ?>
                <style type="text/css">
                    @media (max-width: 750px){
                        .weekly_period_filter {
                            position: relative !important;
                            top: auto !important;
                            left: auto !important;
                            margin-bottom: 20px;
                            z-index: 99999;
                        }

                    }
                    .wpdt-c{
                        position: relative;
                    }
                </style>
                <div class="weekly_period_filter" style="position: absolute; left: 48%; top: 55px;z-index: 9999">
                    <!-- <center> -->
                    <label for="filter_by_weekly_period">Weekly:</label>
                    <select class="btn-default btn-group period_filter" id="filter_by_weekly_period" data-dt-id="<?php echo $table_id; ?>" data-period="weekly">
                        <?php
                        foreach ($commision_periods as $commision_data) {

                            $commision_month = date('F', strtotime($commision_data['start_date']));
                            $commision_year = date('Y', strtotime($commision_data['start_date']));
                            $commision_start_dt = date('d', strtotime($commision_data['start_date']));
                            $commision_end_dt = date('d', strtotime($commision_data['end_date']));

                            $commision_date = $commision_month . " " . $commision_year;
                            $current = "";

                            if ($current_date == date('W', strtotime($commision_data['end_date']))) {
                                $current = "(Current)";
                            }
                            ?>  
                            <option value="<?php echo $commision_data['commission_period_id']; ?>" > 
                                <?php echo $commision_month . '&nbsp;' . $commision_start_dt . "-" . $commision_end_dt . ", " . $commision_year . '&nbsp;' . $current; ?>
                            </option>
                            <?php
                        }
                        ?>
                    </select>
                    <!-- </center> -->
                </div> <?php
            }
        }

        //Get commission periods
        public function get_commision_periods($interval='monthly') {
            global $wpdb;
            
            if($interval == 'monthly'){
                $query = "SELECT commission_period_id, start_date, end_date FROM commission_periods ORDER BY start_date DESC LIMIT 12";
            }else if($interval == 'weekly'){
                $query = "SELECT commission_period_id, start_date, end_date FROM commission_weekly_period ORDER BY start_date DESC LIMIT 12";
            }

            return $wpdb->get_results($query, ARRAY_A);
        }

        public function get_advanced_settings($table_id,$type='monthly') {

            global $wpdb;

            $result = $wpdb->get_row("SELECT advanced_settings FROM wp_wpdatatables WHERE id=" . $table_id);
            $result = json_decode($result->advanced_settings, true);
            
            if($type == 'monthly'){
                return $result['historyFilter'];
            } else if($type = 'weekly') {
                return $result['weeklyFilter'];
            }
        }

        public function filter_commission_period_results() {

            $arr_session = array('status' => 'fail');

            if (isset($_POST['commission_period_id'])) {
                $period_id = $_POST['commission_period_id'];
                
                if($_POST['period'] == 'monthly'){
                    set_transient('current_commission_period_id', $period_id);
                }
                
                if($_POST['period'] == 'weekly'){
                    set_transient('current_weekly_period_id', $period_id);
                }
                
                $arr_session = array('status' => 'success');
            }

            echo json_encode($arr_session);
            die();
        }

        public function resolve_commission_period_placeholder($query_string, $ID) {

            $commission_period_id = get_transient('current_commission_period_id');

            if (empty($commission_period_id)) {
                $commission_period_id = $this->get_latest_comission_period_id();
            }

            if (strpos($query_string, '%COMMISION_PERIOD_ID%') !== false) {
                $query_string = str_replace('%COMMISION_PERIOD_ID%', $commission_period_id, $query_string);
            }

            delete_transient('current_commission_period_id');

            $weekly_period_id = get_transient('current_weekly_period_id');

            if (empty($weekly_period_id)) {
                $weekly_period_id = $this->get_latest_comission_period_id('weekly');
            }

            if (strpos($query_string, '%WEEKLY_PERIOD_ID%') !== false) {
                $query_string = str_replace('%WEEKLY_PERIOD_ID%', $weekly_period_id, $query_string);
            }

            delete_transient('current_weekly_period_id');

            return $query_string;
        }

        public function get_latest_comission_period_id($interval = 'monthly') {

            global $wpdb;
            $commission_period_id = '';

            if ($interval == 'monthly') {
                $result = $wpdb->get_var("SELECT commission_period_id FROM commission_periods ORDER BY start_date DESC LIMIT 1");
            } else if ($interval == 'weekly') {
                $result = $wpdb->get_var("SELECT commission_period_id FROM commission_weekly_period ORDER BY start_date DESC LIMIT 1");
            }

            if ($result) {
                $commission_period_id = $result;
            }

            return $commission_period_id;
        }

        public function commision_period_filter_display() {
            ?>
            <div class="col-sm-6 m-b-20 commission-period-filtering-form-block">
                <h4 class="c-black m-b-20">Add History
                    <i class="wpdt-icon-info-circle-thin" data-popover-content="#commission-period-filter"
                       data-toggle="html-popover" data-trigger="hover" data-placement="right"></i>
                </h4>
                <!-- Hidden popover with image hint -->
                <div class="hidden" id="commission-period-filter">
                    <div class="popover-heading">Show historical period filter</div>

                    <div class="popover-body">Show historical commission filters in a form above the table</div>
                </div>
                <!-- /Hidden popover with image hint -->
                <div class="row">
                    <div class="col-md-6 ">
                        <div class="toggle-switch" data-ts-color="blue">
                            <label for="wdt-commission-filter-in-form" class="ts-label">Monthly Period Filter</label>
                            <input id="wdt-commission-filter-in-form" type="checkbox" hidden="hidden">
                            <label for="wdt-commission-filter-in-form" class="ts-helper"></label>
                        </div>
                    </div>
                    <div class="col-md-6 ">
                        <div class="toggle-switch" data-ts-color="blue">
                            <label for="wdt-weekly-filter-in-form" class="ts-label">Weekly Period Filter</label>
                            <input id="wdt-weekly-filter-in-form" type="checkbox" hidden="hidden">
                            <label for="wdt-weekly-filter-in-form" class="ts-helper"></label>
                        </div>
                    </div>
                </div>

            </div> 
            <?php
        }

        public function display_commission_period_namespace() {

            $commission_period_id = $this->get_latest_comission_period_id();
            $weekly_period_id = $this->get_latest_comission_period_id('weekly');
            ?>
                <div class="row period-placeholders" style="display: none">
                    <div class="col-sm-4 m-b-16">
                        <h4 class="c-title-color m-b-2">
                            %COMMISION_PERIOD_ID%
                            <i class="wpdt-icon-info-circle-thin" data-toggle="tooltip" data-placement="right"
                               title="This placeholder will be dynamically replaced with the commission_period_id of the selected historic period. The default historic period will be the current period. This field is readonly."></i>
                        </h4>

                        <div class="fg-line form-group">
                            <input type="text" value="<?php echo $commission_period_id; ?>" class="form-control input-sm" readonly>
                        </div>

                    </div> 
                    <div class="col-sm-4 m-b-16">
                        <h4 class="c-title-color m-b-2">
                            %WEEKLY_PERIOD_ID%
                            <i class="wpdt-icon-info-circle-thin" data-toggle="tooltip" data-placement="right"
                               title="This placeholder will be dynamically replaced with the weekly_period_id of the selected historic period. The default historic period will be the current period. This field is readonly."></i>
                        </h4>

                        <div class="fg-line form-group">
                            <input type="text" value="<?php echo $weekly_period_id; ?>" class="form-control input-sm" readonly>
                        </div>

                    </div> 
                </div>
            <?php
        }
        
        public function show_placeholder_tags(){
            
            if(isset($_GET['page']) && $_GET['page'] == 'wpdatatables-constructor'){
            ?>
                <script>
                    jQuery(function($){
                         /**
                         * Show "period placeholders" when "Placeholder" tab is active
                         */
                        $('.wdt-main-menu a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                            var target = $(e.target).attr("href");
                            if (target == '#placeholders-settings') {
                                $('.period-placeholders').show();
                            } else {
                                $('.period-placeholders').hide();
                            }
                        });
                    })
                </script>    
            <?php
            }
        }
        
        public function modify_wpdt_display_options($table_config) {

            $adv_settings = json_decode($table_config['advanced_settings'], true);
            
            $table = explode('historyFilter', $_POST["table"]);
            $table = explode(',',$table[1]);
            $historyFilter = str_replace('\":', "", $table[0]);
            $historyFilter = str_replace("}", "", $historyFilter);
            $adv_settings['historyFilter'] = $historyFilter;
            
            $w_table = explode('weeklyFilter', $_POST["table"]);
            $weeklyFilter = str_replace('\":', "", $w_table[1]);
            $weeklyFilter = str_replace("}", "", $weeklyFilter);
            $adv_settings['weeklyFilter'] = $weeklyFilter;
            
            $table_config['advanced_settings'] = json_encode($adv_settings);

            return $table_config;
        }
        
        public function set_curl_cookie_option($data,$ch,$url){
            curl_setopt($ch, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE']);
            return $data;
        }
        
    }

    new WPDataTable_Customization();
}