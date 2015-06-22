Voce Post Meta
===================
Contributors: prettyboymp, kevinlangleyjr, jeffstieler, markparolisi, banderon  
Tags: post, meta  
Requires at least: 3.5  
Tested up to: 4.2.2  
Stable tag: 1.9.2  
License: GPLv2 or later  
License URI: http://www.gnu.org/licenses/gpl-2.0.html

## Description
Create a group (metabox), then add fields to it.

## Installation

### As standard plugin:
> See [Installing Plugins](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

### As theme or plugin dependency:
> After dropping the plugin into the containing theme or plugin, add the following:
```php
if( ! class_exists( 'Voce_Meta_API' ) ) {
	require_once( $path_to_voce_post_meta . '/voce-post-meta.php' );
}
```

## Usage

#### Example

```php
<?php
add_action('init', function(){
	add_metadata_group( 'demo_meta', 'Page Options', array(
		'capability' => 'edit_posts'
	));
	add_metadata_field( 'demo_meta', 'demo_key', 'Title', 'text', array(
		'description' => 'Descriptive string. Example: <code>Description</code>'
	) );
	add_post_type_support( 'page', 'demo_meta' );
});
?>
```

## Input Types

By default, Voce Post Meta comes with support for these input types:

#### Text Inputs
* `text` - A one line text input field.
* `textarea` - A basic multiline text field.  
* `numberic` - The same as text field but sanitizes as a number on save.  
* `wp_editor` - Uses the full WordPress post content editor, for more advanced editing scenarios.  

#### Selection
* `dropdown` - A dropdown select field.  
* `radio` - Radio button selection field.  
* `checkbox` - Checkbox input field for on/off toggling.  

The `dropdown` and `radio` fields expect an `options` argument passed into the options array. The options should be an array of `$value => $label` pairs.

This field is optional for the `checkbox` field; utilizing it will allow for multiple checkboxes under the same field and will cause the value to save as an array.

Additionally, for `radio` fields and `checkbox` fields that use the `options` argument, an `item_container` argument is available to specify the HTML tag used to wrap the individual items. The only currently allowed values are `div` (default) and `span`.

#### Misc
* `hidden` A hidden input field for saving meta outside of user control. 



### Standard Field Options
These are the **optional** args that all default fields accept, and that any extended fields are expected to handle.  

```php
<?php
array(
	'description' => '', // A short description of the expected value displayed with the field.
	'default_value' => '', // The value to be shown and used when not set.
	
	// Override options
	'capability' => '', // User permission level that must be met to edit the field. Defaults to edit_posts
	'display_callbacks' => array(), // An array of valid callable functions to render the field display.
	'sanitize_callbacks' => array() // An array of callable functions to sanitize the field value on save.
);
?>
```

## Changelog

Please refer to the full changelog at https://github.com/voceconnect/voce-post-meta/releases