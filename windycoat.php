<?php
/**
 * @wordpress-plugin
 * Plugin Name: Windy Coat
 * Plugin URI: https://windycoat.com
 * Description: WindyCoat allows you to display a beautiful weather page on your WordPress site in a minute without coding skills! 
 * Version: 1.3.0
 * Author: Nicholas Mercer (@kittabit)
 * Author URI: https://kittabit.com
 */

defined( 'ABSPATH' ) or die( 'Direct Access Not Allowed.' );

require 'vendor/autoload.php';

use Carbon_Fields\Container;
use Carbon_Fields\Block;
use Carbon_Fields\Field;

define( 'Carbon_Fields\URL', plugin_dir_url( __FILE__ ) . "vendor/htmlburger/carbon-fields" );

if (!class_exists("WindyCoat")) {

    class WindyCoat {

        protected $WC_WIDGET_PATH;
        protected $WC_ASSET_MANIFEST;
        protected $WC_DB_VERSION;

        function __construct() {

            $this->WC_WIDGET_PATH = plugin_dir_path( __FILE__ ) . '/weather';
            $this->WC_ASSET_MANIFEST = $this->WC_WIDGET_PATH . '/build/asset-manifest.json';
            $this->WC_DB_VERSION = "1.3.0";

            register_activation_hook( __FILE__, array($this, 'wc_install') );

            if(!is_admin()):
                add_filter( 'script_loader_tag', array($this, "script_loader_wc_widget_js"), 10, 2);
                add_action( 'wp_enqueue_scripts', array($this, "enqueue_wc_widget_js"));
            endif; 
            add_shortcode( 'wc_weather', array($this, "shortcode_wc_weather_widget"));
            add_action( 'after_setup_theme', array($this, 'load_carbon_fields') );
            add_action( 'carbon_fields_register_fields', array($this, 'add_plugin_settings_page') );
            add_action( 'plugins_loaded', array($this, 'update_db_check') );
            add_action( 'rest_api_init', function () {
                register_rest_route( 'windycoat/v1', '/weather/(?P<type>\S+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'api_response_data'),
                ));
            });
            add_action( 'wp', array($this, 'wc_scheduled_tasks') );
            add_action( 'wc_sync_weather', array($this, 'remote_openweather_get_data') );
            add_action( 'admin_enqueue_scripts', array($this, 'admin_css_enqueue') );
            add_action( 'admin_notices', array($this, 'wc_admin_notice') );

        }


        /**
        * Setup Scheduled Task to Sync Weather
        *
        * @since 0.2.0
        */
        function wc_scheduled_tasks() {

            if ( !wp_next_scheduled( 'wc_sync_weather' ) ) {
                wp_schedule_event(time(), 'hourly', 'wc_sync_weather');
            }

        }


        /**
        * Checks Database & Sets Up Data Store (on activation/install)
        *
        * @since 0.1.0
        */
        function wc_install(){

            global $wpdb;
            $installed_ver = get_option( "wc_db_version" );
            $wc_api_key = get_option( '_wc_openweatherapikey' ); 

            if($wc_api_key):
                if ( $installed_ver != $this->WC_DB_VERSION ):
                    $this->remote_openweather_get_data();
                    update_option("wc_db_version", $this->WC_DB_VERSION);
                endif;
            endif;

        }


        /**
        * Checks Database & Sets Up Data Store (for upgrades versus activation/installation)
        *
        * @since 0.1.0
        */
        function update_db_check(){

            if ( get_site_option( 'wc_db_version' ) != $this->WC_DB_VERSION ):
                $this->wc_install();
            endif;

        }


        /**
        * Load & Enable Cardon Fields Support (for admin options)
        *
        * @since 0.1.0
        */
        function load_carbon_fields(){

            \Carbon_Fields\Carbon_Fields::boot();

        }


        /**
        * Setup Administration Panel Theme Options & Settings
        *
        * @since 0.1.0
        */
        function add_plugin_settings_page(){

            $timezones = array(
                'Pacific/Midway'       => "(GMT-11:00) Midway Island",
                'US/Samoa'             => "(GMT-11:00) Samoa",
                'US/Hawaii'            => "(GMT-10:00) Hawaii",
                'US/Alaska'            => "(GMT-09:00) Alaska",
                'US/Pacific'           => "(GMT-08:00) Pacific Time (US &amp; Canada)",
                'America/Tijuana'      => "(GMT-08:00) Tijuana",
                'US/Arizona'           => "(GMT-07:00) Arizona",
                'US/Mountain'          => "(GMT-07:00) Mountain Time (US &amp; Canada)",
                'America/Chihuahua'    => "(GMT-07:00) Chihuahua",
                'America/Mazatlan'     => "(GMT-07:00) Mazatlan",
                'America/Mexico_City'  => "(GMT-06:00) Mexico City",
                'America/Monterrey'    => "(GMT-06:00) Monterrey",
                'Canada/Saskatchewan'  => "(GMT-06:00) Saskatchewan",
                'US/Central'           => "(GMT-06:00) Central Time (US &amp; Canada)",
                'US/Eastern'           => "(GMT-05:00) Eastern Time (US &amp; Canada)",
                'US/East-Indiana'      => "(GMT-05:00) Indiana (East)",
                'America/Bogota'       => "(GMT-05:00) Bogota",
                'America/Lima'         => "(GMT-05:00) Lima",
                'America/Caracas'      => "(GMT-04:30) Caracas",
                'Canada/Atlantic'      => "(GMT-04:00) Atlantic Time (Canada)",
                'America/La_Paz'       => "(GMT-04:00) La Paz",
                'America/Santiago'     => "(GMT-04:00) Santiago",
                'Canada/Newfoundland'  => "(GMT-03:30) Newfoundland",
                'America/Buenos_Aires' => "(GMT-03:00) Buenos Aires",
                'Greenland'            => "(GMT-03:00) Greenland",
                'Atlantic/Stanley'     => "(GMT-02:00) Stanley",
                'Atlantic/Azores'      => "(GMT-01:00) Azores",
                'Atlantic/Cape_Verde'  => "(GMT-01:00) Cape Verde Is.",
                'Africa/Casablanca'    => "(GMT) Casablanca",
                'Europe/Dublin'        => "(GMT) Dublin",
                'Europe/Lisbon'        => "(GMT) Lisbon",
                'Europe/London'        => "(GMT) London",
                'Africa/Monrovia'      => "(GMT) Monrovia",
                'Europe/Amsterdam'     => "(GMT+01:00) Amsterdam",
                'Europe/Belgrade'      => "(GMT+01:00) Belgrade",
                'Europe/Berlin'        => "(GMT+01:00) Berlin",
                'Europe/Bratislava'    => "(GMT+01:00) Bratislava",
                'Europe/Brussels'      => "(GMT+01:00) Brussels",
                'Europe/Budapest'      => "(GMT+01:00) Budapest",
                'Europe/Copenhagen'    => "(GMT+01:00) Copenhagen",
                'Europe/Ljubljana'     => "(GMT+01:00) Ljubljana",
                'Europe/Madrid'        => "(GMT+01:00) Madrid",
                'Europe/Paris'         => "(GMT+01:00) Paris",
                'Europe/Prague'        => "(GMT+01:00) Prague",
                'Europe/Rome'          => "(GMT+01:00) Rome",
                'Europe/Sarajevo'      => "(GMT+01:00) Sarajevo",
                'Europe/Skopje'        => "(GMT+01:00) Skopje",
                'Europe/Stockholm'     => "(GMT+01:00) Stockholm",
                'Europe/Vienna'        => "(GMT+01:00) Vienna",
                'Europe/Warsaw'        => "(GMT+01:00) Warsaw",
                'Europe/Zagreb'        => "(GMT+01:00) Zagreb",
                'Europe/Athens'        => "(GMT+02:00) Athens",
                'Europe/Bucharest'     => "(GMT+02:00) Bucharest",
                'Africa/Cairo'         => "(GMT+02:00) Cairo",
                'Africa/Harare'        => "(GMT+02:00) Harare",
                'Europe/Helsinki'      => "(GMT+02:00) Helsinki",
                'Europe/Istanbul'      => "(GMT+02:00) Istanbul",
                'Asia/Jerusalem'       => "(GMT+02:00) Jerusalem",
                'Europe/Kiev'          => "(GMT+02:00) Kyiv",
                'Europe/Minsk'         => "(GMT+02:00) Minsk",
                'Europe/Riga'          => "(GMT+02:00) Riga",
                'Europe/Sofia'         => "(GMT+02:00) Sofia",
                'Europe/Tallinn'       => "(GMT+02:00) Tallinn",
                'Europe/Vilnius'       => "(GMT+02:00) Vilnius",
                'Asia/Baghdad'         => "(GMT+03:00) Baghdad",
                'Asia/Kuwait'          => "(GMT+03:00) Kuwait",
                'Africa/Nairobi'       => "(GMT+03:00) Nairobi",
                'Asia/Riyadh'          => "(GMT+03:00) Riyadh",
                'Europe/Moscow'        => "(GMT+03:00) Moscow",
                'Asia/Tehran'          => "(GMT+03:30) Tehran",
                'Asia/Baku'            => "(GMT+04:00) Baku",
                'Europe/Volgograd'     => "(GMT+04:00) Volgograd",
                'Asia/Muscat'          => "(GMT+04:00) Muscat",
                'Asia/Tbilisi'         => "(GMT+04:00) Tbilisi",
                'Asia/Yerevan'         => "(GMT+04:00) Yerevan",
                'Asia/Kabul'           => "(GMT+04:30) Kabul",
                'Asia/Karachi'         => "(GMT+05:00) Karachi",
                'Asia/Tashkent'        => "(GMT+05:00) Tashkent",
                'Asia/Kolkata'         => "(GMT+05:30) Kolkata",
                'Asia/Kathmandu'       => "(GMT+05:45) Kathmandu",
                'Asia/Yekaterinburg'   => "(GMT+06:00) Ekaterinburg",
                'Asia/Almaty'          => "(GMT+06:00) Almaty",
                'Asia/Dhaka'           => "(GMT+06:00) Dhaka",
                'Asia/Novosibirsk'     => "(GMT+07:00) Novosibirsk",
                'Asia/Bangkok'         => "(GMT+07:00) Bangkok",
                'Asia/Jakarta'         => "(GMT+07:00) Jakarta",
                'Asia/Krasnoyarsk'     => "(GMT+08:00) Krasnoyarsk",
                'Asia/Chongqing'       => "(GMT+08:00) Chongqing",
                'Asia/Hong_Kong'       => "(GMT+08:00) Hong Kong",
                'Asia/Kuala_Lumpur'    => "(GMT+08:00) Kuala Lumpur",
                'Australia/Perth'      => "(GMT+08:00) Perth",
                'Asia/Singapore'       => "(GMT+08:00) Singapore",
                'Asia/Taipei'          => "(GMT+08:00) Taipei",
                'Asia/Ulaanbaatar'     => "(GMT+08:00) Ulaan Bataar",
                'Asia/Urumqi'          => "(GMT+08:00) Urumqi",
                'Asia/Irkutsk'         => "(GMT+09:00) Irkutsk",
                'Asia/Seoul'           => "(GMT+09:00) Seoul",
                'Asia/Tokyo'           => "(GMT+09:00) Tokyo",
                'Australia/Adelaide'   => "(GMT+09:30) Adelaide",
                'Australia/Darwin'     => "(GMT+09:30) Darwin",
                'Asia/Yakutsk'         => "(GMT+10:00) Yakutsk",
                'Australia/Brisbane'   => "(GMT+10:00) Brisbane",
                'Australia/Canberra'   => "(GMT+10:00) Canberra",
                'Pacific/Guam'         => "(GMT+10:00) Guam",
                'Australia/Hobart'     => "(GMT+10:00) Hobart",
                'Australia/Melbourne'  => "(GMT+10:00) Melbourne",
                'Pacific/Port_Moresby' => "(GMT+10:00) Port Moresby",
                'Australia/Sydney'     => "(GMT+10:00) Sydney",
                'Asia/Vladivostok'     => "(GMT+11:00) Vladivostok",
                'Asia/Magadan'         => "(GMT+12:00) Magadan",
                'Pacific/Auckland'     => "(GMT+12:00) Auckland",
                'Pacific/Fiji'         => "(GMT+12:00) Fiji",
            );

            $units = array(
                'standard' => 'Standard',
                'metric' => 'Metric',
                'imperial' => 'Imperial',
            );

            $themes = array(
                'basic' => 'Basic',
                'flat' => 'Flat UI'
            );

            Container::make( 'theme_options', 'WindyCoat' )->set_page_parent("options-general.php")->add_fields( array(
                Field::make( 'separator', 'wc_openweather_basic', 'Basic Settings' )->set_classes( 'windycoat-options-heading' ),
                Field::make( 'text', 'wc_latitude', "Latitude")->set_width( 50 ),
                Field::make( 'text', 'wc_longitude', "Longitude" )->set_width( 50 ),            
                Field::make( 'separator', 'wc_openweather_styling', 'OpenWeather API' )->set_classes( 'windycoat-options-heading' ),
                Field::make( 'text', 'wc_openweatherapikey', "API Key")->set_help_text("If you need an API key from OpenWeatherMap, <a href='https://openweathermap.org/api' target='_blank'>click here</a> in order to obtain."),
                Field::make( 'text', 'wc_cache_hours', "Hours to Cache")->set_width( 33 ),
                Field::make( 'select', 'wc_time_zone', 'Time Zone' )->add_options( $timezones )->set_default_value('US/Eastern')->set_width( 33 ),            
                Field::make( 'select', 'wc_openweather_unit', 'Unit of Measurement' )->add_options( $units )->set_default_value('imperial')->set_width( 33 ),
                Field::make( 'separator', 'wc_openweather_design', 'Design Options' )->set_classes( 'windycoat-options-heading' ),
                Field::make( 'select', 'wc_openweather_theme', 'Theme/Design' )->add_options( $themes )->set_default_value('basic'),
                Field::make( 'separator', 'wc_openweather_misc', 'Misc Options' )->set_classes( 'windycoat-options-heading' ),
                Field::make( 'checkbox', 'wc_enable_powered_by', __( 'Show Powered By WindyCoat (Footer)' ) )->set_option_value( 'yes' ),
            ));

            $this->register_weather_block();
            
        }


        /**
        * Custom Admin CSS for Options
        *
        * @since 0.4.0
        */
        function admin_css_enqueue(){

            wp_enqueue_style( 'windycoat-admin-css', plugin_dir_url( __FILE__ ) . 'public/css/admin.css' );

        }
        

        /**
        * Optimize JS Loading for `wc-*` Assets
        *
        * @since 0.1.0
        */
        function script_loader_wc_widget_js($tag, $handle){

            if ( ! preg_match( '/^wc-/', $handle ) ) { return $tag; }
            return str_replace( ' src', ' async defer src', $tag );

        }


        /**
        * Load all JS/CSS assets from 'weather' React Widget
        *
        * @since 0.1.0
        */
        function enqueue_wc_widget_js(){
            
            $json_assets = file_get_contents( $this->WC_ASSET_MANIFEST );
            $asset_manifest = json_decode( $json_assets, true )['files'];

            if ( isset( $asset_manifest[ 'main.css' ] ) ) {
                wp_enqueue_style( 'wc',  plugin_dir_url( __FILE__ ) . $asset_manifest[ 'main.css' ] );
            }
        
            wp_enqueue_script( 'wc-main', plugin_dir_url( __FILE__ ) . $asset_manifest[ 'main.js' ], array(), null, true );
        
            foreach ( $asset_manifest as $key => $value ) {
                if ( preg_match( '@static/js/(.*)\.chunk\.js@', $key, $matches ) ) {
                    if ( $matches && is_array( $matches ) && count( $matches ) === 2 ) {
                        $name = "wc-" . preg_replace( '/[^A-Za-z0-9_]/', '-', $matches[1] );
                        wp_enqueue_script( $name, plugin_dir_url( __FILE__ ) . $value, array( 'wc-main' ), null, true );
                    }
                }
            
                if ( preg_match( '@static/css/(.*)\.chunk\.css@', $key, $matches ) ) {
                    if ( $matches && is_array( $matches ) && count( $matches ) == 2 ) {
                        $name = "wc-" . preg_replace( '/[^A-Za-z0-9_]/', '-', $matches[1] );
                        wp_enqueue_style( $name, plugin_dir_url( __FILE__ ) . $value, array( 'wc' ), null );
                    }
                }
            }

        }


        /**
        * [wc_weather] Shortcode (for React)
        *
        * @since 0.1.0
        */
        function shortcode_wc_weather_widget( $atts ){

            $default_atts = array();
            $args = shortcode_atts( $default_atts, $atts );

            $wc_lat = carbon_get_theme_option( 'wc_latitude' );
            $wc_lon = carbon_get_theme_option( 'wc_longitude' );
            $wc_enable_powered_by = carbon_get_theme_option( 'wc_enable_powered_by' );
            $wc_openweather_unit = carbon_get_theme_option( 'wc_openweather_unit' );
            $wc_theme = carbon_get_theme_option( 'wc_openweather_theme' );
            if(!$wc_theme): $wc_theme = "basic"; endif;

            ob_start();
            ?>
            <script>
            window.wcSettings = window.wcSettings || {};
            window.wcSettings = {
                'wc_base_url': '<?php echo esc_js(plugin_dir_url( __FILE__ )); ?>',
                'latitude': '<?php echo esc_js($wc_lat); ?>',
                'longitude': '<?php echo esc_js($wc_lon); ?>',                
                'show_logo': '<?php echo esc_js($wc_enable_powered_by); ?>',
                'unit_of_measurement': '<?php echo esc_js($wc_openweather_unit); ?>',
                'wc_theme': '<?php echo esc_js($wc_theme); ?>'
            }
            </script>
            <div class="wc-root"></div>
            <?php
            return ob_get_clean();

        }


        /**
        * Gutenberg Block Support
        *
        * @since 0.5.0
        */
        function register_weather_block(){

            Block::make( __( 'WindyCoat Weather' ) )->set_mode("preview")->set_render_callback( function ( $fields, $attributes, $inner_blocks ) {
                if (strpos($_SERVER['REQUEST_URI'],'carbon-fields') !== false):
                    echo esc_html("Notice:  Weather block only visible on the front end.");
                else:
                    echo do_shortcode("[wc_weather]");
                endif;
            } );        
            
        }


        /**
        * Centralized Remote Data Connection & Data Response
        *
        * @since 0.1.0
        */
        function get_remote_date($url){

            $response = wp_remote_get($url);
            $body = wp_remote_retrieve_body($response);

            return json_decode($body);

        }


        /**
        * OpenWeather Map API Queries & Data Storage
        *
        * @since 0.1.0
        */
        function remote_openweather_get_data(){

            $wc_lat = get_option( '_wc_latitude' );
            $wc_lon = get_option( '_wc_longitude' );
            $wc_api_key = get_option( '_wc_openweatherapikey' );        

            if($wc_api_key && $wc_lon && $wc_lat):
                $wc_cache_hours = get_option( '_wc_cache_hours' );        
                if(!$wc_cache_hours): $wc_cache_hours = 1; endif;

                $wc_unit = get_option( '_wc_openweather_unit' );        
                if(!$wc_openweather_unit): $wc_openweather_unit = "standard"; endif;

                $wc_timezone = get_option( '_wc_time_zone' );        
                if(!$wc_timezone): $wc_timezone = "US/Eastern"; endif;        

                $weather_forecast = array();

                $wc_weather_data = get_option("wc_weather_data");
                $wc_weather_last_updated = get_option("wc_weather_last_updated");

                date_default_timezone_set($wc_timezone);

                if( time() - $wc_weather_last_updated > (3601 * $wc_cache_hours) ):

                    //CURRENT WEATHER
                    $remote_url = "https://api.openweathermap.org/data/2.5/weather?lat={$wc_lat}&lon={$wc_lon}&units={$wc_unit}&appid={$wc_api_key}";
                    $response = $this->get_remote_date($remote_url);
                    $weather_forecast['current'] = array(
                        "date" => date("F j, Y"),
                        "main" => $response->weather[0]->main,
                        "description" => $response->weather[0]->description,
                        "icon" => $response->weather[0]->icon,
                        "temp" => $response->main->temp,
                        "feels_like" => $response->main->feels_like,
                        "temp_min" => $response->main->temp_min,
                        "temp_max" => $response->main->temp_max,
                        "pressure" => $response->main->pressure,
                        "humidity" => $response->main->humidity,
                        "wind_speed" => $response->wind->speed,
                        "city_name" => $response->name
                    );

                    // HOURLY FORECAST
                    $remote_url = "https://api.openweathermap.org/data/2.5/onecall?lat={$wc_lat}&lon={$wc_lon}&units={$wc_unit}&exclude=current,minutely&appid={$wc_api_key}";
                    $response = $this->get_remote_date($remote_url);
                    foreach (range(0, 14) as $number):
                        $weather_forecast['hourly'][$number] = array(
                            "dt" => $response->hourly[$number]->dt,
                            "hour" => date("h", $response->hourly[$number]->dt),
                            "period" => date("A", $response->hourly[$number]->dt),
                            "temp" => $response->hourly[$number]->temp,
                            "feels_like" => $response->hourly[$number]->feels_like,
                            "pressure" => $response->hourly[$number]->pressure,
                            "humidity" => $response->hourly[$number]->humidity,
                            "dew_point" => $response->hourly[$number]->dew_point,
                            "uvi" => $response->hourly[$number]->uvi,
                            "clouds" => $response->hourly[$number]->clouds,
                            "visibility" => $response->hourly[$number]->visibility,
                            "wind_speed" => $response->hourly[$number]->wind_speed,
                            "wind_gust" => $response->hourly[$number]->wind_gust,
                            "main" => $response->hourly[$number]->weather[0]->main,
                            "description" => $response->hourly[$number]->weather[0]->description,
                            "icon" => $response->hourly[$number]->weather[0]->icon,
                        );
                    endforeach;

                    foreach($response->daily as $daily):
                        $weather_forecast['daily'][] = array(
                            "dt" => $daily->dt,
                            "label" => date("D", $daily->dt),
                            "sunrise" => $daily->sunrise,
                            "sunset" => $daily->sunset,
                            "moonrise" => $daily->moonrise,
                            "moonset" => $daily->moonset,
                            "moon_phase" => $daily->moon_phase,
                            "temp_low" => $daily->temp->min,
                            "temp_high" => $daily->temp->max,
                            "pressure" => $daily->pressure,
                            "humidity" => $daily->humidity,
                            "wind_speed" => $daily->wind_speed,
                            "wind_gust" => $daily->wind_gust,
                            "clouds" => $daily->clouds,
                            "main" => $daily->weather[0]->main,
                            "description" => $daily->weather[0]->description,
                            "icon" => $daily->weather[0]->icon
                        );
                    endforeach;

                    if( count($weather_forecast['daily']) > 0 && count($weather_forecast['daily']) > 0 ):
                        update_option("wc_weather_data", json_encode($weather_forecast));
                        update_option("wc_weather_last_updated", time());
                    endif;

                endif;
            endif;

        }


        /**
        * Setup API URL's for JSON Output
        *
        * @since 0.1.0
        */
        function api_response_data( $data ){

            $selected_type = $data['type'];

            $wc_weather_last_updated = get_option("wc_weather_last_updated");
            $wc_cache_hours = get_option( '_wc_cache_hours' );        
            if(!$wc_cache_hours): $wc_cache_hours = 1; endif;

            if( (!get_option("wc_weather_data")) || (time() - $wc_weather_last_updated > (3601 * $wc_cache_hours)) ):
                $this->remote_openweather_get_data();            
            endif;

            $weather = json_decode(get_option("wc_weather_data"));
            if($selected_type == "current"):
                echo json_encode($weather->current);
            elseif($selected_type == "hourly"):
                echo json_encode($weather->hourly);
            elseif($selected_type == "daily"):
                echo json_encode($weather->daily);
            endif;

        }


        /**
        * Custom Admin Notices
        *
        * @since 0.5.0
        */
        function wc_admin_notice(){

            $wc_lat = get_option( '_wc_latitude' );
            $wc_lon = get_option( '_wc_longitude' );
            $wc_api_key = get_option( '_wc_openweatherapikey' );
            
            if(!$wc_api_key || !$wc_lat || !$wc_lon):
                $wc_settings_url = admin_url('options-general.php?page=crb_carbon_fields_container_windycoat.php');
                echo "<div class='notice notice-warning'>
                    <p>In order to use WindyCoat, you'll need to finish your <a href='{$wc_settings_url}'>setup process</a>.</p>
                </div>";
            endif;

        }

    }

}
$windycoat = new WindyCoat();