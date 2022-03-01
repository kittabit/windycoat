<?php
/**
 * @wordpress-plugin
 * Plugin Name: Windy Coat
 * Plugin URI: https://www.windycoat.com
 * Description: WindyCoat allows you to display a beautiful weather page on your WordPress site in a minute without coding skills! 
 * Version: 0.4.0
 * Author: Nicholas Mercer (@kittabit)
 * Author URI: https://kittabit.com
 */

// TODO:  Proper Unit Output (in design)
// TODO:  Block (versus just shortcode)
// TODO:  Documentation
// TODO:  Debug/Developer Tools Security (with admin key)

defined( 'ABSPATH' ) or die( 'Direct Access Not Allowed.' );

require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

use Carbon_Fields\Container;
use Carbon_Fields\Block;
use Carbon_Fields\Field;

define( 'Carbon_Fields\URL', $_SERVER['HTTP_X_FORWARDED_PROTO'] . "://" . $_SERVER['HTTP_X_FORWARDED_HOST'] . "/wp-content/plugins/windycoat/vendor/htmlburger/carbon-fields" );

class WindyCoat {

    protected $WC_WIDGET_PATH;
    protected $WC_ASSET_MANIFEST;
    protected $WC_DB_VERSION;
    protected $WC_DEBUG;

    function __construct() {

        $this->WC_WIDGET_PATH = plugin_dir_path( __FILE__ ) . '/weather';
        $this->WC_ASSET_MANIFEST = $this->WC_WIDGET_PATH . '/build/asset-manifest.json';
        $this->WC_DB_VERSION = "0.4.0";
        if($_GET['wc_debug'] == "1"):
            $this->WC_DEBUG = true;
        else:
            $this->WC_DEBUG = false;
        endif;

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
        add_action( 'init', array($this, 'developer_tools') );
        add_action( 'admin_enqueue_scripts', array($this, 'admin_css_enqueue') );

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

        if ( $installed_ver != $this->WC_DB_VERSION ):
            /*
            Note:  Might USE SQL Here, Might Not - Just In Case 
            */
            update_option("wc_db_version", $this->WC_DB_VERSION);
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

        Container::make( 'theme_options', 'WindyCoat' )->add_fields( array(
            Field::make( 'separator', 'wc_openweather_basic', 'Basic Settings' )->set_classes( 'windycoat-options-heading' ),
            Field::make( 'text', 'wc_latitude', "Latitude")->set_width( 50 ),
            Field::make( 'text', 'wc_longitude', "Longitude" )->set_width( 50 ),            
            Field::make( 'separator', 'wc_openweather_styling', 'OpenWeather API' )->set_classes( 'windycoat-options-heading' ),
            Field::make( 'text', 'wc_openweatherapikey', "API Key"),
            Field::make( 'text', 'wc_cache_hours', "Hours to Cache")->set_width( 33 ),
            Field::make( 'select', 'wc_time_zone', 'Time Zone' )->add_options( $timezones )->set_default_value('US/Eastern')->set_width( 33 ),            
            Field::make( 'select', 'wc_openweather_unit', 'Unit of Measurement' )->add_options( $units )->set_default_value('imperial')->set_width( 33 ),
            Field::make( 'separator', 'wc_openweather_misc', 'Misc Options' )->set_classes( 'windycoat-options-heading' ),
            Field::make( 'checkbox', 'wc_enable_powered_by', __( 'Show Powered By WindyCoat (Footer)' ) )->set_option_value( 'yes' ),
        ));
        
    }


    /**
    * Custom Admin CSS for Options
    *
    * @since 0.4.0
    */
    function admin_css_enqueue(){

        wp_enqueue_style( 'windycoat-admin-css', plugin_dir_url( __FILE__ ) . '/public/css/admin.css' );

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

        $asset_manifest = json_decode( file_get_contents( $this->WC_ASSET_MANIFEST ), true )['files'];

        if ( isset( $asset_manifest[ 'main.css' ] ) ) {
          wp_enqueue_style( 'wc', get_site_url() . $asset_manifest[ 'main.css' ] );
        }
    
        wp_enqueue_script( 'wc-main', get_site_url() . $asset_manifest[ 'main.js' ], array(), null, true );
    
        foreach ( $asset_manifest as $key => $value ) {
          if ( preg_match( '@static/js/(.*)\.chunk\.js@', $key, $matches ) ) {
            if ( $matches && is_array( $matches ) && count( $matches ) === 2 ) {
              $name = "erw-" . preg_replace( '/[^A-Za-z0-9_]/', '-', $matches[1] );
              wp_enqueue_script( $name, get_site_url() . $value, array( 'wc-main' ), null, true );
            }
          }
    
          if ( preg_match( '@static/css/(.*)\.chunk\.css@', $key, $matches ) ) {
            if ( $matches && is_array( $matches ) && count( $matches ) == 2 ) {
              $name = "wc-" . preg_replace( '/[^A-Za-z0-9_]/', '-', $matches[1] );
              wp_enqueue_style( $name, get_site_url() . $value, array( 'wc' ), null );
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
        ob_start();
        ?>
        <script>
        window.wcSettings = window.wcSettings || {};
        window.wcSettings = {
            'latitude': '<?= $wc_lat; ?>',
            'longitude': '<?= $wc_lon; ?>',
            'show_logo': '<?= $wc_enable_powered_by; ?>'
        }
        </script>
        <div class="wc-root"></div>
        <?php
        return ob_get_clean();

    }


    /**
    * Centralized CURL Configuration & Setup
    *
    * @since 0.1.0
    */
    function get_remote_date($url){

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "Accept: application/json",
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl);
        curl_close($curl);

        if($this->WC_DEBUG):
            echo "<span><strong>Debug URL: " . $url . "</strong></span><br />\n";
            print_r($response);
            echo "<hr />\n";
        endif;

        return json_decode($response);

    }


    /**
    * OpenWeather Map API Queries & Data Storage
    *
    * @since 0.1.0
    */
    function remote_openweather_get_data(){

        $wc_lat = carbon_get_theme_option( 'wc_latitude' );
        $wc_lon = carbon_get_theme_option( 'wc_longitude' );
        $wc_api_key = carbon_get_theme_option( 'wc_openweatherapikey' );

        $wc_cache_hours = carbon_get_theme_option( 'wc_cache_hours' );
        if(!$wc_cache_hours): $wc_cache_hours = 1; endif;

        $wc_unit = carbon_get_theme_option( 'wc_openweather_unit' );
        if(!$wc_openweather_unit): $wc_openweather_unit = "standard"; endif;

        $wc_timezone = carbon_get_theme_option( 'wc_time_zone' );
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

    }


    /**
    * Setup API URL's for JSON Output
    *
    * @since 0.1.0
    */
    function api_response_data( $data ){

        $selected_type = $data['type'];

        if(!get_option("wc_weather_data")):
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
    * Custom Tools for Testing, Manual CRON's, etc.
    *
    * @since 0.4.0
    */
    function developer_tools(){

        if($_GET['windycoat_action'] == "purge"):

            update_option("wc_weather_data", "");
            update_option("wc_weather_last_updated", "");

        elseif($_GET['windycoat_action'] == "import"):

            $this->remote_openweather_get_data();

        elseif($_GET['windycoat_action'] == "validate"):

            $weather = json_decode(get_option("wc_weather_data"));
            print_r($weather);

        endif;

    }

}
$windycoat = new WindyCoat();