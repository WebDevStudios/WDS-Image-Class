# WDS Image Class

Library for getting post images and attachments the WDS way.

We always want the post image or an attachment somewhere in our code, but WordPress doesn't, by default, have a fallback that supplies some kind of image (a placeholder).

This library allows you to get a post's featured image (or the first image we can find in the post) or a media file and always have a fallback to a placeholder (which you can define using the customizer).

It also offers up some handy tools you can use like resizing images, etc.

### Handy Functions ###

Get the attachment URI or pass back a placeholder.

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

Return the default arguments (see `$args` array above).

```php
wds_get_image_uri_arg_defaults()
```

```php
wds_get_first_image_in_post_uri()
```
```php
wds_get_image_placeholder_uri()
```
```php
wds_get_image_uri()
```

```php
wds_get_resize_image_uri()
```
```php
wds_get_wp_size_options()
```
```php
wds_image_placeholder_customizer()
```
```php
wds_is_acceptable_size_choice()
```
```php
wds_is_wp_named_size()
```
```php
wds_set_default_size_of_image()
```
```php
wds_set_default_size_placeholder()
```
```php
wds_the_image()
```