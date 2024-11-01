<?php
/*
Plugin Name: WeatherWidget
Plugin URI: http://www.fliptel.de/wordpress-plugins
Description: Shows the current weather of your location or the weather of the visitors location via widget in the sidebar  of your wordpress blog. Browse for more <a href="http://www.fliptel.de/wordpress-plugins">Wordpress Plugins</a> brought to you by <a href="http://www.fliptel.de">Fliptel</a>.
Version: 0.4
Author: fliptel
Author URI: http://www.fliptel.de
*/
 
/**
 * v0.4 11.03.2010 some improvement by refactoring loader code
 * v0.3 13.07.2009 css fix again, widget was broken in some templates
 * v0.2 10.07.2009 small css fix
 * v0.1 09.07.2009 initial release
 */
if(!class_exists('WeatherWidget')):
class WeatherWidget {
  var $id;
  var $title;
  var $plugin_url;
  var $version;
  var $name;
  var $url;
  var $options;
  var $locale;

  function WeatherWidget() {
    $this->id         = 'weatherwidget';
    $this->title      = 'WeatherWidget';
    $this->version    = '0.4';
    $this->plugin_url = 'http://www.fliptel.de/wordpress-plugins';
    $this->name       = 'WeatherWidget v'. $this->version;
    $this->url        = get_bloginfo('wpurl'). '/wp-content/plugins/' . $this->id;
	  $this->locale     = get_locale();
    $this->path       = dirname(__FILE__);

	  if(empty($this->locale)) {
		  $this->locale = 'en_US';
    }

    load_textdomain($this->id, sprintf('%s/%s.mo', $this->path, $this->locale));

    $this->loadOptions();

    if(!is_admin()) {
      add_filter('wp_head', array(&$this, 'blogHeader'));
    }
    else {
      add_action('admin_menu', array( &$this, 'optionMenu')); 
    }

    add_action('widgets_init', array( &$this, 'initWidget')); 
  }

  function optionMenu() {
    add_options_page($this->title, $this->title, 8, __FILE__, array(&$this, 'optionMenuPage'));
  }

  function optionMenuPage() {
?>
<style type="text/css">
#ColorPickerDiv {
    display: block;
    display: none;
    position: relative;
    border: 1px solid #777;
    background: #fff
}
#ColorPickerDiv TD.color {
	cursor: pointer;
	font-size: xx-small;
	font-family: 'Arial' , 'Microsoft Sans Serif';
}
#ColorPickerDiv TD.color label {
	cursor: pointer;
}
.ColorPickerDivSample {
	margin: 0px 0px 0px 4px;
	border: solid 1px #000;
	padding: 0px 10px;	
	position: relative;
	cursor: pointer;
}
</style>
<script type="text/javascript" src="<?=$this->url?>/js/colorpicker.js"></script>
<script type="text/javascript"><!--
jQuery(document).ready(function(){
jQuery(".picker1,.picker2").attachColorPicker(jQuery);
jQuery( ".picker1,.picker2" ).keyup(function() {
  jQuery.colorPicker.hideColorPicker();
  var v = jQuery(this).getValue();
  if( v && v.length == 7 ) {
    jQuery(this).setSpanColor( jQuery(this).getValue() );
  }
});
});
// --></script>
<div class="wrap">
<h2><?=$this->title?></h2>
<div align="center"><p><?=$this->name?> <a href="<?php print( $this->plugin_url ); ?>" target="_blank">Plugin Homepage</a></p></div> 
<?php
  if(isset($_POST[ $this->id ])) {

    $this->updateOptions( $_POST[ $this->id ] );

    echo '<div id="message" class="updated fade"><p><strong>' . __( 'Settings saved!', $this->id) . '</strong></p></div>'; 
  }
  
?>
<form method="post" action="options-general.php?page=<?=$this->id?>/<?=$this->id?>.php">

<table class="form-table">

<tr valign="top">
  <th scope="row"><?php _e('Title', $this->id); ?></th>
  <td colspan="3"><input name="weatherwidget[title]" type="text" id="" class="code" value="<?=$this->options['title']?>" /><br /><?php __('Title is shown above the Widget. If left empty can break your layout in widget mode!', $this->id); ?></td>
</tr>
<tr valign="top">
  <th scope="row"><?php _e('City', $this->id); ?></th>
  <td colspan="3"><input name="weatherwidget[city]" type="text" id="" class="code" value="<?=$this->options['city']?>" /><br /><?php _e('Leafe blank to autodetect the visitors city!', $this->id); ?></td>
</tr>
<tr valign="top">
  <th scope="row"><?php _e('Background color', $this->id); ?></th>
  <td colspan="3"><input name="weatherwidget[background_color]" type="text" id="" class="picker1" value="<?=$this->options['background_color']?>" /></td>
</tr>
<tr valign="top">
  <th scope="row"><?php _e('Font color', $this->id); ?></th>
  <td colspan="3"><input name="weatherwidget[font_color]" type="text" id="" class="picker2" value="<?=$this->options['font_color']?>" /></td>
</tr>


</table>

<p class="submit">
<input type="submit" name="Submit" value="<?php _e('save', $this->id); ?>" class="button" />
</p>
</form>

</div>
<?php
  }

  function updateOptions($options) {
    foreach($this->options as $k => $v) {
      if(array_key_exists( $k, $options)) {
        $this->options[ $k ] = trim($options[ $k ]);
      }
    }
        
		update_option($this->id, $this->options);
	}
  
  function loadOptions() {
    $this->options = get_option($this->id);

    if(!$this->options) {
      $this->options = array(
        'installed' => time(),
        'city' => '',
        'background_color' => '#ffffff',
        'font_color' => '#174F98',
        'title' => 'WeatherWidget'
			);

      add_option($this->id, $this->options, $this->name, 'yes');
      
      if(is_admin()) {
        add_filter('admin_footer', array(&$this, 'addAdminFooter'));
      }
    }
  }
  
  function sliceOut($data, $start, $end) {
    $from = strpos($data, $start) + strlen($start);
    
    if($from === false) {
      return false;
    }

    $to = @strpos($data, $end, $from);
    
    if($to === false) {
      return false;
    }
    
    return substr($data, $from, $to-$from);
  }

  function getWeather($city='', $language='') {
    if(!empty($city)) {

      $city = strtolower($city);
      $city = str_replace(array('ü'), array('ue'), $city);
      
      $url = 'http://www.google.de/ig/api?weather='. urlencode($city). '&hl='. $language;

      $data = $this->httpGet($url);

      if($data === false || strpos($data, 'problem') !== false) {
        return false;
      }
      
      $deg = $language == 'en' ? '&deg; F' : '&deg; C';
            
      $result = array();
      
      $current = $this->sliceOut($data, '<current_conditions>', '</current_conditions>');

      $result['current'] = array(
        'temperature' => $this->sliceOut($current, $language == 'en' ? '<temp_f data="' : '<temp_c data="', '"'). $deg,
        'condition' => $this->sliceOut($current, '<condition data="', '"'),
        'humidity' => $this->sliceOut($current, '<humidity data="', '"'),
        'icon' => 'http://www.google.com'. $this->sliceOut($current, '<icon data="', '"'),
        'wind' => $this->sliceOut($current, '<wind_condition data="', '"')
      );
      
      $result['forecast'] = array();
      
      if(preg_match_all('|<forecast_conditions>(.*?)</forecast_conditions>|', $data, $matches)) {
        if(count($matches[0]) > 0 ) {
          foreach($matches[0] as $match) {
            array_push($result['forecast'], array(
              'day' => $this->sliceOut($match, '<day_of_week data="', '"'),
              'low' => $this->sliceOut($match, '<low data="', '"'). $deg,
              'high' => $this->sliceOut($match, '<high data="', '"'). $deg,
              'icon' => 'http://www.google.com'. $this->sliceOut($match, '<icon data="', '"'),
              'condition' => $this->sliceOut($match, '<condition data="', '"')
            ));
          }
        }
      }
      
      
      return $result;
    }
    
    return false;
  }

  function initWidget() {
    if(function_exists('register_sidebar_widget')) {
      register_sidebar_widget($this->title . ' Widget', array($this, 'showWidget'), null, 'widget_weatherwidget');
    }
  }

  function showWidget( $args ) {
    extract($args);
    printf( '%s%s%s%s%s%s', $before_widget, $before_title, $this->options['title'], $after_title, $this->getCode(), $after_widget );
  }
 
  function httpGet($url) {

    if(!class_exists('Snoopy')) {
      include_once(ABSPATH. WPINC. '/class-snoopy.php');
    }

	  $Snoopy = new Snoopy();

    if(@$Snoopy->fetch($url)) {

      if(!empty($Snoopy->results)) {
        return $Snoopy->results;
      }
    }

    return false;
  }

  function blogHeader() {
    printf('<meta name="%s" content="%s/%s" />' . "\n", $this->id, $this->id, $this->version);
 print( '<style type="text/css">
#weatherwidget {padding: 0;margin: 0;color: #aaa;font-family: Arial, sans-serif;font-size: 10px;font-style: normal;font-weight: normal;letter-spacing: 0px;text-transform: none;text-align: center !important;width:160px;}
#weatherwidget a:hover, #weatherwidget a:link, #weatherwidget a:visited, #weatherwidget a:active {color: #aaa;text-decoration:none;cursor: pointer;text-transform: none;text-align: center !important;border:0;display:inline;font-size:10px;}
</style>' );

    print('<script type="text/javascript" src="http://j.maxmind.com/app/geoip.js"></script>');
    printf("<script type='text/javascript'>var weatherwidget_url='%s/weatherwidget.php?frame=1';</script>", $this->url, $this->id);

  }

  function getCode() {
    return sprintf('<script type="text/javascript" src="%s/js/%s.js"></script><div id="%s"><a href="http://www.fliptel.de/wordpress-plugins" class="snap_noshots">Plugin</a> by <a href="http://www.fliptel.de" class="snap_noshots">Fliptel</a></div>', $this->url, $this->id, $this->id);
  }
  
  function decode($s) {
    if(function_exists('utf8_decode')) {
      $s = utf8_decode($s);
    }
    return $s;
  }
  
  function frame() {
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
<head>
<title></title>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1">
<style type="text/css">
html {
  padding: 0;
  margin: 0;
}
table {
  background-color: <?=$this->options['background_color']?>;
}
th, td {
  font-family: Arial;
  font-size: 12px;
  color: <?=$this->options['font_color']?>;
}
</style>
</head>
<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0">
<?php
    if(empty($this->options['city'])) {
      $city = urldecode(wp_kses($_GET['city'], array()));
      echo "<!-- city from url: $city -->";
    }
    else {
      echo "<!-- city from config: $city -->";
    }
    
    if(empty($city)) {
      $city = $this->decode($this->options['city']);
    }

    $result = $this->getWeather($city, wp_kses(@$_GET['language'], array()));
    
    if($result === false) {
      $data = sprintf(__('Failed to get the weather for "<strong>%s</strong>".', $this->id), $city);
    }
    else {

      array_shift($result['forecast']);
      
      $data = sprintf('<table bgcolor="#909090" cellpadding="0" cellspacing="0" border="0" width="160"><tr><th colspan="2" align="center">%s</th></tr><tr><td><img src="%s" border="0" width="40" height="40" /></td><td>%s<br />%s</td></tr>', 
sprintf(__('%s<br />%s', $this->id), $city, date(__('m/d/y', $this->id), time())),
$result['current']['icon'], 
$result['current']['temperature'], 
$result['current']['condition']);

      foreach($result['forecast'] as $forecast) {
        $data .= sprintf('<tr><td valign="middle" style="padding-bottom:5px;"><strong>%s</strong>:</td><td valign="middle" style="padding-bottom:5px;">%s / %s<br />%s</td></tr>', $forecast['day'], $forecast['high'], $forecast['low'], $forecast['condition']);
      }
      
      $data .= '</table>';
    }
    
    echo $data;
?>
</body></html>
<?php
  }
}

function weatherwidget_display() {

  global $WeatherWidget;

  if($WeatherWidget) {
    echo $WeatherWidget->getcode();
  }
}
endif;

if(@isset($_GET['frame'])) {
  include_once(dirname(__FILE__). '/../../../wp-config.php');

  if(!isset($WeatherWidget)) {
    $WeatherWidget = new WeatherWidget();
  }

  $WeatherWidget->frame();
}
else {
  add_action( 'plugins_loaded', create_function( '$WeatherWidget_5ls2l', 'global $WeatherWidget; $WeatherWidget = new WeatherWidget();' ) );
}

?>