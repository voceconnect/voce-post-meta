Voce Post Meta
===================
Contributors: prettyboymp, kevinlangleyjr, jeffstieler, markparolisi, banderon  
Tags: post, meta  
Requires at least: 3.5  
Tested up to: 3.9  
Stable tag: 1.5  
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

## Standard Field Options
These are the args that all default fields accept, and that any extended fields are expected to handle.  
Arg | Default | Type | Description  
`description` | none | String | A short description of the expected value displayed with the field.  
`capability` | `edit_posts` | String | User permission level that must be met to edit the field.  
`default_value` | none | Mixed | The value to be shown and used when not set.  
`display_callbacks` | varies | Array | An array of valid callable functions to render the field display. Functions should expect 3 arguments: `$field, $current_value, $post_id`  
`sanitize_callbacks` | varies | Array | An array of callable functions to sanitize the field value on save. Functions should expect 4 arguments: `$field, $old_value, $new_value, $post_id`  


## Input Types

By default, Voce Post Meta comes with support for these input types:

### Text Inputs
* `text` - A one line text input field
* `textarea` - A basic multiline text field.  
* `numberic` - The same as text field but sanitizes as a number on save.  
* `wp_editor` - Uses the full WordPress post content editor, for more advanced editing scenarios.  

### Selection
* `dropdown` - A dropdown select field.  
* `radio` - Radio button selection field.  

Both selection fields expect an `options` argument passed into the options array. The options should be an array of `$value => $label` pairs.

### Misc
* `hidden` A hidden input field for saving meta outside of user control. 
* `checkbox` - Checkbox input field for on/off toggling.  


**1.5**  
*Fixing wp_editor issue with WP 3.9*

**1.4.1**  
*Adding get_vpm_value() helper function to get post meta value*

**1.4**  
*Adding sanitization function for dropdown meta fields*

**1.3**  
*Adding radio buttons as an available field*

**1.2**  
*Adding ability to use some HTML within the label and description of a metadata field*

**1.1**  
*Adding WP Editor*

**1.0**  
*Initial version.*
