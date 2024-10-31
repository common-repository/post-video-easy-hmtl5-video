<?php
/*
Plugin Name: Post Video
Plugin URI: http://www.yourwebsiteforless.com/wordpress-plugins/
Description: A plugin for easily embedding an HTML5 video into a post/page
Version: 1.1.1
Author: Jason Miesionczek, CJ Gaul
Author URI: http://atmospherian.wordpress.com
License: GPL2
*/

/*  Copyright 2010  Jason Miesionczek  (email : atmospherian@gmail.com)

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

if ( !class_exists('myCustomFields') ) {

	class myCustomFields {
		/**
		* @var  string  $prefix  The prefix for storing custom fields in the postmeta table
		*/
		var $prefix = '_mcf_';
		/**
		* @var  array  $postTypes  An array of public custom post types, plus the standard "post" and "page" - add the custom types you want to include here
		*/
		var $postTypes = array( "page", "post" );
		/**
		* @var  array  $customFields  Defines the custom fields available
		*/

                /*
                 * fields needed:
                 * mobile width
                 * mobile height
                 * mobile video url (mp4)
                 * mobile image url
                 * desktop width
                 * desktop height
                 * desktop video url (mp4)
                 * desktop flash url
                 * desktop image url
                 *
                 */

		var $customFields =	array(
                        array(
				"name"			=> "auto-play",
				"title"			=> "Instructions: Fill out the details below and then add the video shortcode into the content area above where you want the video to be embeded. e.g. [video] <br><Br><br>Auto Play",
				"description"	=> "Indicate that the video should start playing as soon as it is loaded",
				"type"			=> "checkbox",
				"scope"			=>	array( "page","post"),
				"capability"	=> "edit_posts"
			),
			array(
				"name"			=> "mobile-width",
				"title"			=> "Mobile Video Width",
				"description"	=> "The width of the mobile version of the video",
				"type"			=> "text",
				"scope"			=>	array( "page","post"),
				"capability"	=> "edit_posts"
			),
                        array(
				"name"			=> "mobile-height",
				"title"			=> "Mobile Video Height",
				"description"	=> "The height of the mobile version of the video",
				"type"			=> "text",
				"scope"			=>	array( "page","post"),
				"capability"	=> "edit_posts"
			),
                        array(
				"name"			=> "mobile-video-url",
				"title"			=> "Mobile Video URL",
				"description"	=> "The URL to the video file to be embedded (MP4 format)",
				"type"			=> "text",
				"scope"			=>	array( "page","post"),
				"capability"	=> "edit_posts"
			),
                        array(
				"name"			=> "mobile-video-image",
				"title"			=> "Mobile Video Image URL",
				"description"	=> "The URL of the image to display on an unsupported mobile device",
				"type"			=> "text",
				"scope"			=>	array( "page","post"),
				"capability"	=> "edit_posts"
			),
                        array(
				"name"			=> "desktop-width",
				"title"			=> "Desktop Video Width",
				"description"	=> "The width of the desktop version of the video",
				"type"			=> "text",
				"scope"			=>	array( "page","post"),
				"capability"	=> "edit_posts"
			),
                        array(
				"name"			=> "desktop-height",
				"title"			=> "Desktop Video Height",
				"description"	=> "The height of the desktop version of the video",
				"type"			=> "text",
				"scope"			=>	array( "page","post"),
				"capability"	=> "edit_posts"
			),
                        array(
				"name"			=> "desktop-video-url",
				"title"			=> "Desktop Video URL",
				"description"	=> "The URL of the full size video to be embedded (MP4 format)",
				"type"			=> "text",
				"scope"			=>	array( "page","post"),
				"capability"	=> "edit_posts"
			),
                        array(
				"name"			=> "desktop-flash-url",
				"title"			=> "Desktop Flash Video URL",
				"description"	=> "The URL to a Flash version of the video as a fallback",
				"type"			=> "text",
				"scope"			=>	array( "page","post"),
				"capability"	=> "edit_posts"
			),
                        array(
				"name"			=> "desktop-video-image",
				"title"			=> "Desktop Video Image URL",
				"description"	=> "The URL of the image to display in a browser that does not support HTML5 or Flash",
				"type"			=> "text",
				"scope"			=>	array( "page","post"),
				"capability"	=> "edit_posts"
			),
			
		);
		/**
		* PHP 4 Compatible Constructor
		*/
		function myCustomFields() { $this->__construct(); }
		/**
		* PHP 5 Constructor
		*/
		function __construct() {
			add_action( 'admin_menu', array( &$this, 'createCustomFields' ) );
			add_action( 'save_post', array( &$this, 'saveCustomFields' ), 1, 2 );
			// Comment this line out if you want to keep default custom fields meta box
			add_action( 'do_meta_boxes', array( &$this, 'removeDefaultCustomFields' ), 10, 3 );
		}
		/**
		* Remove the default Custom Fields meta box
		*/
		function removeDefaultCustomFields( $type, $context, $post ) {
			foreach ( array( 'normal', 'advanced', 'side' ) as $context ) {
				foreach ( $this->postTypes as $postType ) {
					remove_meta_box( 'postcustom', $postType, $context );
				}
			}
		}
		/**
		* Create the new Custom Fields meta box
		*/
		function createCustomFields() {
			if ( function_exists( 'add_meta_box' ) ) {
				foreach ( $this->postTypes as $postType ) {
					add_meta_box( 'my-custom-fields', 'Embedded Video Settings', array( &$this, 'displayCustomFields' ), $postType, 'normal', 'high' );
				}
			}
		}
		/**
		* Display the new Custom Fields meta box
		*/
		function displayCustomFields() {
			global $post;
			?>
			<div class="form-wrap">
				<?php
				wp_nonce_field( 'my-custom-fields', 'my-custom-fields_wpnonce', false, true );
				foreach ( $this->customFields as $customField ) {
					// Check scope
					$scope = $customField[ 'scope' ];
					$output = false;
					foreach ( $scope as $scopeItem ) {
						switch ( $scopeItem ) {
							default: {
								if ( $post->post_type == $scopeItem )
									$output = true;
								break;
							}
						}
						if ( $output ) break;
					}
					// Check capability
					if ( !current_user_can( $customField['capability'], $post->ID ) )
						$output = false;
					// Output if allowed
					if ( $output ) { ?>
						<div class="form-field form-required">
							<?php
							switch ( $customField[ 'type' ] ) {
								case "checkbox": {
									// Checkbox
									echo '<label for="' . $this->prefix . $customField[ 'name' ] .'" style="display:inline;"><b>' . $customField[ 'title' ] . '</b></label>&nbsp;&nbsp;';
									echo '<input type="checkbox" name="' . $this->prefix . $customField['name'] . '" id="' . $this->prefix . $customField['name'] . '" value="yes"';
									if ( get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) == "yes" )
										echo ' checked="checked"';
									echo '" style="width: auto;" />';
									break;
								}
								case "textarea":
								case "wysiwyg": {
									// Text area
									echo '<label for="' . $this->prefix . $customField[ 'name' ] .'"><b>' . $customField[ 'title' ] . '</b></label>';
									echo '<textarea name="' . $this->prefix . $customField[ 'name' ] . '" id="' . $this->prefix . $customField[ 'name' ] . '" columns="30" rows="3">' . htmlspecialchars( get_post_meta( $post->ID, $this->prefix . $customField[ 'name' ], true ) ) . '</textarea>';
									// WYSIWYG
									if ( $customField[ 'type' ] == "wysiwyg" ) { ?>
										<script type="text/javascript">
											jQuery( document ).ready( function() {
												jQuery( "<?php echo $this->prefix . $customField[ 'name' ]; ?>" ).addClass( "mceEditor" );
												if ( typeof( tinyMCE ) == "object" && typeof( tinyMCE.execCommand ) == "function" ) {
													tinyMCE.execCommand( "mceAddControl", false, "<?php echo $this->prefix . $customField[ 'name' ]; ?>" );
												}
											});
										</script>
									<?php }
									break;
								}
								default: {
									// Plain text field
									echo '<label for="' . $this->prefix . $customField[ 'name' ] .'"><b>' . $customField[ 'title' ] . '</b></label>';
									echo '<input type="text" name="' . $this->prefix . $customField[ 'name' ] . '" id="' . $this->prefix . $customField[ 'name' ] . '" value="' . htmlspecialchars( get_post_meta( $post->ID, $this->prefix . $customField[ 'name' ], true ) ) . '" />';
									break;
								}
							}
							?>
							<?php if ( $customField[ 'description' ] ) echo '<p>' . $customField[ 'description' ] . '</p>'; ?>
						</div>
					<?php
					}
				} ?>
			</div>
			<?php
		}
		/**
		* Save the new Custom Fields values
		*/
		function saveCustomFields( $post_id, $post ) {
			if ( !isset( $_POST[ 'my-custom-fields_wpnonce' ] ) || !wp_verify_nonce( $_POST[ 'my-custom-fields_wpnonce' ], 'my-custom-fields' ) )
				return;
			if ( !current_user_can( 'edit_post', $post_id ) )
				return;
			if ( ! in_array( $post->post_type, $this->postTypes ) )
				return;
			foreach ( $this->customFields as $customField ) {
				if ( current_user_can( $customField['capability'], $post_id ) ) {
					if ( isset( $_POST[ $this->prefix . $customField['name'] ] ) && trim( $_POST[ $this->prefix . $customField['name'] ] ) ) {
						$value = $_POST[ $this->prefix . $customField['name'] ];
						// Auto-paragraphs for any WYSIWYG
						if ( $customField['type'] == "wysiwyg" ) $value = wpautop( $value );
						update_post_meta( $post_id, $this->prefix . $customField[ 'name' ], $value );
					} else {
						delete_post_meta( $post_id, $this->prefix . $customField[ 'name' ] );
					}
				}
			}
		}

	} // End Class

} // End if class exists statement

function video_embed_handler($atts) {
    global $post;

    $mobile_width = get_post_meta($post->ID, "_mcf_mobile-width", true);
    $mobile_height = get_post_meta($post->ID, "_mcf_mobile-height", true);
    $mobile_video_url = get_post_meta($post->ID, "_mcf_mobile-video-url", true);
    $mobile_image_url = get_post_meta($post->ID, "_mcf_mobile-video-image", true);

    $desktop_width = get_post_meta($post->ID, "_mcf_desktop-width", true);
    $desktop_height = get_post_meta($post->ID, "_mcf_desktop-height", true);
    $desktop_video_url = get_post_meta($post->ID, "_mcf_desktop-video-url", true);
    $desktop_flash_url = get_post_meta($post->ID, "_mcf_desktop-flash-url", true);
    $desktop_image_url = get_post_meta($post->ID, "_mcf_desktop-video-image", true);

    $auto_play = get_post_meta($post->ID, "_mcf_auto-play", true);

    $plugin_url = WP_PLUGIN_URL.'/'.plugin_basename(dirname(__FILE__));

    $output = "";

    $output .= "<script type=\"text/javascript\" src=\"". $plugin_url. "/js/html5.js\"></script>";
    $output .= "<div id=\"embedded_video\">";
    $output .= "<script type=\"text/javascript\">";
    $output .= "var video = new HTML5Video();";
    $output .= "video.init({";
    $output .= "useFlashFirst:false,";
    if ($auto_play == "yes") {
        $output .= "mobileVideoObject: new HTML5VideoObject(" . $mobile_width . "," . $mobile_height. ", {\"autoplay\":\"autoplay\",\"autobuffer\":\"autobuffer\",\"controls\":\"controls\"}),";
    } else {
        $output .= "mobileVideoObject: new HTML5VideoObject(" . $mobile_width . "," . $mobile_height. ", {\"autobuffer\":\"autobuffer\",\"controls\":\"controls\"}),";
    }
    $output .= "mobileH264: new HTML5VideoAsset(\"" . $mobile_video_url . "\", \"video/mp4\"),";
    $output .= "mobilePosterImage: new HTML5VideoImage(\"" . $mobile_image_url . "\", ". $mobile_width. ", ".$mobile_height.", \"telematics\", \"No video playback capabilities.\"),";
    $output .= "desktopVideoObject: new HTML5VideoObject(" . $desktop_width . "," . $desktop_height. ", {\"autobuffer\":\"autobuffer\",\"controls\":\"controls\"}),";
    $output .= "desktopH264: new HTML5VideoAsset(\"" . $desktop_video_url . "\", \"video/mp4\"),";
    $output .= "desktopFlashObject: new HTML5VideoFlashObject(\"". $plugin_url. "/player.swf\", ".$desktop_width.",".$desktop_height.", {\"allowFullScreen\":\"true\", \"flashvars\":\"file=".$desktop_flash_url."&image=".$desktop_image_url."\"}),";
    $output .= "desktopPosterImage: new HTML5VideoImage(\"" . $desktop_image_url . "\", ". $desktop_width. ", ".$desktop_height.", \"telematics\", \"No video playback capabilities.\")";
    $output .= "});";
    $output .= "video.detect();";
    $output .= "</script></div>";

    return $output;
}

add_shortcode('video','video_embed_handler');


// Instantiate the class
if ( class_exists('myCustomFields') ) {
	$myCustomFields_var = new myCustomFields();
}

?>