<?php
/**
 * Plugin Name: Simple Brightcove
 * Plugin URI: http://misterben.me/todo
 * Description: Allows the easy inclusion of a Brightcove player
 * Version: 0.1
 * Author: Me
 * Author URI: http://URI_Of_The_Plugin_Author
 * License:GPL2
 * Usage: [bc]http://bcove.me/bbnvrhso[/bc]
 * 
 * This is a simple way to publish a player by using a link to a player.
 * This hasn't been extensively tested, but does work with the bcove.me links from the media module.
 * There may be ways you can break this.
 * 
 */
class BC_Shortcode {
	static $add_script;

	static function init() {
		add_shortcode('bc', array(__CLASS__, 'handle_shortcode'));

		add_action('init', array(__CLASS__, 'register_script'));
		add_action('wp_footer', array(__CLASS__, 'print_script'));
	}

	static function newParam($dom, $name, $value) {
		$newParam = $dom->createElement('param');
		$newParam->setAttribute("name",$name);
		$newParam->setAttribute("value",$value);
		return $newParam;
	}

	static function handle_shortcode($atts, $content = null) {
		// Retrieve the URL and find the first object with class BrightcoveExperience
		// TODO: This could cache to the local db
		$html = file_get_contents($content);
		libxml_use_internal_errors(true);
		$dom = new DOMDocument;
		$dom->loadHTML($html);
		$xpath =new DOMXPath($dom);
		$query = '//object[@class="BrightcoveExperience"]';
		$objects = $xpath->query($query);
		
		// If nothing matches, abort
		if ($objects->length == 0) {
			$errorMessage = $dom->createElement('p');
			$errorMessage->appendChild($dom->createTextNode('There is no player at ' . $content . '.'));
			return $dom->saveHTML($errorMessage);
		}
		
		$object = $objects->item(0);
		
		// Grab the twitter card URL to find the video id
		// TODO: Check if this is needed i.e. if there is already an @videoPlayer param
		//       And if needed and there is no video id to be found, show and error
		$query = '//meta[@name="twitter:player"]';
		$metas = $xpath->query($query);
		if($metas->length > 0) {
			parse_str(parse_url($metas->item(0)->getAttribute("content"), PHP_URL_QUERY),$args);
			$object->appendChild(self::newParam($dom, '@videoPlayer',$args['bctid']));
		}
		if (is_ssl()) {
			$object->appendChild(self::newParam($dom, 'secureConnections','true'));
			$object->appendChild(self::newParam($dom, 'secureHTMLConnections','true'));
		}
		// Make sure the script gets written, and return HTML
		self::$add_script = true;
		return $dom->saveHTML($object);
	}

	static function register_script() {
		if (is_ssl()) {
			$scriptUrl =  'https://sadmin.brightcove.com/js/BrightcoveExperiences.js';
		} else {
			$scriptUrl =  'http://admin.brightcove.com/js/BrightcoveExperiences.js';
		}
		wp_register_script('brightcove', $scriptUrl, array(), false, true);
	}

	static function print_script() {
		if ( ! self::$add_script )
			return;

		wp_print_scripts('brightcove');
	}
}

BC_Shortcode::init();
?>
