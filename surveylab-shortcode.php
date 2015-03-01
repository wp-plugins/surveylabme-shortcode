<?php
/**
 * Plugin Name: SurveyLab.me Shortcode Plugin
 * Plugin URI: https://surveylab.me
 * Description: SurveyLab.me Shortcode offers an easy way to integrate and embed social survey, opinions, polls and questions created with SurveyLab.me service.
 * Version: 1.1
 * Author: SurveyLab
 * Author URI: https://surveylab.me
 * Network: Optional. Whether the plugin can only be activated network wide. Example: true
 * License: GPL3
 */

/*  Copyright 2015  SurveyLab  (email : hello@surveylab.me)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class SurveyLab_Shortcode_Plugin {

	const SHORTCODE_SLUG = 'surveylab';
	const DEFAULT_EMEBED_VERSION = '0';
	const SURVEYLAB_SURVEY_URL_PATTERN = '#http(s?)://(www\.)?surveylab\.me/survey/[0-9a-zA-Z\-]*-(\d+)+/#i';

	/**
	 * @var SurveyLab_Shortcode_Plugin
	 */
	static protected $instance;

	/**
	 * @return SurveyLab_Shortcode_Plugin
	 */
	static public function instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Add hooks for both shortcode and autodetect links
	 */
	protected function __construct() {
		// Add hook to shortcode
		add_shortcode( self::SHORTCODE_SLUG, array( $this, 'wp_sl_shortcode_handler' ) );
		
		// Add hook to autodetect url and replace with embedding
		wp_embed_register_handler( self::SHORTCODE_SLUG, self::SURVEYLAB_SURVEY_URL_PATTERN, array( $this, 'wp_sl_embed_handler' ), 1 );
		
		// Add hoock to the_posts and invoke our conditionally_enqueue_script function on it
		add_action( 'the_posts', array( $this, 'conditionally_enqueue_script' ) );

		// Enable shortcode support for sidebar widget
		add_filter('widget_text', 'do_shortcode');

		//Enable TinyMCE (default wordpress WYSIWUG editor) shortocode button
		add_action('admin_init', array($this, 'sl_setup_tinymce_button'));
	}

	function sl_setup_tinymce_button() {
		// check user permissions
	    if ( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) {
	        return;
	    }

	    // check if WYSIWYG is enabled
	    if ( 'true' == get_user_option( 'rich_editing' ) ) {
			add_filter( 'mce_external_plugins', array($this, 'sl_register_tinymce_javascript') );
			// Add to line 1 form WP TinyMCE
			add_filter( 'mce_buttons', array($this, 'sl_tinymce_register_buttons') );

			//add_filter( 'mce_buttons_2', array($this, 'sl_tinymce_register_buttons_2') );
		}
	}

	function sl_tinymce_register_buttons($buttons) {
	   array_push($buttons, 'surveylab_add_survey');
	   return $buttons;
	}

	function sl_tinymce_register_buttons_2( $buttons ) {
	    return $buttons;
	}

	function sl_register_tinymce_javascript( $plugin_array ) {
        $plugin_array['surveylab'] = plugins_url('/js/surveylab-plugin.js', __FILE__);
   		return $plugin_array;
	}

	/**
	 * Construct a SurveyLab embed from an ID and embed version
	 *
	 * @param $id
	 * @return string
	 */
	function construct_embed( $survey_id, $embed_version ) {

		$survey_id = str_replace("/", "", $survey_id);

		$embed = sprintf(
			"<noscript><a href='%1\$s'>%2\$s</a></noscript><div class='surveylab_widget_v%3\$s' style='max-width:100%; margin: 0 auto' id='%4\$s'></div>",
			esc_url( 'https://surveylab.me/social/survey/' . $survey_id . '/' ),
			esc_html__( 'Compile this survey!', 'surveylab-shortcode' ),
			esc_attr( $embed_version ),
			esc_attr( $survey_id )
		);
		return $embed;
		//return "<noscript><a href='https://surveylab.me/social/survey/" . $id . "/'>Compile <b>Grillo a Bruxelles lancia referendum anti-euro: vogliamo riprenderci la sovranità della nostra moneta. Sei d'accordo?</b></a></noscript><script src='https://surveylab.me/widget/widget.js?v=" . $embed_version . "' type='text/javascript'></script><div class='surveylab_widget_v" . $embed_version . "' style='max-width:100%; margin: 0 auto' id='" . $id . "'></div>";
	}

	/**
	 * Shortcode handler for [surveylab id="123456"]
	 *
	 * @param array $attrs
	 *
	 * @return string
	 */
	function wp_sl_shortcode_handler( $attrs ) {
		$attrs = shortcode_atts(
			array(
				'id' => "123456",
			),
			$attrs,
			self::SHORTCODE_SLUG
		);

		return $this->construct_embed( $attrs['id'], self::DEFAULT_EMEBED_VERSION );
	}

	/**
	 * Embed handler for Wedgie
	 *
	 * @param array $matches
	 * @param array $attr
	 * @param string $url
	 * @param string $rawattr
	 *
	 * @return mixed|void
	 */
	function wp_sl_embed_handler( $matches, $attr, $url, $rawattr ) {
		$embed = $this->construct_embed( $matches[3], self::DEFAULT_EMEBED_VERSION );
		return apply_filters( 'embed_surveylab', $embed, $matches, $attr, $url, $rawattr );
	}

	/**
	 * wp_enqueue_scripts hook.
	 */
	public function enqueue_script( $embed_version ) {
		if( $embed_version == null ) {
			$embed_version = self::DEFAULT_EMEBED_VERSION;
		}
		wp_enqueue_script( 'surveylab_embed', 'https://surveylab.me/widget/widget.js?v=' . $embed_version, null, null );
	}


	/**
	 * Hook handler for the_posts to enqueue the JS if a wedgie is found.
	 *
	 * @param array $posts
	 *
	 * @return mixed
	 */
	function conditionally_enqueue_script( $posts ) {
		if ( empty( $posts ) ) {
			return $posts;
		}

		$shortcode_found = false;

		foreach ( $posts as $post ) {
			if ( ! ( false === stripos( $post->post_content, '[' . self::SHORTCODE_SLUG ) ) || preg_match( self::SURVEYLAB_SURVEY_URL_PATTERN, $post->post_content ) ) {
				$shortcode_found = true;
				break;
			}
		}

		if ( $shortcode_found ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ) );
		}

		return $posts;
	}

}

SurveyLab_Shortcode_Plugin::instance();