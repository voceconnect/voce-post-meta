<?php
if ( !class_exists('Voce_Meta_API') ) {
/*
  Plugin Name: Voce Post Meta
  Description: Allow easily adding meta fields to post types
  Version: 1.9.2
  Author: prettyboymp, kevinlangleyjr, jeffstieler, markparolisi, banderon, smccafferty
  License: GPLv2 or later
 */

class Voce_Meta_API {

	private static $instance;
	public $groups;
	public $type_mapping;
	public $label_allowed_html;
	public $description_allowed_html;

	/**
	 *
	 * @return Voce_Meta_API
	 */
	public static function GetInstance() {

		if ( !isset( self::$instance ) ) {
			self::$instance = new Voce_Meta_API();
		}

		return self::$instance;
	}

	/**
	 * @constructor
	 */
	private function __construct() {
		$this->groups = array();

		$mapping = array();
		$mapping['text'] = array(
			'class' => 'Voce_Meta_Field',
			'args' => array(
				'display_callbacks' => array( 'voce_text_field_display' ),
				'sanitize_callbacks' => array( 'vpm_sanitize_text_field' )
			)
		);
		$mapping['hidden'] = array(
			'class' => 'Voce_Meta_Field',
			'args' => array(
				'display_callbacks' => array( 'voce_hidden_field_display' )
			)
		);
		$mapping['numeric'] = array(
			'class' => 'Voce_Meta_Field',
			'args' => array(
				'display_callbacks' => array( 'voce_text_field_display' ),
				'sanitize_callbacks' => array( 'voce_numeric_value' )
			)
		);
		$mapping['dropdown'] = array(
			'class' => 'Voce_Meta_Field',
			'args' => array(
				'display_callbacks' => array( 'voce_dropdown_field_display' )
			)
		);
		$mapping['textarea'] = array(
			'class' => 'Voce_Meta_Field',
			'args' => array(
				'display_callbacks' => array( 'voce_textarea_field_display' ),
				'rows' => 2
			)
		);
		$mapping['checkbox'] = array(
			'class' => 'Voce_Meta_Field',
			'args' => array(
				'display_callbacks' => array( 'voce_checkbox_field_display' )
			)
		);
		$mapping['radio'] = array(
			'class' => 'Voce_Meta_Field',
			'args' => array(
				'display_callbacks' => array( 'voce_radio_field_display' )
			)
		);
		$mapping['wp_editor'] = array(
			'class' => 'Voce_Meta_Field',
			'args' => array(
				'display_callbacks' => array( 'voce_wp_editor_field_display' ),
				'sanitize_callbacks' => array( 'voce_sanitize_wp_editor' ),
				'wp_editor_args' => array(
					'textarea_rows' => 10
				)
			)
		);

		$this->type_mapping = apply_filters( 'meta_type_mapping', $mapping );

		$allowed_html = array(
			'a' => array(
				'href' => array(),
				'title' => array()
			),
			'br' => array(),
			'em' => array(),
			'strong' => array(),
			'code' => array(),
			'pre' => array()
		);

		$this->label_allowed_html = apply_filters( 'voce_meta_label_allowed_html', $allowed_html );
		$this->description_allowed_html = apply_filters( 'voce_meta_description_allowed_html', $allowed_html );
	}

	/**
	 *
	 * @param string $id
	 * @param string $title
	 * @param array $args
	 * @return Voce_Meta_Group
	 */
	public function add_group( $id, $title, $args = array() ) {

		if ( !isset( $this->groups[$id] ) ) {
			$this->groups[$id] = new Voce_Meta_Group( $id, $title, $args );
		}

		return $this->groups[$id];
	}

	/**
	 * @method get_meta_value
	 * @param integer $post_id
	 * @param string $group
	 * @param string $field
	 * @return boolean
	 */
	public function get_meta_value( $post_id, $group, $field ) {
		if ( isset( $this->groups[$group] ) && isset( $this->groups[$group]->fields[$field] ) ) {
			return $this->groups[$group]->fields[$field]->get_value( $post_id );
		}
		return false;
	}

}

class Voce_Meta_Group {

	/**
	 *
	 * Associative array of fields belonging to this group
	 * @var array
	 */
	var $fields;

	/**
	 *
	 * ID of the group
	 * @var string
	 */
	var $id;

	/**
	 *
	 * Title of group
	 * @var string
	 */
	var $title;

	/**
	 *
	 * Descriptive text to display at the top of the metabox
	 * @var string
	 */
	var $description;

	/**
	 *
	 * Required capability to edit this group
	 * @var string
	 */
	var $capability;

	/**
	 *
	 * Context used for the metabox
	 * @var string
	 */
	var $context;

	/**
	 *
	 * priority for the metabox
	 * @var string
	 */
	var $priority;

	/**
	 *
	 * @param interger id
	 * @param string title
	 * @param array args
	 * @constructor
	 */
	public function __construct( $id, $title, $args ) {
		$defaults = array(
			'description' => '',
			'capability' => 'edit_posts',
			'context' => 'normal',
			'priority' => 'default'
		);
		$r = wp_parse_args( $args, $defaults );

		$this->fields = array();
		$this->id = $id;
		$this->title = $title;
		$this->description = $r['description'];
		$this->capability = $r['capability'];
		$this->context = $r['context'];
		$this->priority = $r['priority'];

		add_action( 'add_meta_boxes', array( $this, '_add_metabox' ) );
		add_action( 'save_post', array( $this, 'update_group' ), 10, 2 );
	}

	/**
	 *
	 * @method _add_metabox
	 * @param string $post_type
	 */
	public function _add_metabox( $post_type ) {

		if ( post_type_supports( $post_type, $this->id ) && current_user_can( $this->capability ) ) {
			add_meta_box( $this->id, $this->title, array( $this, '_display_group' ), $post_type, $this->context, $this->priority );
		}
	}

	/**
	 * @method _display_group
	 * @param type $post
	 */
	public function _display_group( $post ) {
		echo !empty( $this->description ) ? ('<p class="description">' . wp_kses( $this->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</p>') : '';
		foreach ($this->fields as $field) {
			$field->display_field( $post->ID );
		}
		wp_nonce_field( "update_{$this->id}", "{$this->id}_nonce" );
	}

	/**
	 * @method __call
	 * @param string $name
	 * @param array $func_args
	 * @return null
	 */
	public function __call( $name, $func_args ) {
		if ( strpos( $name, 'add_field_' ) === 0 ) {
			$type = substr( $name, 10 );
			$api = Voce_Meta_API::GetInstance();
			if ( isset( $api->type_mapping[$type] ) ) {
				$mapping = $api->type_mapping[$type];
				$field_args = isset( $func_args[2] ) ? $func_args[2] : array();
				$field_args = wp_parse_args( $field_args, $mapping['args'] );
				return $this->add_field( $mapping['class'], $func_args[0], $func_args[1], $field_args );
			}
			return null;
		}
	}

	/**
	 *
	 * Creates, adds, and returns a new field for this group
	 * @param string $type
	 * @param string $id
	 * @param string $label
	 * @param array $args
	 * @return iVoce_Meta_Field
	 */
	public function add_field( $type, $id, $label, $args = array() ) {

		if ( !isset( $this->fields[$id] ) ) {

			if ( class_exists( $type ) && in_array( 'iVoce_Meta_Field', class_implements( $type ) ) ) {
				$this->fields[$id] = new $type( $this, $id, $label, $args );
			}
		}

		return $this->fields[$id];
	}

	/**
	 *
	 * Deletes a field for this group
	 * @param string $id
	 * @return bool
	 */
	public function remove_field( $id ) {

		if ( isset( $this->fields[$id] ) ){
			unset( $this->fields[$id] );
			return true;
		}

		return false;
	}

	/**
	 * @method verfiy_nonce
	 * @return boolean
	 */
	private function verify_nonce() {

		if ( isset( $_POST["{$this->id}_nonce"] ) ) {
			return wp_verify_nonce( $_POST["{$this->id}_nonce"], "update_{$this->id}" );
		}

		return false;
	}

	/**
	 * @method update_group
	 * @param type $post_id
	 * @param type $post
	 * @return type
	 */
	public function update_group( $post_id, $post ) {

		if ( wp_is_post_autosave( $post ) ||
				wp_is_post_revision( $post ) ||
				!post_type_supports( $post->post_type, $this->id ) ||
				!$this->verify_nonce() ||
				!current_user_can( 'edit_posts' ) ) {
			return $post_id;
		}

		foreach ($this->fields as $field) {
			$field->update_field( $post_id );
		}
	}

}

interface iVoce_Meta_Field {

	public function __construct( $group, $id, $label, $args = array() );

	public function update_field( $post_id );

	public function display_field( $post_id );
}

/**
 * @class Voce_Meta_Field
 */
class Voce_Meta_Field implements iVoce_Meta_Field {

	var $group;
	var $id;
	var $label;
	var $post_type;
	var $display_callbacks;
	var $sanitize_callbacks;
	var $capability;
	var $args;

	/**
	 * @constructor
	 * @param string $group
	 * @param integer $id
	 * @param string $label
	 * @param array $args
	 */
	public function __construct( $group, $id, $label, $args = array() ) {
		$this->group = $group;
		$this->label = $label;
		$this->id = $id;
		$this->post_type = get_post_type( $id );

		$defaults = array(
			'capability' => $this->group->capability,
			'default_value' => '',
			'display_callbacks' => array( 'voce_text_field_display' ),
			'sanitize_callbacks' => array( 'vpm_sanitize_text_field ' ),
			'description' => ''
		);
		$args = wp_parse_args( $args, $defaults );

		$this->default_value = $args['default_value'];
		$this->display_callbacks = $args['display_callbacks'];
		$this->sanitize_callbacks = $args['sanitize_callbacks'];
		$this->description = $args['description'];
		$this->args = $args;
	}

	/**
	 * Returns post meta value
	 *
	 * @param integer $post_id
	 * @return string|bool Returns default value if meta returns empty
	 */
	public function get_value( $post_id ) {
		$value = get_post_meta( $post_id, "{$this->group->id}_{$this->id}", true );
		if ( ('' === $value) && $this->default_value ) {
			$value = $this->default_value;
		}
		return $value;
	}

	/**
	 * Update post meta
	 * @param type $post_id
	 */
	public function update_field( $post_id ) {
		$old_value = $this->get_value( $post_id );
		$new_value = '';
		$meta_key = "{$this->group->id}_{$this->id}";
		if ( isset( $_POST[ $this->group->id ][ $this->id ] ) ) {
			$new_value = $_POST[ $this->group->id ][ $this->id ];
		} elseif ( isset( $_POST[ $meta_key ] ) ) {
			// see voce_wp_editor_field_display function
			$new_value = $_POST[ $meta_key ];
		} 
		foreach ($this->sanitize_callbacks as $callback) {
			if ( is_callable( $callback ) )
				$new_value = call_user_func( $callback, $this, $old_value, $new_value, $post_id );
		}

		if ( is_null($new_value) || false === $new_value || "" === $new_value ) {
			delete_post_meta( $post_id, $meta_key );

			do_action( 'voce_meta_deleted_field', $post_id, $meta_key, $new_value, $old_value, $this );
		} else {
			update_post_meta( $post_id, $meta_key, $new_value );

			do_action( 'voce_meta_updated_field', $post_id, $meta_key, $new_value, $old_value, $this );
		}
	}

	/**
	 * Output HTML from application callback or user defined callback
	 *
	 * @param type $post_id
	 */
	public function display_field( $post_id ) {
		$value = $this->get_value( $post_id );
		foreach ($this->display_callbacks as $callback) {
			if ( is_callable( $callback ) )
				call_user_func( $callback, $this, $value, $post_id );
		}
	}

	/**
	 * Returns field name
	 *
	 * @return string Returns name for form element.
	 */
	public function get_name(){
		return "{$this->group->id}[{$this->id}]";
	}

	/**
	 * Returns field input id
	 *
	 * @return string Returns input id used on the form element
	 */
	public function get_input_id(){
		return "{$this->group->id}_{$this->id}";
	}

}

/**
 *
 * @param string $id
 * @param string $title
 * @param array $args
 * @return Voce_Meta_Group
 */
function add_metadata_group( $id, $title, $args = array() ) {
	return Voce_Meta_API::GetInstance()->add_group( $id, $title, $args );
}

/**
 * @method add_metadata_field
 * @param string $group
 * @param integer $id
 * @param string $label
 * @param string $type
 * @param array $args
 * @return boolean
 */
function add_metadata_field( $group, $id, $label, $type = 'text', $args = array() ) {
	$api = Voce_Meta_API::GetInstance();
	if ( isset( $api->groups[$group] ) ) {
		$func = "add_field_{$type}";
		return $api->groups[$group]->$func( $id, $label, $args );
	}
	return false;
}

/**
 * @method get_vpm_value
 * @param string $group
 * @param string $field
 * @param integer $post_id
 */
function get_vpm_value( $group, $field, $post_id = false ){
	if( !$post_id )
		$post_id = get_the_ID();

	return Voce_Meta_API::GetInstance()->get_meta_value( $post_id, $group, $field );
}

/**
 * @method remove_metadata_field
 * @param string $group
 * @param integer $id
 * @return boolean
 */
function remove_metadata_field( $group, $id ) {
	$api = Voce_Meta_API::GetInstance();
	if ( isset( $api->groups[$group] ) ) {
		return $api->groups[$group]->remove_field( $id );
	}
	return false;
}

/**
 * @method voce_field_label_display
 * @param string $field
 */
function voce_field_label_display( $field ) {
	if ( property_exists( $field, 'label' ) && ('' != $field->label) ):
		?>
		<label for="<?php echo esc_attr( $field->get_input_id() ) ?>"><?php echo wp_kses( $field->label, Voce_Meta_API::GetInstance()->label_allowed_html ); ?>:</label>
		<?php
	endif;
}

/**
 * @method voce_textarea_field_display
 * @param string $field
 * @param type $current_value
 * @param integer $post_id
 */
function voce_textarea_field_display( $field, $current_value, $post_id ) {
	?>
	<p id="<?php echo esc_attr( 'vpm_field-' . $field->get_input_id() ); ?>">
		<?php voce_field_label_display( $field ); ?>
		<textarea class="widefat" id="<?php echo esc_attr( $field->get_input_id() ); ?>" name="<?php echo esc_attr( $field->get_name() ); ?>" rows="<?php echo esc_attr( $field->args['rows'] ); ?>"><?php echo esc_textarea( $current_value ); ?></textarea>
		<?php echo !empty( $field->description ) ? ('<br><span class="description">' . wp_kses( $field->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</span>') : ''; ?>
	</p>
	<?php
}

/**
 * @method voce_checkbox_field_display
 * @param string $field
 * @param type $current_value
 * @param integer $post_id
 */
function voce_checkbox_field_display( $field, $current_value, $post_id ) {
	?>
	<p id="<?php echo esc_attr( 'vpm_field-' . $field->get_input_id() ); ?>">
		<?php voce_field_label_display( $field ); ?>
		<?php if ( empty( $field->args['options'] ) ): ?>
			<input type="checkbox" id="<?php echo esc_attr( $field->get_input_id() ); ?>" name="<?php echo esc_attr( $field->get_name() ) ?>" <?php checked( $current_value, 'on' ); ?> />
		<?php else: ?>
			<?php $item_container = ! empty( $field->args['item_container'] ) && in_array( $field->args['item_container'], array( 'div', 'span' ) ) ? $field->args['item_container'] : 'div'; ?>
			<?php foreach ($field->args['options'] as $key => $value): ?>
			<<?php echo tag_escape( $item_container ); ?> class="voce-meta-checkbox"><input type="checkbox" id="<?php echo esc_attr( $field->get_input_id() . '_' . $key ); ?>" name="<?php echo esc_attr( $field->get_name() . '[' . $key . ']') ?>" <?php checked( array_key_exists( $key, (array)$current_value ), true ); ?> /><?php echo wp_kses( $value, Voce_Meta_API::GetInstance()->label_allowed_html ); ?></<?php echo tag_escape( $item_container ); ?>>
			<?php endforeach; ?>
		<?php endif; ?>
		<?php echo !empty( $field->description ) ? ('<br><span class="description">' . wp_kses( $field->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</span>') : ''; ?>
	</p>
	<?php
}

/**
 * @method voce_radio_field_display
 * @param string $field
 * @param type $current_value
 * @param integer $post_id
 */
function voce_radio_field_display( $field, $current_value, $post_id ) {
	$item_container = ! empty( $field->args['item_container'] ) && in_array( $field->args['item_container'], array( 'div', 'span' ) ) ? $field->args['item_container'] : 'div';
	?>
	<p id="<?php echo esc_attr( 'vpm_field-' . $field->get_input_id() ); ?>">
		<?php voce_field_label_display( $field ); ?>
		<?php foreach ($field->args['options'] as $key => $value): ?>
			<<?php echo tag_escape( $item_container ); ?> class="voce-meta-radio"><input type="radio" id="<?php echo esc_attr( $field->get_input_id() . '_' . $key ); ?>" name="<?php echo esc_attr( $field->get_name() ) ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( $current_value, $key ); ?> /><?php echo wp_kses( $value, Voce_Meta_API::GetInstance()->label_allowed_html ); ?></<?php echo tag_escape( $item_container ); ?>>
		<?php endforeach; ?>
		<?php echo !empty( $field->description ) ? ('<br><span class="description">' . wp_kses( $field->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</span>') : ''; ?>
	</p>
	<?php
}

/**
 * @method voce_dropdown_field_display
 * @param string $field
 * @param type $current_value
 * @param integer $post_id
 */
function voce_dropdown_field_display( $field, $current_value, $post_id ) {
	?>
	<p id="<?php echo esc_attr( 'vpm_field-' . $field->get_input_id() ); ?>">
		<?php voce_field_label_display( $field ); ?>
		<select id="<?php echo esc_attr( $field->get_input_id() ); ?>" name="<?php echo esc_attr( $field->get_name() ); ?>">
			<?php foreach ($field->args['options'] as $key => $value): ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_value, $key ); ?>><?php echo esc_html( $value ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php echo !empty( $field->description ) ? ('<br><span class="description">' . wp_kses( $field->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</span>') : ''; ?>
	</p>
	<?php
}

/**
 * @method voce_text_field_display
 * @param string $field
 * @param type $value
 * @param integer $post_id
 */
function voce_text_field_display( $field, $value, $post_id ) {
	?>
	<p id="<?php echo esc_attr( 'vpm_field-' . $field->get_input_id() ); ?>">
		<?php voce_field_label_display( $field ); ?>
		<input class="widefat" type="text" id="<?php echo esc_attr( $field->get_input_id() ); ?>" name="<?php echo esc_attr( $field->get_name() ); ?>" value="<?php echo esc_attr( $value ); ?>"  />
		<?php echo !empty( $field->description ) ? ('<br><span class="description">' . wp_kses( $field->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</span>') : ''; ?>
	</p>
	<?php
}

function voce_wp_editor_field_display($field, $current_value, $post_id) {
	?>
	<div id="<?php echo esc_attr( 'vpm_field-' . $field->get_input_id() ); ?>" class="voce-post-meta-wp-editor">
		<?php voce_field_label_display($field);
			echo '<div class="wp-editor-wrapper">';
			// use the get_input_id method as the $editor_id (second argument in wp_editor()) because WP3.9 deprecated the usage of brackets in the name value for the wp_editor
			wp_editor( $current_value, $field->get_input_id(), $field->args['wp_editor_args'] );
			echo '</div>';
			echo !empty( $field->description ) ? ('<br><span class="description">' . wp_kses( $field->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</span>') : '';
		?>
	</div>
	<?php
}

/**
 * @method voce_hidden_field_display
 * @param type $field
 * @param type $value
 * @param integer $post_id
 */
function voce_hidden_field_display( $field, $value, $post_id ) {
	?>
	<input class="hidden" type="hidden" id="<?php echo esc_attr( $field->get_input_id() ); ?>" name="<?php echo esc_attr( $field->get_name() ); ?>" value="<?php echo esc_attr( $value ); ?>"  />
	<?php
}

/**
 * @method voce_numeric_value
 * @param string $field
 * @param type $old
 * @param type $new
 * @param integer $post_id
 * @return int
 */
function voce_numeric_value( $field, $old, $new, $post_id ) {
	if ( is_numeric( $new ) ) {
		return $new;
	}
	return 0;
}

/**
 * @method vpm_sanitize_dropdown
 * @param string $field
 * @param type $old
 * @param type $new
 * @param integer $post_id
 * @return mixed
 */
function vpm_sanitize_dropdown( $field, $old, $new, $post_id ) {
	if( isset( $field->args['options'] ) && !empty( $field->args['options'] ) ){
		$value = sanitize_text_field( $new );
		if( in_array( $value, array_keys( $field->args['options'] ) ) )
			return $value;
	}
	return false;
}

function voce_sanitize_wp_editor( $field, $old_value, $new_value, $post_id ) {
	return wp_kses( $new_value, wp_kses_allowed_html( 'post' ) );
}

function vpm_sanitize_text_field( $field, $old, $new, $post_id ) {
	return sanitize_text_field( $new );
}

}