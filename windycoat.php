<?php
/**
 * @wordpress-plugin
 * Plugin Name: Windy Coat
 * Plugin URI: https://www.windycoat.com
 * Description: Basic Weather Plugin
 * Version: 0.1.0
 * Author: Nicholas Mercer (@kittabit)
 * Author URI: https://kittabit.com
 */

// TODO:  CACHING OPTIONS IN ADMIN
// TODO:  ALLOW ADMIN `units` OF MEASUREMENT OPTIONS
// TODO:  ReactJS Styling
// TODO:  Add CRON, Remove `developer_tests` Function

defined( 'ABSPATH' ) or die( 'Direct Access Not Allowed.' );

require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
use Carbon_Fields\Container;
use Carbon_Fields\Field;

define( 'Carbon_Fields\URL', $_SERVER['HTTP_X_FORWARDED_PROTO'] . "://" . $_SERVER['HTTP_X_FORWARDED_HOST'] . "/wp-content/plugins/windycoat/vendor/htmlburger/carbon-fields" );

class WindyCoat {

    protected $WC_WIDGET_PATH;
    protected $WC_ASSET_MANIFEST;
    protected $WC_DB_VERSION;

    function __construct() {

        $this->WC_WIDGET_PATH = plugin_dir_path( __FILE__ ) . '/weather';
        $this->WC_ASSET_MANIFEST = $this->WC_WIDGET_PATH . '/build/asset-manifest.json';
        $this->WC_DB_VERSION = "0.1.0";

        if(!is_admin()):
            add_filter( 'script_loader_tag', array($this, "script_loader_wc_widget_js"), 10, 2);
            add_action( 'wp_enqueue_scripts', array($this, "enqueue_wc_widget_js"));
        endif; 
        add_shortcode( 'wc_weather', array($this, "shortcode_wc_weather_widget"));

        add_action( 'after_setup_theme', array($this, 'load_carbon_fields') );
        add_action( 'carbon_fields_register_fields', array($this, 'add_plugin_settings_page') );
        add_action( 'plugins_loaded', array($this, 'update_db_check') );
        add_action( 'rest_api_init', function () {
            register_rest_route( 'windycoat/v1', '/weather/(?P<type>\d+)', array(
              'methods' => 'GET',
              'callback' => array($this, 'api_response_data'),
            ));
        });

        register_activation_hook( __FILE__, array($this, 'wc_install') );

        add_action( 'init', array($this, 'developer_tests') );

    }


    /**
    * Checks Database & Sets Up Data Store (on activation/install)
    *
    * @since 1.0.0
    */
    function wc_install(){

        global $wpdb;
        $installed_ver = get_option( "wc_db_version" );

        if ( $installed_ver != $this->WC_DB_VERSION ):
            /*
            Might USE SQL Here, Might Not - Just In Case 
            */
            update_option("wc_db_version", $this->WC_DB_VERSION);
        endif;

    }


    /**
    * Checks Database & Sets Up Data Store (for upgrades versus activation/installation)
    *
    * @since 1.0.0
    */
    function update_db_check(){

        if ( get_site_option( 'wc_db_version' ) != $this->WC_DB_VERSION ):
            $this->wc_install();
        endif;

    }


    /**
    * Load & Enable Cardon Fields Support (for admin options)
    *
    * @since 1.0.0
    */
    function load_carbon_fields(){

        \Carbon_Fields\Carbon_Fields::boot();

    }

    /**
    * Setup Administration Panel Theme Options & Settings
    *
    * @since 1.0.0
    */
    function add_plugin_settings_page(){

       Container::make( 'theme_options', 'WindyCoat' )
        ->add_fields( array(
            Field::make( 'separator', 'wc_openweather_basic', 'Basic Settings' ),
            Field::make( 'text', 'wc_latitude', "Latitude")->set_width( 50 ),
            Field::make( 'text', 'wc_longitude', "Longitude" )->set_width( 50 ),
            Field::make( 'separator', 'wc_openweather_styling', 'OpenWeather API' ),
            Field::make( 'text', 'wc_openweatherapikey', "API Key")
        ) );
        
    }


    /**
    * Optimize JS Loading for `wc-*` Assets
    *
    * @since 1.0.0
    */
    function script_loader_wc_widget_js($tag, $handle){

        if ( ! preg_match( '/^wc-/', $handle ) ) { return $tag; }
        return str_replace( ' src', ' async defer src', $tag );

    }


    /**
    * Load all JS/CSS assets from 'weather' React Widget
    *
    * @since 1.0.0
    */
    function enqueue_wc_widget_js(){

        $asset_manifest = json_decode( file_get_contents( $this->WC_ASSET_MANIFEST ), true )['files'];

        if ( isset( $asset_manifest[ 'main.css' ] ) ) {
          wp_enqueue_style( 'wc', get_site_url() . $asset_manifest[ 'main.css' ] );
        }
    
        wp_enqueue_script( 'wc-runtime', get_site_url() . $asset_manifest[ 'runtime~main.js' ], array(), null, true );
    
        wp_enqueue_script( 'wc-main', get_site_url() . $asset_manifest[ 'main.js' ], array('wc-runtime'), null, true );
    
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
    * @since 1.0.0
    */
    function shortcode_wc_weather_widget( $atts ){

        $default_atts = array();
        $args = shortcode_atts( $default_atts, $atts );
        $unique_div_id = uniqid('id');
      
        $wc_lat = carbon_get_theme_option( 'wc_latitude' );
        $wc_lon = carbon_get_theme_option( 'wc_longitude' );
        ob_start();
        ?>
        <script>
        window.wcSettings = window.wcSettings || {};
        window.wcSettings["<?= $unique_div_id ?>"] = {
            'latitude': '<?= $wc_lat; ?>',
            'longitude': '<?= $wc_lon; ?>',
        }
        </script>
        <div class="wc-root" data-id="<?= $unique_div_id ?>"></div>
        <?php
        return ob_get_clean();

    }


    /**
    * Centralized CURL Configuration & Setup
    *
    * @since 1.0.0
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

        return json_decode($response);

    }


    /**
    * OpenWeather Map API Queries & Data Storage
    *
    * @since 1.0.0
    */
    function remote_openweather_get_data(){

        $wc_lat = carbon_get_theme_option( 'wc_latitude' );
        $wc_lon = carbon_get_theme_option( 'wc_longitude' );
        $wc_api_key = carbon_get_theme_option( 'wc_openweatherapikey' );
        $weather_forecast = array();

        $wc_weather_data = get_option("wc_weather_data");
        $wc_weather_last_updated = get_option("wc_weather_last_updated");

        if(time() - $wc_weather_last_updated > 3601):

            //CURRENT WEATHER
            $remote_url = "https://api.openweathermap.org/data/2.5/weather?lat={$wc_lat}&lon={$wc_lon}&units=imperial&appid={$wc_api_key}";
            $response = $this->get_remote_date($remote_url);
            $weather_forecast['current'] = array(
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
            $remote_url = "https://api.openweathermap.org/data/2.5/onecall?lat={$wc_lat}&lon={$wc_lon}&units=imperial&exclude=current,minutely&appid={$wc_api_key}";
            $response = $this->get_remote_date($remote_url);
            foreach (range(0, 12) as $number):
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

            update_option("wc_weather_data", json_encode($weather_forecast));
            update_option("wc_weather_last_updated", time());

        endif;

    }


    /**
    * Setup API URL's for JSON Output
    *
    * @since 1.0.0
    */
    function api_response_data( $data ){

        $selected_type = $data['type'];
        $weather = json_decode(get_option("wc_weather_data"));
        
        if($selected_type == 1):
            return $weather->current;
        elseif($selected_type == 2):
            return $weather->hourly;
        elseif($selected_type == 3):
            return $weather->daily;
        else:
            return $weather;
        endif;

    }


    /**
    * Debug & Testing Tools (removing soon)
    *
    * @since 1.0.0
    */
    function developer_tests(){

        if($_GET['wc_action'] == "getData"):
            $this->remote_openweather_get_data();
        endif;

    }

}
$windycoat = new WindyCoat();