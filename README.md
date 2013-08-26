Voce Post Meta
===================
Contributors: prettyboymp, kevinlangleyjr, jeffstieler, markparolisi, banderon  
Tags: post, meta  
Requires at least: 3.5  
Tested up to: 3.6  
Stable tag: 1.3
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
if( ! class_exists( 'Voce_Post_Meta' ) ) {
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

**1.3**
*Adding radio buttons as an available field

**1.2**  
*Adding ability to use some HTML within the label and description of a metadata field*

**1.1**  
*Adding WP Editor*

**1.0**  
*Initial version.*