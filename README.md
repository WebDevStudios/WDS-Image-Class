# 
      ___       __    ________   ________
     |\  \     |\  \ |\   ___ \ |\   ____\
     \ \  \    \ \  \\ \  \_|\ \\ \  \___|_
      \ \  \  __\ \  \\ \  \ \\ \\ \_____  \
       \ \  \|\__\_\  \\ \  \_\\ \\|____|\  \
        \ \____________\\ \_______\ ____\_\  \
         \|____________| \|_______||\_________\
                                   \|_________|
      ___   _____ ______    ________   ________   _______
     |\  \ |\   _ \  _   \ |\   __  \ |\   ____\ |\  ___ \
     \ \  \\ \  \\\__\ \  \\ \  \|\  \\ \  \___| \ \   __/|
      \ \  \\ \  \\|__| \  \\ \   __  \\ \  \  ___\ \  \_|/__
       \ \  \\ \  \    \ \  \\ \  \ \  \\ \  \|\  \\ \  \_|\ \
        \ \__\\ \__\    \ \__\\ \__\ \__\\ \_______\\ \_______\
         \|__| \|__|     \|__| \|__|\|__| \|_______| \|_______|
      ________   ___        ________   ________    ________
     |\   ____\ |\  \      |\   __  \ |\   ____\  |\   ____\
     \ \  \___| \ \  \     \ \  \|\  \\ \  \___|_ \ \  \___|_
      \ \  \     \ \  \     \ \   __  \\ \_____  \ \ \_____  \
       \ \  \____ \ \  \____ \ \  \ \  \\|____|\  \ \|____|\  \
        \ \_______\\ \_______\\ \__\ \__\ ____\_\  \  ____\_\  \
         \|_______| \|_______| \|__|\|__||\_________\|\_________\
                                         \|_________|\|_________|

       Library for getting post images and attachments the WDS way.

**Contributors:** [aubreypwd](https://github.com/aubreypwd), [jazzsequence](https://github.com/jazzsequence), [bradp](https://github.com/bradp), **YOU!!** [Submit a pull request](https://github.com/WebDevStudios/WDS-Image-Class/pulls)

## Overview

We always want the post image or an attachment somewhere in our code, but WordPress doesn't, by default, have a fallback that supplies some kind of image (a placeholder).

This library allows you to get a post's featured image (or the first image we can find in the post) or a media file and always have a fallback to a placeholder (which you can define using the customizer).

It also offers up some handy tools you can use like resizing images, etc.

### Usage

Include this file in your theme/plugin, e.g. in the `/includes` folder. For example:

```
require_once( 'includes/class-wds-image.php' );
```

### Handy Functions ###

####Get the attachment URI or pass back a placeholder.

```php
// Default arguments.
$args = array(
	'size'          => $this->default_size_of_image,
	'post_id'       => get_the_ID() ? get_the_ID() : get_queried_object_id(), // Use the post id if in the loop.
	'attachment_id' => false,
	'placeholder'   => $this->get_image_placeholder_uri( array(
		'size' => $this->default_size_placeholder  // Uses the full size or the set placeholder size.
	) ),
	'include_meta'  => false, // Don't include meta (makes it an Array).
	'meta_key'      => '', // Get the image from a post meta key.
	'pb_meta_data'  => array(), // Get the image from a Page Builder meta field.
	'default'       => false, // Allow one method for getting the image override others that might also exist.
);

wds_get_attachment_uri_or_placeholder_uri( $args );
```

####Return the default arguments (see `$args` array above).

```php
wds_get_image_uri_arg_defaults();
```

####Gets the URI of the first image found in the post.

```php
wds_get_first_image_in_post_uri( $args );
```

####Get the post image, and use a placeholder if there isn't one.

```php
wds_get_image_uri( $args );
```

####Gets the image placeholder at the size you want.

```php
wds_get_image_placeholder_uri( $args );
```

####Resizes an image and passes back the URI of that new image.

```php
wds_get_resize_image_uri( $args );
```

####Get information about the default image sizes.

```php
wds_get_wp_size_options();
```


####Sets the placeholder image in the WP Customizer.

```php
wds_image_placeholder_customizer( $wp_customizer );
```

####Checks the variable as an acceptable size format.
These formats are WP sizes: full, large, medium, thumbnail or a custom width/height, e.g:

```php
 // e.g.:
 // $size = array(
 //	'width'  => 150,
 //	'height' => 150,
 // );
 //
 // or:
 //
 // $size = 'thumbnail';

wds_is_acceptable_size_choice( $size );
```

####Figures out if the size requested is a WP named size like 'large'.

```php
wds_is_wp_named_size();
```

####Sets the default image size.
Accepted values are full|large|medium|thumbnail or array( 'width', 'height' ).

```php
wds_set_default_size_of_image( $size );
```

####Sets the default placeholder size.
Accepted values are full|large|medium|thumbnail or array( 'width', 'height' ).

```php
wds_set_default_size_placeholder( $size );
```

####Outputs the post image or placeholder.

```php
wds_the_image( $args );
```
