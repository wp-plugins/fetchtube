<?php
/*
Plugin Name: fetchTube
Plugin URI: http://wordpress.org/extend/plugins/fetchtube/
Description: Allows you to include Youtube videos of your or any other channel with preview pcitures into the sidebar
Version: 0.1
License: GPL
Author: Christian Leo
Author URI: http://mazedlx.net
*/
if(!class_exists(fetchTube)) {
	class fetchTube {
		function version() {
			$this->version = '0.1';
		}
		
		function getSettings() {
			if(!get_option('fetchtube_settings')) {
				$settings = array(
                    'title' => 'My YouTube Channel',
					'userId' => 'deftones',
                    'typeOf' => 'uploads',
                    'format' => 'json',
					'numberOfClips' => '5',
					'orderBy' => 'rating',
					'thumbWidth' => '160',
					'thumbHeight' => '120',
                    'errorMsg' => 'Sorry, no videos were found.'
				);
			} else {
				$settings = get_option('fetchtube_settings');
			}
			return $settings;
		}

        function setupWidget() {
			if (!function_exists('wp_register_sidebar_widget')) return;
			function widget_fetchtube($args) {
				extract($args);
				$options = get_option('fetchtube_widget');
				$title = $options['title'];
				echo $before_widget . $before_title . $title . $after_title;
				get_fetchTube();
				echo $after_widget;
			}
			function widget_fetchtube_control() {
				$options = get_option('fetchtube_widget');
				if ( $_POST['fetchtube-submit'] ) {
					$options['title'] = strip_tags(stripslashes($_POST['fetchtube-title']));
					update_option('fetchtube_widget', $options);
				}
				$title = htmlspecialchars($options['title'], ENT_QUOTES);
				$settingspage = trailingslashit(get_option('siteurl')).'wp-admin/options-general.php?page='.basename(__FILE__);
				echo
				'<p><label for="fetchtube-title">Title:<input name="fetchtube-title" type="text" value="'.$title.'" /></label></p>'.
				'<p>To control the other settings, please visit the <a href="'.$settingspage.'">fetchTube Settings page</a>.</p>'.
				'<input type="hidden" id="fetchtube-submit" name="fetchtube-submit" value="1" />';
			}
			wp_register_sidebar_widget('fetchtube', 'fetchTube', 'widget_fetchtube');
			wp_register_widget_control('fetchtube', 'fetchTube', 'widget_fetchtube_control');
		}

        function setupSettingsPage() {
			if (function_exists('add_options_page')) {
				add_options_page('fetchTube Settings', 'fetchTube', 8, basename(__FILE__), array(&$this, 'printSettingsPage'));
			}
		}

		function printSettingsPage() {
			if (isset($_POST['save_fetchtube_settings'])) {
                $temp = array('title','userId','typeOf','format','numberOfClips','orderBy','thumbWidth','thumbHeight','errorMsg');
				foreach ($temp as $name) {
					$settings[$name] = $_POST['fetchtube_'.$name];
				}
				update_option('fetchtube_settings', $settings);
				echo '<div class="updated"><p>fetchTube settings saved!</p></div>';
			} elseif (isset($_POST['reset_fetchtube_settings'])) {
				delete_option('fetchtube_settings');
                $settings = $this->getSettings();
                add_option('fetchtube_settings',$settings);
				echo '<div class="updated"><p>fetchTube settings restored to default!</p></div>';
			} else {
                $settings = get_option('fetchtube_settings');
            }
            
			include ("fetchtube-options.php");
		}
			
		function getClips() {
            $settings = $this->getSettings();
            $curlURL = 'http://gdata.youtube.com/feeds/users/';
            $curlURL.= $settings['userId'];
            $curlURL.= '/'.$settings['typeOf'];
            $curlURL.= '?alt='.$settings['format'];
            $curlURL.= '&max-results='.$settings['numberOfClips'];
            $curlURL.= '&orderby='.$settings['orderBy'];
            $ytHandler = curl_init();

            curl_setopt($ytHandler, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ytHandler, CURLOPT_URL, $curlURL);
            curl_setopt($ytHandler, CURLOPT_HEADER, 0);

            $aResults = json_decode(curl_exec($ytHandler), true);
            curl_close($ytHandler);
            $output = ' <style>
                        .fetchTube ul {list-style-type: none;padding: 0px; margin: 0px;}
                        .fetchTube li {list-style-type: none;padding: 0px; margin: 0px;}
                        </style>';
            $output.= ' <ul class="fetchTube">';
            if(count($aResults) > 0) {
                foreach($aResults['feed']['entry'] as $result) {
                $output.= '     <li class="fetchTube">
                                    <a href="'.$result['link'][0]['href'].'">'.utf8_decode($result['title']['$t']).'
                                    <br /><img src="'.$result['media$group']['media$thumbnail'][0]['url'].'" width="'.$settings['thumbWidth'].'" height="'.$settings['thumbHeight'].'" /></a>
                                </li>';
                }
            } else {
                $output.= '<li>'.$settings['errorMsg'].'</li>';
            }
            $output.= '</ul>';
            return $output;
		}
	}
}

$fetchTube = new fetchTube();
add_action( 'admin_menu', array(&$fetchTube, 'setupSettingsPage') );
add_action( 'plugins_loaded', array(&$fetchTube, 'setupWidget') );

function get_fetchTube() {
    global $fetchTube;
    echo $fetchTube->getClips();
}
?>