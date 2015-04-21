<?php
/*
Plugin Name: OmniVideo
Plugin URI: http://www.colorlabsproject.com/plugins/omnivideo/
Description: Enhance your WordPress website with the OmniVideo Photo Gallery. You can easily add your videos from your Youtube, Vimeo or Daily Motion channel. Adding the gallery is very simple, you just have to add new post/page, then select gallery icon, select OmniVideo then you can select which video source you want to insert.
Version: 1.1
Author: ColorLabs & Company
Author URI: http://www.colorlabsproject.com

Copyright 2015 ColorLabs & Company

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if ( ! defined( 'ABSPATH' ) )
	die( __("Can't load this file directly") );	

class Colabs_OmniVideo {
	var $plugin_url;
	var $plugin_dir;
	var $video_sources;
	var $textdomain = 'omnivideo';

	/**
	 * Constructor
	 */
	function __construct() {
		$this->plugin_url = plugin_dir_url( __FILE__ );
		$this->plugin_dir = plugin_dir_path( __FILE__ );
		$this->get_video_sources();

		// Script and styles
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts_styles' ) );
		add_action( 'admin_print_styles', array( $this, 'admin_scripts_styles' ) );

		// Media Tabs
		add_filter( 'media_upload_tabs', array( $this, 'upload_tab' ) );
		add_action( 'media_upload_omnivideo', array( $this, 'tabs_content' ) );

		// Omnivideo Shortcode
		add_shortcode( 'omnivideo', array( $this, 'omnivideo_shortcode' ) );

		// Add WP Pointer
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_pointer_script_style' ) );

		// Languages
		$this->load_plugin_textdomain();
	}


	/**
	 * Get OmniVideo video sources
	 */
	function get_video_sources() {
		$this->video_sources = new stdClass;

		require_once( $this->plugin_dir ."/video_source/dailymotion.php");
		require_once( $this->plugin_dir ."/video_source/youtube.php");
		require_once( $this->plugin_dir ."/video_source/vimeo.php");

		$this->video_sources->dailymotion = new OmniVideo_DailyMotion();
		$this->video_sources->youtube = new OmniVideo_YouTube();
		$this->video_sources->vimeo = new OmniVideo_Vimeo();
	}


	/**
	 * OmniVideo Scripts
	 */
	function scripts_styles() {
		wp_enqueue_script( 'omnivideo-min', $this->plugin_url . 'js/bootstrap.js', array('jquery') );
		wp_enqueue_script( 'omnivideo-js', $this->plugin_url . 'js/scripts.js', array('jquery'));
		wp_enqueue_style( 'omnivideo-style', $this->plugin_url . 'css/omnivideo.css' );
	}


	/**
	 * OmniVideo admin scripts and styles
	 */
	function admin_scripts_styles() {
		wp_enqueue_style( 'omnivideo-admin-css', $this->plugin_url . 'css/admin.css' );
	}


	/**
	 * Add OmniVideo tabs into WordPress media tab
	 */
	function upload_tab( $tabs ) {
		$tabs[ 'omnivideo' ] = 'OmniVideo';
		return $tabs;
	}


	/**
	 * Fill of the tabs using wp iframe
	 */
	function tabs_content() {
		wp_iframe( array( $this, 'iframe_tabs_content' ) );
	}


	/**
	 * OmniVideo tabs content
	 */
	function iframe_tabs_content() {
		media_upload_header();
		wp_enqueue_script( 'media-editor' );
		wp_enqueue_style( 'media-views' );
		require_once( $this->plugin_dir ."/omnivideo-form.php");
	}


	/**
	 * OmniVideo Shortcode
	 */
	function omnivideo_shortcode( $atts ) {
		extract( shortcode_atts( array(
			'source' => 'youtube',
			'username' => 'UCkiXdz9KUfeyPLN_cYAxveg',
			'result' => '6',
			'type' => 'image',
			'column' => '2',
			'description' => false,
		), $atts ) );

		$output = '<div class="omnivideo-wrapper">
							<ul class="gallery gallery-columns-'.$column.'">';

		if( isset( $this->video_sources->$source ) ) {
			$output .= $this->video_sources->$source->render( $atts );
		}

		$output .= '</ul></div>';	
		return $output;
	}


	/**
	 * Enqueue pointer script and style
	 */
	function enqueue_pointer_script_style() {
		// Assume pointer shouldn't be shown
		$enqueue_pointer_script_style = false;

		// Get array list of dismissed pointers for current user and convert it to array
		$dismissed_pointers = explode( ',', get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );

		// Check if our pointer is not among dismissed ones
		if( !in_array( 'omnivideo_admin_pointer', $dismissed_pointers ) ) {
		  $enqueue_pointer_script_style = true;
		  
		  // Add footer scripts using callback function
		  add_action( 'admin_print_footer_scripts', array( $this, 'pointer_print_scripts' ) );
		}

		// Enqueue pointer CSS and JS files, if needed
		if( $enqueue_pointer_script_style ) {
		  wp_enqueue_style( 'wp-pointer' );
		  wp_enqueue_script( 'wp-pointer' );
		}
	}


	/**
	 * Print pointer script
	 */
	function pointer_print_scripts() {
		$pointer_content  = "<h3>" . __('OmniVideo Plugin is active!', 'omnivideo') . "</h3>";
		$pointer_content .= "<p>" . __('You can start add a post/page and click on <strong>add media</strong> button to add your videos', 'omnivideo') . "</p>";
		?>
		
		<script type="text/javascript">
			//<![CDATA[
			jQuery(document).ready( function($) {
				$('#menu-posts').pointer({
					content: '<?php echo $pointer_content; ?>',
					position: {
						edge: 'left',
						align: 'center'
					},
					pointerWidth: 350,
					close: function() {
						$.post( ajaxurl, {
							pointer: 'omnivideo_admin_pointer',
							action: 'dismiss-wp-pointer'
						});
					}
				}).pointer('open');
			});
			//]]>
		</script>

		<?php
	}


	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any
	 * following ones if the same translation is present.
	 *
	 * @access public
	 * @return void
	 */
	public function load_plugin_textdomain() {
	  // Set filter for plugin's languages directory
	  $lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';

	  // Traditional WordPress plugin locale filter
	  $locale = apply_filters( 'plugin_locale',  get_locale(), $this->textdomain );
	  $mofile = sprintf( '%1$s-%2$s.mo', $this->textdomain, $locale );

	  // Setup paths to current locale file
	  $mofile_local  = $lang_dir . $mofile;
	  $mofile_global = WP_LANG_DIR . '/' . $this->textdomain . '/' . $mofile;

	  if ( file_exists( $mofile_global ) ) {
	    // Look in global /wp-content/languages/wc-extend-plugin-name/ folder
	    load_textdomain( $this->textdomain, $mofile_global );
	  }
	  elseif ( file_exists( $mofile_local ) ) {
	    // Look in local /wp-content/plugins/wc-extend-plugin-name/languages/ folder
	    load_textdomain( $this->textdomain, $mofile_local );
	  }
	  else {
	    // Load the default language files
	    load_plugin_textdomain( $this->textdomain, false, $lang_dir );
	  }
	}
}

$colabs_omnivideo = new Colabs_OmniVideo();