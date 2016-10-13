<?php
/**
 * Library for getting post images and attachments the WDS way.
 *
 * We always want the post image or an attachment somewhere in our code,
 * but WordPress doesn't, by default, have a fallback that supplies some
 * kind of image (a placeholder).
 *
 * This library allows you to get a post's featured image (or the first image
 * we can find in the post) or a media file and always have a fallback to
 * a placeholder (which you can define using the customizer).
 *
 * It also offers up some handy tools you can use like resizing images, etc.

 */
class WDS_Image {

	/**
	 * The default placeholder image size (when one is not specified).
	 *
	 * @var string
	 */
	private $default_size_placeholder = 'full';

	/**
	 * The default image size (when one is not specified).
	 *
	 * @var string
	 */
	private $default_size_of_image = 'full';

	/**
	 * Bootup!
	 */
	function __construct() {

		// Where we can set the placeholder.
		add_action( 'customize_register', array( $this, 'image_placeholder_customizer' ) );
	}

	/**
	 * Figures out if the size requested is a WP named size like 'large'.
	 *
	 * @param  mixed $size  The named size.
	 *
	 * @return boolean       True if it is a named size, false if not.
	 */
	public function is_wp_named_size( $size ) {
		if ( ! is_string( $size ) ) {
			return;
		}

		return ( 'medium' == $size || 'full' == $size || 'thumbnail' == $size || 'large' == $size );
	}

	/**
	 * Checks the variable as an acceptable size format.
	 *
	 * These formats are WP sizes: full, large, medium, thumbnail or
	 * a custom width/height, e.g:
	 *
	 *     array(
	 *         'width'  => 150,
	 *         'height' => 150,
	 *     )
	 *
	 * @param  array|string $size full|large|medium|thumbnail or array( 'width', 'height' ).
	 *
	 * @return boolean       True if it's WP format or acceptable array, false if not.
	 */
	public function is_acceptable_size_choice( $size ) {
		return ( is_array( $size ) && isset( $size['width'] ) && isset( $size['height'] ) ) || $this->is_wp_named_size( $size );
	}

	/**
	 * Sets the default image size.
	 *
	 * @param string|array $size full|large|medium|thumbnail or array( 'width', 'height' ).
	 */
	public function set_default_size_of_image( $size ) {
		if ( $this->is_acceptable_size_choice( $size ) ) {
			$this->default_size_of_image = $size;
		}
	}

	/**
	 * Sets the default placeholder size.
	 *
	 * @param string|array $size full|large|medium|thumbnail or array( 'width', 'height' ).
	 */
	public function set_default_size_placeholder( $size ) {
		if ( $this->is_acceptable_size_choice( $size ) ) {
			$this->default_size_placeholder = $size;
		}
	}

	/**
	 * Default arguments for the_image() and get_image_uri().
	 *
	 * @return array The default arguments for wp_parse_args().
	 */
	public function get_image_uri_arg_defaults() {

		// You can replace any of these when you call get_image_uri() or the_image().
		return array(
			'size'          => $this->default_size_of_image,
			'post_id'       => get_the_ID() ? get_the_ID() : get_queried_object_id(), // Use the post id if in the loop.
			'attachment_id' => false,
			'placeholder'   => $this->get_image_placeholder_uri( array(
				'size' => $this->default_size_placeholder,// Uses the full size or the set placeholder size.
			) ),
			'include_meta'  => false, // Don't include meta (makes it an Array).
			'meta_key'      => '', // Get the image from a post meta key.
			'pb_meta_data'  => array(), // Get the image from a Page Builder meta field.
			'default'       => false, // Allow one method for getting the image override others that might also exist.
		);
	}

	/**
	 * Gets the URI of the first image found in the post.
	 *
	 * @param  array $args               Arguments.
	 *
	 * @see get_image_uri_arg_defaults() Default arguments.
	 *
	 * @return string                    The URI.
	 */
	public function get_first_image_in_post_uri( $args ) {
		$args = wp_parse_args( $args, $this->get_image_uri_arg_defaults() );
		global $post;

		// If it's not even a post, we have nothing.
		if ( ! isset( $post->post_content ) ) {
			return false;
		}

		// Get the src of that first post.
		$image_in__post = preg_match_all( '/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches );
		$post_content_first_image_src = ( isset( $matches[1][0] ) ) ? $matches[1][0] : false;

		// If we have a first image.
		if ( $post_content_first_image_src ) {

			// Try and find the attachment since all we have is the src
			$attachment_id = attachment_url_to_postid( $post_content_first_image_src );

			if ( $attachment_id ) {

				// If it's an attachment, return the right size.
				$media = wp_get_attachment_image_src( $attachment_id, $args['size'] );

				if ( is_array( $media ) ) {

					// Give back the src of the right size.
					return current( $media );
				}

				// If it's not an attachment in WP then it's some other image we can't modify.
			} else {
				return $post_content_first_image_src;
			}
		}

		return false;
	}

	/**
	 * Get the post image, and use a placeholder if there isn't one.
	 *
	 * @param  string $size                The size of the images/placeholder.
	 *
	 * @see  get_image_uri_arg_defaults()  Argument defaults.
	 *
	 * @return string                      The URI of the image or false if nothing is found.
	 */
	public function get_image_uri( $args = array() ) {
		$args = wp_parse_args( $args, $this->get_image_uri_arg_defaults() );
		$media = false;

		// If a value was passed to the default parameter to specify a type of image.
		if ( $args['default'] ) {
			$media = $this->get_priority_image( $args );
		} // Try getting an image from an attachment ID that was passed.
		elseif ( $args['attachment_id'] ) {

			$media = $this->get_image_from_attachment_id( $args['attachment_id'], $args['size'] );

		} // Try getting an image from a featured image.
		elseif ( has_post_thumbnail( $args['post_id'] ) ) {

			$media = $this->get_featured_image( $args['post_id'], $args['size'] );

		} // Try getting an image from a custom meta key that was passed.
		elseif ( $args['meta_key'] ) {

			$media = $this->get_image_from_meta( $args['post_id'], $args['meta_key'], $args['size'] );

		} // Try getting an image from a Page Builder meta key that was passed.
		elseif ( ! empty( $args['pb_meta_data'] ) ) {

			$media = $this->get_image_from_pagebuilder_data( $args['pb_meta_data'], $args['size'] );

		}

		// If we found an image in one of those, return it.
		if ( $media ) {
			return $media;
		}

		// If there is an image in the post, use that!
		$first_image_in_post_uri = $this->get_first_image_in_post_uri( $args );
		if ( $first_image_in_post_uri ) {
			return $first_image_in_post_uri;
		}

		// If we don't have a post image or an image in the post to use, try attachments with post_id.
		$attachment_or_placeholder_uri = $this->get_attachment_uri_or_placeholder_uri( $args );
		if ( $attachment_or_placeholder_uri ) {
			return $attachment_or_placeholder_uri;
		}

		// There's no post image, no image in the post, and no attachment with post_id.
		return false;
	}

	/**
	 * Allow a particular method for getting the image to take priority over others.
	 * @param  array $args The arguments array for the image.
	 * @return string      The URL to the image, if it matches one of the cases.
	 */
	public function get_priority_image( $args ) {
		$args = wp_parse_args( $args, $this->get_image_uri_arg_defaults() );

		$media = false;
		switch ( $args['default'] ) {
			case 'featured' :
				$media = $this->get_featured_image( $args['post_id'], $args['size'] );

			case 'meta_key' :
				$media = $this->get_image_from_meta( $args['post_id'], $args['meta_key'], $args['size'] );

			case 'pb_meta_data' :
				$media = $this->get_image_from_pagebuilder_data( $args['pb_meta_data'], $args['size'] );

			default :
				$media = $this->get_featured_image( $args['post_id'], $args['size'] );
				break;
		}

		if ( $media ) {
			return $media;
		}

		return false;

	}

	/**
	 * Get an image from a post meta key (e.g. a CMB2 file upload field).
	 * @param  int    $post_id  The post ID.
	 * @param  string $meta_key The custom post meta key.
	 * @param  string $size     The size of the image desired.
	 * @return string           The URL of the image, if it exists.
	 */
	public function get_image_from_meta( $post_id, $meta_key, $size ) {
		$media = false;

		// We can pass an array of meta keys. Loop through the array and return the first one with a valid URL.
		if ( is_array( $meta_key ) ) {
			foreach ( $meta_key as $meta_key ) {

				$url = esc_url( get_post_meta( $post_id, $meta_key, true ) );

				// Make sure the post meta is an actual image and return a smaller version.
				if ( $url && $this->is_image_file( $url ) ) {
					$media = wp_get_attachment_image_src( $this->get_image_id( $url ), $size );
				}
			}
		} else {

			// If we just have a single meta key, check and display that.
			$url = esc_url( get_post_meta( $post_id, $meta_key, true ) );

			// Make sure this is actually an image.
			if ( $url && $this->is_image_file( $url ) ) {
				$media = wp_get_attachment_image_src( $this->get_image_id( $url ), $size );
			}
		}

		if ( is_array( $media ) ) {
			return current( $media );
		}

		return false;
	}

	/**
	 * Get an image from the attachment ID.
	 * @param  int    $attachment_id The attachment ID of the image.
	 * @param  string $size          The size of the image desired.
	 * @return string                The URL of the image, if it exists.
	 */
	public function get_image_from_attachment_id( $attachment_id, $size ) {
		$media = wp_get_attachment_image_src( absint( $attachment_id ), $size );
		if ( is_array( $media ) ) {
			return current( $media );
		}

		return false;
	}

	/**
	 * Get an image from the featured image.
	 * @param  int    $post_id The post ID.
	 * @param  string $size    The size of the image desired.
	 * @return string          The URL of the image, if it exists.
	 */
	public function get_featured_image( $post_id, $size ) {
		$featured_image_id = get_post_thumbnail_id( $post_id );
		$media = wp_get_attachment_image_src( $featured_image_id, $size );
		if ( is_array( $media ) ) {
			return current( $media );
		}

		return false;
	}

	/**
	 * Get an image from custom Page Builder template data.
	 * @param  array  $args An array of Page Builder data to pass to wds_page_builder_get_part_data.
	 * @param  string $size The size of the image desired.
	 * @return string       The URL of the image, if it exists.
	 */
	public function get_image_from_pagebuilder_data( $args, $size ) {
		// Get the passed data and set up some empty defaults.
		$pb_args = wp_parse_args( $args, array(
			'part'     => false,
			'meta_key' => false,
			'post_id'  => 0,
			'area'     => '',
		) );

		$media = false;

		// We need all three of these to be set to a non-false value.
		if ( $pb_args['part'] && $pb_args['meta_key'] && $pb_args['post_id'] && $pb_args['area'] ) {

			// We'll assume this is a URL first. First get the data so we can analyze it.
			$url = wds_page_builder_get_part_data( $pb_args['part'], $pb_args['meta_key'], $pb_args['post_id'], $pb_args['area'] );

			// Check if it's a URL. If it is, get the ID for that URL and return a resized version of the image.
			if ( $url && $this->is_image_file( $url ) ) {
				$media = wp_get_attachment_image_src( $this->get_image_id( $url ), $size );

			} else {
				// If it wasn't a URL, we'll assume that an ID was passed.
				$id = wds_page_builder_get_part_data( $pb_args['part'], $pb_args['meta_key'], $pb_args['post_id'], $pb_args['area'] );

				// Following that assumption, let's get a resized version of that image.
				$media = wp_get_attachment_image_src( absint( $id ), $size );
			}

			if ( is_array( $media ) ) {
				return current( $media );
			}
		}

		return false;
	}

	/**
	 * Get an image ID from the URL.
	 * @param  string $image_url Full URL of the image file.
	 * @return int               The image ID.
	 * @link   https://pippinsplugins.com/retrieve-attachment-id-from-image-url/
	 */
	public function get_image_id( $image_url ) {
		global $wpdb;
		$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid='%s';", $image_url ) );
	        return $attachment[0];
	}

	/**
	 * Check if a given URL is an image file.
	 * @param  string $url_to_file URL to the image.
	 * @param  array  $file_types  Optional. Allowed file types.
	 * @return boolean
	 */
	public function is_image_file( $url_to_file = '', $file_types = array() ) {
		// If no URL was passed, bail.
		if ( '' == $url_to_file ) {
			return false;
		}

		// Set up the allowed file types if none were passed.
		if ( empty( $file_types ) ) {
			$file_types = array(
				'jpg',
				'jpeg',
				'gif',
				'png',
			);
		}

		$media_ext = strtolower( pathinfo( basename( parse_url( $url_to_file, PHP_URL_PATH ) ), PATHINFO_EXTENSION ) );

		if ( $media_ext && in_array( $media_ext, $file_types ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the attachment URI or pass back a placeholder.
	 *
	 * @param  array $args               Arguments.
	 *
	 * @see get_image_uri_arg_defaults() Default arguments.
	 *
	 * @return string|boolean            Placeholder URI.
	 */
	public function get_attachment_uri_or_placeholder_uri( $args ) {
		$args = wp_parse_args( $args, $this->get_image_uri_arg_defaults() );

		// If there wasn't a post image, let's get the attachment and see what's there.
		$media = wp_get_attachment_image_src( $args['post_id'], $args['size'] );

		// Set up default image path (the placeholder) just in case we don't find anything.
		$media_url = $this->get_image_placeholder_uri( $args['placeholder'] );

		// If what we have an attachment, use it's stuff.
		if ( is_array( $media ) && 0 < count( $media ) ) {

			// What if they want the meta (of the selected size).
			if ( $args['include_meta'] ) {
				$media_with_meta['src'] = $media_url;
				$media_with_meta['meta'] = $media;

				return $media_with_meta;
			} else {

				// Just pass back the src.
				return current( $media ); // The src attribute.
			}
		}

		return $media_url;
	}

	/**
	 * Outputs the post image or placeholder.
	 *
	 * @param  string $size              The desired size of the image.
	 *
	 * @see get_image_uri_arg_defaults() Default arguments.
	 */
	public function the_image( $args = array() ) {
		$args = wp_parse_args( $args, $this->get_image_uri_arg_defaults() );

		// Make sure they never set include_meta here as an array doesn't help us.
		$args['include_meta'] = false;

		echo '<img src="' . esc_url( $this->get_image_uri( $args ) ) . '" class="attachment-thumbnail wp-post-image" alt="' . esc_html( get_the_title( $args['post_id'] ) )  . '" />';
	}

	/**
	 * Allows us to set the placeholder image.
	 *
	 * @param  object $wp_customize The WP Customizer class.
	 */
	public function image_placeholder_customizer( $wp_customize ) {

		// Section.
		$wp_customize->add_section( 'image_placeholder', array(
			'title'       => __( 'Placeholders', 'clp' ),
			'description' => '',
			'priority'    => 50,
		));

		// Logo Setting.
		$wp_customize->add_setting( 'image_placeholder', array(

			// Note that this default carries if no placeholder is set in customizer.
			'default'     => get_stylesheet_directory_uri() . '/images/default-post-thumbnail.png',
			'capability'  => 'edit_theme_options',
			'type'        => 'theme_mod',
		));

		// Logo Setting (Control).
		$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'image_placeholder', array(
			'label'    => __( 'Placeholder Image', 'clp' ),
			'section'  => 'image_placeholder',
			'settings' => 'image_placeholder',
		) ) );

	}

	/**
	 * Gets the image placeholder at the size you want.
	 *
	 * @param  array $args               The Arguments.
	 *
	 * @see get_image_uri_arg_defaults() Default arguments.
	 *
	 * @return string                    The placeholder at the size you want.
	 */
	public function get_image_placeholder_uri( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'size' => 'thumbnail', // Use the smallest size
		) );

		// What's in the customizer (or default for it)?
		$mod_result = get_theme_mod( 'image_placeholder' );

		// Get the SRC stored in theme mod and get the attachment ID.
		$attachment_id = attachment_url_to_postid( $mod_result );

		// If no attachment id, we're using default.
		if ( ! $attachment_id ) {
			$filename = 'default-post-thumbnail.png';
			$src = get_stylesheet_directory_uri() . "/images/$filename";

			// Resize the image (though it's not an attachment) if full is not requested.
			if ( $args['size'] != 'full' ) {
				return $this->get_resize_image_uri( array(
					'src'      => get_stylesheet_directory() . "/images/$filename",
					'size'     => $args['size'],
					'filename' => $filename,
				) );
			}

			// Return the result.
			return $src;
		}

		// Now, get the size of the attachment we want!
		$src = current( wp_get_attachment_image_src( $attachment_id, $args['size'] ) );

		return $src;
	}

	/**
	 * Resizes an image and passes back the URI of that new image.
	 *
	 * @param array $args                Arguments.
	 *
	 * @see get_image_uri_arg_defaults() Default arguments.
	 *
	 * @return string                    If we can resize the image, the resize image URI, if not the original.
	 */
	public function get_resize_image_uri( $args = array() ) {

		// Arguments
		$args = wp_parse_args( $args, array(
			'src'      => false,
			'size'     => false,
			'filename' => false,
		) );

		// Set filename (it's easier)
		$filename = $args['filename'];

		// Can't work with defaults.
		if ( ! $args['src'] ) {
			return false;
		}

		// Must have width/height.
		if ( is_array( $args['size'] ) && ( ! isset( $args['size']['width'] ) && ! isset( $args['size']['height'] ) ) ) {
			return false;
		}

		// Resize it to match size.
		$resize = wp_get_image_editor( $args['src'] );

		// If we got an editor.
		if ( ! is_wp_error( $resize ) ) {

			// Get a place to write new file.
			$upload_dir = wp_upload_dir();

			// What's the file's prefix?
			if ( is_array( $args['size'] ) ) {

				// E.g. 100x100-filename.png
				$prefix = $args['size']['width'] . 'x' . $args['size']['height'];
			} else {

				// E.g. medium-filename.png
				$prefix = $args['size'];
			}

			// What is the new file called?
			$new_filename = '/' . $prefix . "-$filename";
			$new_file_path = $upload_dir['path'] . $new_filename;

			// What if the file already exists?
			if ( file_exists( $new_file_path ) ) {
				$src = $upload_dir['url'] . $new_filename;
			}

			// If we didn't pass width/height as array.
			if ( ! is_array( $args['size'] ) ) {

				// Get the size of what was asked for (thumbnail, medium, large, full).
				$sizes = $this->get_wp_size_options();

				// Custom height/width.
			} elseif ( isset( $args['size']['width'] ) && isset( $args['size']['height'] ) ) {
				$sizes = array(
					'custom' => array(
						'width' => $args['size']['width'],
						'height' => $args['size']['height'],
					),
				);

				// Use our new custom size.
				$args['size'] = 'custom';

				// If we're using a named size like 'large' or 'medium'.
			} else {

				// If we don't know what's going on, return the original.
				return $args['src'];
			}

			// If we know what sizes to use...
			// Resize to what they asked for (using crop).
			$resize->resize( $sizes[ $args['size'] ]['width'], $sizes[ $args['size'] ]['height'], true );

			// Write new resized file.
			$resize->save( $new_file_path );

			// Use the new file.
			return $upload_dir['url'] . $new_filename;

			// If the editor chokes, just give back full.
		} else {
			return $args['src'];
		}

		return $args['src'];
	}

	public function get_wp_size_options() {
		return  array(
			'thumbnail' => array(
				'width' => get_option( 'thumbnail_size_w' ),
				'height' => get_option( 'thumbnail_size_h' ),
			),
			'medium' => array(
				'width' => get_option( 'medium_size_w' ),
				'height' => get_option( 'medium_size_h' ),
			),
			'large' => array(
				'width' => get_option( 'large_size_w' ),
				'height' => get_option( 'large_size_h' ),
			),
		);
	}
}

// Global instance our class.
$wds_image = new WDS_Image();

/**
 * Wrapper functions/template tags for public functions.
 * =====================================================
 */

function wds_get_attachment_uri_or_placeholder_uri( $args ) {
	global $wds_image;
	return $wds_image->get_attachment_uri_or_placeholder_uri( $args ); }
function wds_get_first_image_in_post_uri( $args ) {
	global $wds_image;
	return $wds_image->get_first_image_in_post_uri( $args ); }
function wds_get_image_placeholder_uri( $args = array() ) {
	global $wds_image;
	return $wds_image->get_image_placeholder_uri( $args ); }
function wds_get_image_uri( $args = array() ) {
	global $wds_image;
	return $wds_image->get_image_uri( $args ); }
function wds_get_image_uri_arg_defaults() {
	global $wds_image;
	return $wds_image->get_image_uri_arg_defaults(); }
function wds_get_resize_image_uri( $args = array() ) {
	global $wds_image;
	return $wds_image->get_resize_image_uri( $args ); }
function wds_get_wp_size_options() {
	global $wds_image;
	return $wds_image->get_wp_size_options(); }
function wds_image_placeholder_customizer( $wp_customize ) {
	global $wds_image;
	return $wds_image->image_placeholder_customizer( $wp_customize ); }
function wds_is_acceptable_size_choice( $size ) {
	global $wds_image;
	return $wds_image->is_acceptable_size_choice( $size ); }
function wds_is_wp_named_size( $size ) {
	global $wds_image;
	return $wds_image->is_wp_named_size( $size ); }
function wds_set_default_size_of_image( $size ) {
	global $wds_image;
	return $wds_image->set_default_size_of_image( $size ); }
function wds_set_default_size_placeholder( $size ) {
	global $wds_image;
	return $wds_image->set_default_size_placeholder( $size ); }
function wds_the_image( $args = array() ) {
	global $wds_image;
	return $wds_image->the_image( $args ); }

// ** We could do this, but I can't get this to work **
// =============================================================
// $class = new ReflectionClass( 'WDS_Image' );
// $methods = $class->getMethods( ReflectionMethod::IS_PUBLIC );
// foreach( $methods as $function ) {
// if ( '__construct' != $function->name ) {
// $function_name = $function->name;
// $function = "function wds_$function_name( " . '$args = array()' . " ) { global " . '$wds_image' . "; return " . '$wds_image' . "->$function_name( " . '$args' . " ); }";
// eval( $function );
// }
// }
