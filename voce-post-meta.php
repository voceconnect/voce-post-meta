<?php

if ( ! class_exists( 'Voce_Meta_API' ) ):
/*
  Plugin Name: Voce Post Meta
  Description: Allow easily adding meta fields to post types
  Version: 2.0.0-alpha
  Author: prettyboymp, kevinlangleyjr, jeffstieler, markparolisi, banderon
  License: GPLv2 or later
 */


/**
 * Class Voce_Meta_API
 *
 * Main API class
 */
class Voce_Meta_API {

	public $groups;
	public $type_mapping;
	public $label_allowed_html;
	public $description_allowed_html;

	private static $instance;

	/**
	 * Get singleton
	 *
	 * @return Voce_Meta_API
	 */
	public static function GetInstance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Voce_Meta_API();
		}

		return self::$instance;
	}

	/**
	 * @constructor
	 */
	private function __construct() {
		$this->groups = array();

		/* v2.0.0: move/rename default display/sanitization functions into default class */
		$mapping = array();
		$mapping['text'] = array(
			'class' => 'Voce_Meta_Field',
			'args'  => array(
				'display_callbacks' => array( array( 'Voce_Meta_Field', 'display_text' ) ),
			),
		);
		$mapping['textarea'] = array(
			'class' => 'Voce_Meta_Field',
			'args'  => array(
				'display_callbacks' => array( array( 'Voce_Meta_Field', 'display_textarea' ) ),
			),
		);
		$mapping['hidden'] = array(
			'class' => 'Voce_Meta_Field',
			'args'  => array(
				'display_callbacks' => array( array( 'Voce_Meta_Field', 'display_hidden' ) ),
			)
		);
		$mapping['numeric'] = array(
			'class' => 'Voce_Meta_Field',
			'args'  => array(
				'display_callbacks'  => array( array( 'Voce_Meta_Field', 'display_text' ) ),
				'sanitize_callbacks' => array( array( 'Voce_Meta_Field', 'sanitize_numeric' ) ),
			),
		);
		$mapping['dropdown'] = array(
			'class' => 'Voce_Meta_Field',
			'args'  => array(
				'display_callbacks'  => array( array( 'Voce_Meta_Field', 'display_dropdown' ) ),
				'sanitize_callbacks' => array( array( 'Voce_Meta_Field', 'sanitize_dropdown' ) ),
			),
		);
		$mapping['checkbox'] = array(
			'class' => 'Voce_Meta_Field_Checkbox',
			'args'  => array(
				'display_callbacks'       => array( array( 'Voce_Meta_Field_Checkbox', 'display_checkbox' ) ),
				'clone_template_callback' => array( 'Voce_Meta_Field_Checkbox', 'display_checkbox_template' ),
				'clone_js_callback'       => array( 'Voce_Meta_Field_Checkbox', 'render_clone_checkbox_js' ),
				'clone_js'                => 'vpm_clone_checkbox',
			),
		);
		$mapping['radio'] = array(
			'class' => 'Voce_Meta_Field_Radio',
			'args'  => array(
				'display_callbacks'       => array( array( 'Voce_Meta_Field_Radio', 'display_radio' ) ),
				'clone_template_callback' => array( 'Voce_Meta_Field_Radio', 'display_radio_template' ),
				'clone_js_callback'       => array( 'Voce_Meta_Field_Radio', 'render_clone_radio_js' ),
				'clone_js'                => 'vpm_clone_radio',
			),
		);
		$mapping['wp_editor'] = array(
			'class' => 'Voce_Meta_Field_WP_Editor',
			'args'  => array(
				'display_callbacks'       => array( array( 'Voce_Meta_Field_WP_Editor', 'display_wp_editor' ) ),
				'sanitize_callbacks'      => array( array( 'Voce_Meta_Field_WP_Editor', 'sanitize_wp_editor' ) ),
				'clone_template_callback' => array( 'Voce_Meta_Field_WP_Editor', 'display_wp_editor_template' ),
				'clone_js_callback'       => array( 'Voce_Meta_Field_WP_Editor', 'render_clone_wp_editor_js' ),
				'clone_js'                => 'vpm_clone_wp_editor',
				'wp_editor_args'          => array(
					'textarea_rows' => 10
				),
			)
		);

		/* v2.0.0: add 'voce_' to filter to bring it in line with other plugin filters */
		$this->type_mapping = apply_filters( 'voce_meta_type_mapping', $mapping );

		$allowed_html = array(
			'a'      => array(
				'href'  => array(),
				'title' => array(),
			),
			'br'     => array(),
			'em'     => array(),
			'strong' => array(),
			'code'   => array(),
			'pre'    => array(),
		);

		$this->label_allowed_html = apply_filters( 'voce_meta_label_allowed_html', $allowed_html );
		$this->description_allowed_html = apply_filters( 'voce_meta_description_allowed_html', $allowed_html );

		add_action( 'admin_print_styles-post.php', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'admin_print_styles-post-new.php', array( __CLASS__, 'enqueue_scripts' ) );
	}

	public static function enqueue_scripts() {
		wp_enqueue_script( 'vpm_multiple-fields', plugins_url( '/js/multiple-fields.js', __FILE__ ), array( 'jquery', 'jquery-ui-sortable' ) );
		wp_enqueue_style( 'vpm_multiple-fields', plugins_url( '/css/multiple-fields.css', __FILE__ ) );
	}

	/**
	 *
	 * @param string $id
	 * @param string $title
	 * @param array $args
	 *
	 * @return Voce_Meta_Group
	 */
	public function add_group( $id, $title, $args = array() ) {
		if ( !isset( $this->groups[ $id ] ) ) {
			$this->groups[ $id ] = new Voce_Meta_Group( $id, $title, $args );
		}

		return $this->groups[ $id ];
	}

	/**
	 * @method get_meta_value
	 *
	 * @param integer $post_id
	 * @param string $group
	 * @param string $field
	 *
	 * @return boolean
	 */
	public function get_meta_value( $post_id, $group, $field ) {
		if ( isset( $this->groups[ $group ] ) && isset( $this->groups[ $group ]->fields[ $field ] ) ) {
			$values = $this->groups[ $group ]->fields[ $field ]->get_value( $post_id );
			if ( empty( $this->groups[ $group ]->fields[ $field ]->multiple ) ) {
				return reset( $values );
			}

			return $values;
		}

		return false;
	}

	/**
	 * @method get_meta_count
	 *
	 * @param integer $post_id
	 * @param string $group
	 * @param string $field
	 *
	 * @return integer
	 */
	public function get_meta_count( $post_id, $group, $field ) {
		if ( isset( $this->groups[ $group ] ) && isset( $this->groups[ $group ]->fields[ $field ] ) ) {
			return $this->groups[ $group ]->fields[ $field ]->get_count( $post_id );
		}

		return 0;
	}

}


/**
 * Class Voce_Meta_Group
 *
 * Handle meta groups
 */
class Voce_Meta_Group {

	/**
	 * Associative array of fields belonging to this group
	 *
	 * @var array
	 */
	var $fields;

	/**
	 * ID of the group
	 *
	 * @var string
	 */
	var $id;

	/**
	 * Title of group
	 *
	 * @var string
	 */
	var $title;

	/**
	 * Descriptive text to display at the top of the metabox
	 *
	 * @var string
	 */
	var $description;

	/**
	 * Required capability to edit this group
	 *
	 * @var string
	 */
	var $capability;

	/**
	 * Context used for the metabox
	 *
	 * @var string
	 */
	var $context;

	/**
	 * Priority for the metabox
	 *
	 * @var string
	 */
	var $priority;

	/**
	 * @param interger id
	 * @param string title
	 * @param array args
	 *
	 * @constructor
	 */
	public function __construct( $id, $title, $args ) {
		$defaults = array(
			'description' => '',
			'capability'  => 'edit_posts',
			'context'     => 'normal',
			'priority'    => 'default'
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
	 * @method _add_metabox
	 *
	 * @param string $post_type
	 */
	public function _add_metabox( $post_type ) {
		if ( post_type_supports( $post_type, $this->id ) && current_user_can( $this->capability ) ) {
			add_meta_box( $this->id, $this->title, array( $this, '_display_group' ), $post_type, $this->context, $this->priority );
		}
	}

	/**
	 * @method _display_group
	 *
	 * @param type $post
	 */
	public function _display_group( $post ) {
		echo ( ! empty( $this->description ) ? '<p class="description">' . wp_kses( $this->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</p>' : '' );
		foreach ( $this->fields as $field ) {
			$field->display_field( $post->ID );
		}
		wp_nonce_field( "update_{$this->id}", "{$this->id}_nonce" );
	}

	/**
	 * @method __call
	 *
	 * @param string $name
	 * @param array $func_args
	 *
	 * @return iVoce_Meta_Field|null New field or null
	 */
	public function __call( $name, $func_args ) {
		if ( strpos( $name, 'add_field_' ) === 0 ) {
			$type = substr( $name, 10 );
			$api = Voce_Meta_API::GetInstance();

			if ( ! empty( $api->type_mapping[ $type ] ) ) {
				$mapping = $api->type_mapping[ $type ];
				$field_args = isset( $func_args[2] ) ? $func_args[2] : array();
				$field_args = wp_parse_args( $field_args, $mapping['args'] );
				return $this->add_field( $type, $mapping['class'], $func_args[0], $func_args[1], $field_args );
			}

			return null;
		}
	}

	/**
	 * Creates, adds, and returns a new field for this group
	 *
	 * @param string $type
	 * @param string $type_class
	 * @param string $id
	 * @param string $label
	 * @param array $args
	 *
	 * @return iVoce_Meta_Field
	 */
	public function add_field( $type, $type_class, $id, $label, $args = array() ) {
		if ( ! isset( $this->fields[ $id ] ) ) {
			if ( class_exists( $type_class ) && in_array( 'iVoce_Meta_Field', class_implements( $type_class ) ) ) {
				$this->fields[ $id ] = new $type_class( $type, $this, $id, $label, $args );
			}
		}

		return $this->fields[ $id ];
	}

	/**
	 * Deletes a field in this group
	 *
	 * @param string $id
	 *
	 * @return bool
	 */
	public function remove_field( $id ) {
		if ( isset( $this->fields[ $id ] ) ){
			unset( $this->fields[ $id ] );
			return true;
		}

		return false;
	}

	/**
	 * @method verfiy_nonce
	 *
	 * @return boolean
	 */
	private function verify_nonce() {
		if ( isset( $_REQUEST[ "{$this->id}_nonce" ] ) ) {
			return wp_verify_nonce( $_REQUEST[ "{$this->id}_nonce" ], "update_{$this->id}" );
		}

		return false;
	}

	/**
	 * @method update_group
	 *
	 * @param type $post_id
	 * @param type $post
	 *
	 * @return type
	 */
	public function update_group( $post_id, $post ) {
		if ( wp_is_post_autosave( $post ) || wp_is_post_revision( $post ) ) {
			return;
		}
		if ( ! post_type_supports( $post->post_type, $this->id ) || ! $this->verify_nonce() ) {
			return;
		}

		foreach ( $this->fields as $field ) {
			$field->update_field( $post_id );
		}
	}

}


/**
 * Interface iVoce_Meta_Field
 *
 * Interface for custom fields
 */
interface iVoce_Meta_Field {

	public function __construct( $type, $group, $id, $label, $args = array() );

	public function update_field( $post_id );

	public function display_field( $post_id );
}


/**
 * Class Voce_Meta_Field
 *
 * Class for handling basic field types
 */
class Voce_Meta_Field implements iVoce_Meta_Field {

	public $type;
	public $group;
	public $id;
	public $label;
	public $post_type;

	public $capability;
	public $default_value;
	public $display_callbacks;
	public $sanitize_callbacks;
	public $description;

	public $multiple;
	public $add_button_callback;
	public $delete_button_callback;
	public $clone_template_callback;
	public $clone_js;
	public $clone_js_callback;
	public $sortable;

	public $args;

	public $index = '';

	/**
	 * @constructor
	 *
	 * @param string $type
	 * @param string $group
	 * @param integer $id
	 * @param string $label
	 * @param array $args
	 */
	public function __construct( $type, $group, $id, $label, $args = array() ) {
		$this->type = $type;
		$this->group = $group;
		$this->id = $id;
		$this->label = $label;
		$this->post_type = get_post_type( $id );

		$this->args = $args;

		$defaults = array(
			'capability'                  => $this->group->capability,
			'default_value'               => '',
			'display_callbacks'           => array(),
			'sanitize_callbacks'          => array(),
			'description'                 => '',

			'multiple'                    => false,
			'add_button_callback'         => array( __CLASS__, 'render_multiple_add' ),
			'delete_button_callback'      => array( __CLASS__, 'render_multiple_delete' ),
			'clone_template_callback'     => '',
			'clone_js'                    => 'vpm_clone_field',
			'clone_js_callback'           => array( __CLASS__, 'render_clone_field_js' ),
			'sortable'                    => false,
		);
		$args = wp_parse_args( $args, $defaults );

		$this->capability = $args['capability'];
		$this->default_value = $args['default_value'];
		$this->display_callbacks = $args['display_callbacks'];
		$this->sanitize_callbacks = $args['sanitize_callbacks'];
		$this->description = $args['description'];

		$this->multiple = ( intval( $args['multiple'] ) >= 2 ? intval( $args['multiple'] ) : (bool)$args['multiple'] );
		$this->add_button_callback = $args['add_button_callback'];
		$this->delete_button_callback = $args['delete_button_callback'];
		$this->clone_template_callback = ( is_callable( $args['clone_template_callback'] ) ? $args['clone_template_callback'] : reset( $this->display_callbacks ) );
		$this->clone_js = $args['clone_js'];
		$this->clone_js_callback = $args['clone_js_callback'];
		$this->sortable = (bool)$args['sortable'];
	}

	/**
	 * Returns post meta values
	 *
	 * @param integer $post_id
	 *
	 * @return array Returns array of values if multiple, single value in array otherwise. Returns default in array if no values at all.
	 */
	public function get_value( $post_id ) {
		$values = get_post_meta( $post_id, $this->get_input_id() );
		if ( empty( $values ) ) {
			return array( $this->default_value );
		}

		return $values;
	}

	/**
	 * Returns number of saved values for post meta
	 *
	 * @param integer $post_id
	 *
	 * @return integer Returns number of values saved for meta
	 */
	public function get_count( $post_id ) {
		$values = get_post_meta( $post_id, "{$this->group->id}_{$this->id}" );

		return count( $values );
	}

	/**
	 * Update post meta
	 *
	 * @param type $post_id
	 */
	public function update_field( $post_id ) {
		$field_id = $this->get_input_id();
		$old_value = $this->get_value( $post_id );
		$new_value = array();

		if ( isset( $_POST[ $this->group->id ][ $this->id ] ) ) {
			$new_value = $_POST[ $this->group->id ][ $this->id ];
		}

		if ( $this->multiple ) {
			// remove initial value due to template
			array_shift( $new_value );
		}
		foreach ( $this->sanitize_callbacks as $callback ) {
			if ( is_callable( $callback ) ) {
				foreach ( $new_value as $k => $value ) {
					$new_value[ $k ] = call_user_func( $callback, $this, @$old_value[ $k ], $value, $post_id );
				}
			}
		}

		delete_post_meta( $post_id, $field_id );
		foreach ( $new_value as $value ) {
			if ( $value || $value != $this->default_value ) {
				add_post_meta( $post_id, $field_id, $value );
			}
		}
	}

	/**
	 * Output HTML from application callback or user defined callback
	 *
	 * @param type $post_id
	 */
	public function display_field( $post_id ) {
		$values = $this->get_value( $post_id );
		if ( $this->multiple && is_callable( $this->clone_template_callback ) && is_callable( $this->add_button_callback ) && is_callable( $this->delete_button_callback ) ) {
			call_user_func( $this->clone_template_callback, $this, '', $post_id );
			if ( is_callable( $this->clone_js_callback ) ) {
				call_user_func( $this->clone_js_callback, $this, $post_id );
			}
			$this->index = 1;
		}
		foreach ( $this->display_callbacks as $callback ) {
			if ( is_callable( $callback ) ) {
				foreach ( $values as $value ) {
					if ( intval( $this->index ) ) {
						call_user_func( $this->delete_button_callback, $this, $post_id );
					}
					call_user_func( $callback, $this, $value, $post_id );
					if ( intval( $this->index ) ) {
						$this->index++;
					}
				}
			}
		}
		if ( intval( $this->index ) ) {
			call_user_func( $this->add_button_callback, $this, $post_id );
		}
	}

	/**
	 * Returns field name
	 *
	 * @return string Returns name for form element.
	 */
	public function get_name( $index = '' ){
		return "{$this->group->id}[{$this->id}][{$index}]";
	}

	/**
	 * Returns field input id
	 *
	 * @return string Returns input id used on the form element
	 */
	public function get_input_id( $index = '', $separator = '-' ){
		return "{$this->group->id}_{$this->id}" . ( $separator && $index ? $separator . $index : '' );
	}

	/**
	 * Returns field wrapper id
	 *
	 * @return string Returns id used on the form element wrapper
	 */
	public function get_wrapper_id( $index = '', $separator = '-'  ){
		return "vpm_field-" . $this->get_input_id( $index, $separator );
	}

	/**
	 * @method get_label
	 *
	 * @param iVoce_Meta_Field $field
	 *
	 * @return string
	 */
	public static function get_label( $field ) {
		return ( empty( $field->label ) ? '' : sprintf( '<label for="%s">%s:</label>', esc_attr( $field->get_input_id( $field->index ) ), wp_kses( $field->label, Voce_Meta_API::GetInstance()->label_allowed_html ) ) );
	}

	/**
	 * @method render_multiple_add
	 *
	 * @param iVoce_Meta_Field $field
	 * @param integer $post_id
	 */
	public static function render_multiple_add( $field, $post_id ) {
		$class = ( intval( $field->multiple ) >= 2 && intval( $field->multiple ) <= $field->get_count( $post_id ) ? 'disabled' : '' );
		printf( '<span data-wrapper="%s" data-id="%s" data-clone_js="%s" data-multiple_index="%d" data-multiple_max="%d" class="vpm_multiple-add %s dashicons dashicons-plus-alt">Add</span>',
			esc_attr( $field->get_wrapper_id() ),
			esc_attr( $field->get_input_id() ),
			$field->clone_js,
			$field->index,
			$field->multiple,
			$class
		);
	}

	/**
	 * @method render_multiple_delete
	 *
	 * @param iVoce_Meta_Field $field
	 * @param integer $post_id
	 */
	public static function render_multiple_delete( $field, $post_id ) {
		printf( '<span data-wrapper="%s" data-multiple_index="%d" class="vpm_multiple-delete dashicons dashicons-trash"></span>',
			esc_attr( $field->get_wrapper_id() ),
			$field->index
		);
	}

	/**
	 * @method render_clone_field_js
	 *
	 * @param iVoce_Meta_Field $field
	 * @param integer $post_id
	 */
	public static function render_clone_field_js( $field, $post_id ) {
		?>
		<script type="application/javascript">
			if (typeof window.vpm_clone_field === 'undefined') {
				window.vpm_clone_field = function(addButton) {
					var $addButton = jQuery(addButton),
						wrapperId = $addButton.data('wrapper'),
						elId = $addButton.data('id'),
						template = jQuery('#' + wrapperId).clone(),
						$elements = template.add(template.find('*')),
						multiple_index = +$addButton.data('multiple_index'),
						del_button;

					$addButton.attr('data-multiple_index', multiple_index + 1).data('multiple_index', multiple_index + 1);

					jQuery.each($elements, function() {
						var $el = jQuery(this);
						jQuery.each($el.get(0).attributes, function() {
							if (this.value == elId) {
								$el.attr(this.name, this.value + '-' + multiple_index);
							}
							if (this.value == wrapperId) {
								$el.attr(this.name, this.value + '-' + multiple_index);
							}
						});
					});

					template.attr('data-multiple_index', multiple_index).data('multiple_index', multiple_index)
						.removeClass('hidden');

					del_button = jQuery('.vpm_multiple-delete').first().clone()
						.attr('data-wrapper', wrapperId).data('wrapper', wrapperId)
						.attr('data-multiple_index', multiple_index).data('multiple_index', multiple_index);

					$addButton.before(del_button).before(template);
				};
			}
		</script>
		<?php
	}

	/**
	 * @method display_text
	 *
	 * @param iVoce_Meta_Field $field
	 * @param mixed $value
	 * @param integer $post_id
	 */
	public static function display_text( $field, $value, $post_id ) {
		?>
		<p id="<?php echo esc_attr( $field->get_wrapper_id( $field->index ) ); ?>" class="<?php echo esc_attr( $field->get_wrapper_id() ); ?>" data-multiple_index="<?php echo esc_attr( $field->index ); ?>">
			<?php echo self::get_label( $field ); ?>
			<input class="widefat" type="text" id="<?php echo esc_attr( $field->get_input_id( $field->index ) ); ?>" name="<?php echo esc_attr( $field->get_name() ); ?>" value="<?php echo esc_attr( $value ); ?>"  />
			<?php echo ! empty( $field->description ) ? ('<br><span class="description">' . wp_kses( $field->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</span>') : ''; ?>
		</p>
		<?php
	}

	/**
	 * @method display_textarea
	 *
	 * @param iVoce_Meta_Field $field
	 * @param type $current_value
	 * @param integer $post_id
	 */
	public static function display_textarea( $field, $current_value, $post_id ) {
		?>
		<p id="<?php echo esc_attr( $field->get_wrapper_id( $field->index ) ); ?>" class="<?php echo esc_attr( $field->get_wrapper_id() ); ?>" data-multiple_index="<?php echo esc_attr( $field->index ); ?>">
			<?php echo self::get_label( $field ); ?>
			<textarea class="widefat" id="<?php echo esc_attr( $field->get_input_id( $field->index ) ); ?>" name="<?php echo esc_attr( $field->get_name() ); ?>"><?php echo esc_attr( $current_value ); ?></textarea>
			<?php echo !empty( $field->description ) ? ('<br><span class="description">' . wp_kses( $field->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</span>') : ''; ?>
		</p>
		<?php
	}

	/**
	 * @method display_hidden
	 *
	 * @param iVoce_Meta_Field $field
	 * @param type $current_value
	 * @param integer $post_id
	 */
	public static function display_hidden( $field, $current_value, $post_id ) {
		?>
		<input class="hidden" type="hidden" id="<?php echo esc_attr( $field->get_input_id() ); ?>" name="<?php echo esc_attr( $field->get_name() ); ?>" value="<?php echo esc_attr( $current_value ); ?>"  />
		<?php
	}

	/**
	 * @method display_dropdown
	 *
	 * @param iVoce_Meta_Field $field
	 * @param type $current_value
	 * @param integer $post_id
	 */
	public static function display_dropdown( $field, $current_value, $post_id ) {
		?>
		<p id="<?php echo esc_attr( $field->get_wrapper_id( $field->index ) ); ?>" class="<?php echo esc_attr( $field->get_wrapper_id() ); ?>" data-multiple_index="<?php echo esc_attr( $field->index ); ?>">
			<?php echo self::get_label( $field ); ?>
			<select id="<?php echo esc_attr( $field->get_input_id( $field->index ) ); ?>" name="<?php echo esc_attr( $field->get_name() ); ?>">
				<?php foreach ($field->args['options'] as $key => $value): ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_value, $key ); ?>><?php echo esc_html( $value ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php echo !empty( $field->description ) ? ('<br><span class="description">' . wp_kses( $field->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</span>') : ''; ?>
		</p>
		<?php
	}

	/**
	 * @method sanitize_numeric
	 *
	 * @param iVoce_Meta_Field $field
	 * @param type $old_value
	 * @param type $new_value
	 * @param integer $post_id
	 *
	 * @return integer
	 */
	public static function sanitize_numeric( $field, $old_value, $new_value, $post_id ) {
		if ( is_numeric( $new_value ) ) {
			return $new_value;
		}
		return 0;
	}

	/**
	 * @method sanitize_dropdown
	 *
	 * @param iVoce_Meta_Field $field
	 * @param type $old_value
	 * @param type $new_value
	 * @param integer $post_id
	 *
	 * @return string|boolean
	 */
	public static function sanitize_dropdown( $field, $old_value, $new_value, $post_id ) {
		if ( isset( $field->args['options'] ) && ! empty( $field->args['options'] ) ){
			$value = sanitize_text_field( $new_value );
			if ( in_array( $value, array_keys( $field->args['options'] ) ) ) {
				return $value;
			}
		}

		return false;
	}

}


class Voce_Meta_Field_Checkbox extends Voce_Meta_Field {

	/**
	 * @constructor
	 *
	 * @param string $type
	 * @param string $group
	 * @param integer $id
	 * @param string $label
	 * @param array $args
	 */
	public function __construct( $type, $group, $id, $label, $args = array() ) {
		parent::__construct( $type, $group, $id, $label, $args );

		$this->multiple = false;
	}

	/**
	 * Update post meta
	 *
	 * @param type $post_id
	 */
	public function update_field( $post_id ) {
		$field_id = $this->get_input_id();
		$old_values = $this->get_value( $post_id );
		$new_values = array();

		if ( isset( $_POST[ $this->group->id ][ $this->id ] ) ) {
			$new_values = $_POST[ $this->group->id ][ $this->id ];
		}

		foreach ( $this->sanitize_callbacks as $callback ) {
			if ( is_callable( $callback ) ) {
				foreach ( $new_values as $k => $value ) {
					$new_values[ $k ] = call_user_func( $callback, $this, @$old_values[ $k ], $value, $post_id );
				}
			}
		}

		delete_post_meta( $post_id, $field_id );
		if ( empty( $new_values ) ) {
			return;
		}
		foreach ( $new_values as $value ) {
			add_post_meta( $post_id, $field_id, $value );
		}

	}

	/**
	 * Output HTML from application callback or user defined callback
	 *
	 * @param type $post_id
	 */
	public function display_field( $post_id ) {
		$values = $this->get_value( $post_id );
		foreach ( $this->display_callbacks as $callback ) {
			if ( is_callable( $callback ) ) {
				call_user_func( $callback, $this, $values, $post_id );
			}
		}
	}

	/**
	 * @method render_clone_checkbox_js
	 *
	 * @param iVoce_Meta_Field $field
	 * @param integer $post_id
	 */
	public static function render_clone_checkbox_js( $field, $post_id ) {
		?>
		<script type="application/javascript">
			if (typeof window.vpm_clone_checkbox === 'undefined') {
				window.vpm_clone_checkbox = function(addButton) {
					var $addButton = jQuery(addButton),
						wrapperId = $addButton.data('wrapper'),
						elId = $addButton.data('id'),
						template = jQuery('#' + wrapperId).clone(),
						$elements = template.add(template.find('*')),
						multiple_index = +$addButton.data('multiple_index'),
						del_button;

					$addButton.attr('data-multiple_index', multiple_index + 1).data('multiple_index', multiple_index + 1);

					jQuery.each($elements, function() {
						var $el = jQuery(this),
							index_position,
							new_position;

						if ($el.attr('type') == 'checkbox') {
							jQuery.each(['name','id'], function(k, field) {
								index_position = $el.attr(field).indexOf('VPM_CLONE_INDEX');
								if ( index_position > 0 ) {
									new_val = [$el.attr(field).slice(0, index_position), multiple_index, $el.attr(field).slice(index_position + 15)].join('');
									$el.attr(field, new_val);
								}
							});
						}
						jQuery.each($el.get(0).attributes, function() {
							if (this.value == elId) {
								$el.attr(this.name, this.value + '-' + multiple_index);
							}
							if (this.value == wrapperId) {
								$el.attr(this.name, this.value + '-' + multiple_index);
							}
						});

					});

					template.attr('data-multiple_index', multiple_index).data('multiple_index', multiple_index)
						.removeClass('hidden');

					del_button = jQuery('.vpm_multiple-delete').first().clone()
						.attr('data-wrapper', wrapperId).data('wrapper', wrapperId)
						.attr('data-multiple_index', multiple_index).data('multiple_index', multiple_index);

					$addButton.before(del_button).before(template);
				};
			}
		</script>
		<?php
	}

	/**
	 * @method display_checkbox
	 *
	 * @param iVoce_Meta_Field $field
	 * @param type $current_value
	 * @param integer $post_id
	 */
	public static function display_checkbox( $field, $current_value, $post_id ) {
		$val = ( ! empty( $field->args['value'] ) ? $field->args['value'] : 'on' );
		$item_format = ! empty( $field->args['item_format'] ) && in_array( $field->args['item_format'], array( 'inline', 'block' ) ) ? $field->args['item_format'] : 'block';
		?>
		<p id="<?php echo esc_attr( $field->get_wrapper_id( $field->index ) ); ?>" class="<?php echo esc_attr( $field->get_wrapper_id() ); ?> " data-multiple_index="<?php echo esc_attr( $field->index ); ?>">
			<?php echo self::get_label( $field ); ?>
			<?php if ( empty( $field->args['options'] ) ): ?>
				<?php $checked = checked( in_array( $val, $current_value ), true, false ); ?>
				<input type="checkbox" id="<?php echo esc_attr( $field->get_input_id( $field->index ) ); ?>" name="<?php echo esc_attr( $field->get_name() ) ?>" value="<?php echo esc_attr( $val ) ?>" <?php echo $checked; ?> />
			<?php else: ?>
				<?php foreach ($field->args['options'] as $key => $value): ?>
					<?php $checked = checked( in_array( $key, $current_value ) , true, false ); ?>
					<label style="display:<?php echo $item_format; ?>" class="voce-meta-checkbox">
						<input type="checkbox" id="<?php echo esc_attr( $field->get_input_id( $field->index ) . '_' . $key ); ?>" name="<?php echo esc_attr( $field->get_name( $field->index ) ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php echo $checked; ?> /><?php echo wp_kses( $value, Voce_Meta_API::GetInstance()->label_allowed_html ); ?>
					</label>
				<?php endforeach; ?>
			<?php endif; ?>
			<?php echo !empty( $field->description ) ? ('<br><span class="description">' . wp_kses( $field->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</span>') : ''; ?>
		</p>
		<?php
	}

	/**
	 * @method display_checkbox_template
	 *
	 * @param iVoce_Meta_Field $field
	 * @param type $current_value
	 * @param integer $post_id
	 */
	public static function display_checkbox_template( $field, $current_value, $post_id ) {
		$item_format = ! empty( $field->args['item_format'] ) && in_array( $field->args['item_format'], array( 'inline', 'block' ) ) ? $field->args['item_format'] : 'block';
		?>
		<p id="<?php echo esc_attr( $field->get_wrapper_id() ); ?>" class="<?php echo esc_attr( $field->get_wrapper_id() ); ?> ">
			<?php echo self::get_label( $field ); ?>
			<?php if ( empty( $field->args['options'] ) ): ?>
				<input type="checkbox" id="<?php echo esc_attr( $field->get_input_id() ); ?>" name="<?php echo esc_attr( $field->get_name() ) ?>" />
			<?php else: ?>
				<?php foreach ($field->args['options'] as $key => $value): ?>
					<label style="display:<?php echo $item_format; ?>" class="voce-meta-checkbox">
						<input type="checkbox" id="<?php echo esc_attr( $field->get_input_id( 'VPM_CLONE_INDEX' ) . '_' . $key ); ?>" name="<?php echo esc_attr( $field->get_name( 'VPM_CLONE_INDEX' ) . '[' . $key . ']') ?>" /><?php echo wp_kses( $value, Voce_Meta_API::GetInstance()->label_allowed_html ); ?>
					</label>
				<?php endforeach; ?>
			<?php endif; ?>
			<?php echo !empty( $field->description ) ? ('<br><span class="description">' . wp_kses( $field->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</span>') : ''; ?>
		</p>
		<?php
	}
}

class Voce_Meta_Field_Radio extends Voce_Meta_Field {

	/**
	 * @constructor
	 *
	 * @param string $type
	 * @param string $group
	 * @param integer $id
	 * @param string $label
	 * @param array $args
	 */
	public function __construct( $type, $group, $id, $label, $args = array() ) {
		parent::__construct( $type, $group, $id, $label, $args );

		$this->multiple = false;
	}

	/**
	 * @method render_clone_radio_js
	 *
	 * @param iVoce_Meta_Field $field
	 * @param integer $post_id
	 */
	public static function render_clone_radio_js( $field, $post_id ) {
		?>
		<script type="application/javascript">
			if (typeof window.vpm_clone_radio === 'undefined') {
				window.vpm_clone_radio = function(addButton) {
					var $addButton = jQuery(addButton),
						wrapperId = $addButton.data('wrapper'),
						elId = $addButton.data('id'),
						template = jQuery('#' + wrapperId).clone(),
						$elements = template.add(template.find('*')),
						multiple_index = +$addButton.data('multiple_index'),
						del_button;

					$addButton.attr('data-multiple_index', multiple_index + 1).data('multiple_index', multiple_index + 1);

					jQuery.each($elements, function() {
						var $el = jQuery(this),
							index_position,
							new_position;

						if ($el.attr('type') == 'radio') {
							jQuery.each(['name','id'], function(k, field) {
								index_position = $el.attr(field).indexOf('VPM_CLONE_INDEX');
								if ( index_position > 0 ) {
									new_val = [$el.attr(field).slice(0, index_position), multiple_index, $el.attr(field).slice(index_position + 15)].join('');
									$el.attr(field, new_val);
								}
							});
						}
						jQuery.each($el.get(0).attributes, function() {
							if (this.value == elId) {
								$el.attr(this.name, this.value + '-' + multiple_index);
							}
							if (this.value == wrapperId) {
								$el.attr(this.name, this.value + '-' + multiple_index);
							}
						});

					});

					template.attr('data-multiple_index', multiple_index).data('multiple_index', multiple_index)
						.removeClass('hidden');

					del_button = jQuery('.vpm_multiple-delete').first().clone()
						.attr('data-wrapper', wrapperId).data('wrapper', wrapperId)
						.attr('data-multiple_index', multiple_index).data('multiple_index', multiple_index);

					$addButton.before(del_button).before(template);
				};
			}
		</script>
		<?php
	}

	/**
	 * @method display_radio
	 *
	 * @param iVoce_Meta_Field $field
	 * @param type $current_value
	 * @param integer $post_id
	 */
	public static function display_radio( $field, $current_value, $post_id ) {
		$item_format = ! empty( $field->args['item_format'] ) && in_array( $field->args['item_format'], array( 'inline', 'block' ) ) ? $field->args['item_format'] : 'block';
		?>
		<p id="<?php echo esc_attr( $field->get_wrapper_id( $field->index ) ); ?>" class="<?php echo esc_attr( $field->get_wrapper_id() ); ?>" data-multiple_index="<?php echo esc_attr( $field->index ); ?>">
			<?php echo self::get_label( $field ); ?>
			<?php foreach ($field->args['options'] as $key => $value): ?>
				<label style="display:<?php echo $item_format; ?>" class="voce-meta-radio">
					<input type="radio" id="<?php echo esc_attr( $field->get_input_id( $field->index ) . '_' . $key ); ?>" name="<?php echo esc_attr( $field->get_name( $field->index ) ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( $current_value, $key ); ?> /><?php echo wp_kses( $value, Voce_Meta_API::GetInstance()->label_allowed_html ); ?>
				</label>
			<?php endforeach; ?>
			<?php echo !empty( $field->description ) ? ('<br><span class="description">' . wp_kses( $field->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</span>') : ''; ?>
		</p>
		<?php
	}

	/**
	 * @method display_radio_template
	 *
	 * @param iVoce_Meta_Field $field
	 * @param type $current_value
	 * @param integer $post_id
	 */
	public static function display_radio_template( $field, $current_value, $post_id ) {
		$item_format = ! empty( $field->args['item_format'] ) && in_array( $field->args['item_format'], array( 'inline', 'block' ) ) ? $field->args['item_format'] : 'block';
		?>
		<input type="hidden" name="<?php echo esc_attr( $field->get_name() ); ?>" />
		<p id="<?php echo esc_attr( $field->get_wrapper_id() ); ?>" class="<?php echo esc_attr( $field->get_wrapper_id() ); ?>">
			<?php echo self::get_label( $field ); ?>
			<?php foreach ($field->args['options'] as $key => $value): ?>
				<label style="display:<?php echo $item_format; ?>" class="voce-meta-radio">
					<input type="radio" id="<?php echo esc_attr( $field->get_input_id( 'VPM_CLONE_INDEX' ) . '_' . $key ); ?>" name="<?php echo esc_attr( $field->get_name( 'VPM_CLONE_INDEX' ) ); ?>" value="<?php echo esc_attr( $key ); ?>" /><?php echo wp_kses( $value, Voce_Meta_API::GetInstance()->label_allowed_html ); ?>
				</label>
			<?php endforeach; ?>
			<?php echo !empty( $field->description ) ? ('<br><span class="description">' . wp_kses( $field->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</span>') : ''; ?>
		</p>
		<?php
	}
}


class Voce_Meta_Field_WP_Editor extends Voce_Meta_Field {

	/**
	 * @method init
	 */
	public static function init() {
		add_filter( 'wp_ajax_vpm_add_wp_editor', function() {
			$field_id = $_POST['field_id'];
			$args = $_POST['field_args'];

			wp_editor( '', $field_id, $args );
			exit;
		} );
	}

	/**
	 * @method render_clone_wp_editor_js
	 *
	 * @param iVoce_Meta_Field $field
	 * @param integer $post_id
	 */
	public static function render_clone_wp_editor_js( $field, $post_id ) {
		?>
		<script type="application/javascript">
			if (typeof window.vpm_clone_wp_editor === 'undefined') {
				window.vpm_clone_wp_editor = function(addButton) {
					var $addButton = jQuery(addButton),
						wrapperId = $addButton.data('wrapper'),
						elId = $addButton.data('id'),
						template = jQuery('#' + wrapperId).clone(),
						$elements = template.add(template.find('*')),
						multiple_index = +$addButton.data('multiple_index'),
						del_button;

					$addButton.attr('data-multiple_index', multiple_index + 1).data('multiple_index', multiple_index + 1);

					jQuery.each($elements, function() {
						var $el = jQuery(this);

						jQuery.each($el.get(0).attributes, function() {
							if (this.value == elId) {
								$el.attr(this.name, this.value + '-' + multiple_index);
							}
							if (this.value == wrapperId) {
								$el.attr(this.name, this.value + '-' + multiple_index);
							}
						});
					});

					template.attr('data-multiple_index', multiple_index).data('multiple_index', multiple_index)
						.removeClass('hidden');

					del_button = jQuery('.vpm_multiple-delete').first().clone()
						.attr('data-wrapper', wrapperId).data('wrapper', wrapperId)
						.attr('data-multiple_index', multiple_index).data('multiple_index', multiple_index);

					$addButton.before(del_button).before(template);

					jQuery.ajax({
						dataType: 'html',
						async: false,
						type: 'POST',
						url: ajaxurl,
						data: {
							action: 'vpm_add_wp_editor',
							field_id: elId + '_' + multiple_index,
							field_args: vpm_clone_wp_editor_args[elId]
						},
						success: function (resp) {
							if (resp) {
								jQuery('#'+wrapperId+'-'+multiple_index+' .wp-editor-wrapper').html(resp);
							}

							// init tinymce
							quicktags({id : elId + '_' + multiple_index});
							tinymce.execCommand( 'mceAddEditor', false, elId + '_' + multiple_index );
						}
					});
				};
			}
		</script>
		<?php
	}

	/**
	 * @method display_wp_editor
	 *
	 * @param iVoce_Meta_Field $field
	 * @param type $current_value
	 * @param integer $post_id
	 */
	public static function display_wp_editor( $field, $current_value, $post_id ) {
		$field->args['wp_editor_args']['textarea_name'] = $field->get_name();
		?>
		<div id="<?php echo esc_attr( $field->get_wrapper_id( $field->index ) ); ?>" class="<?php echo esc_attr( $field->get_wrapper_id() ); ?> voce-post-meta-wp-editor" data-multiple_index="<?php echo esc_attr( $field->index ); ?>">
			<?php echo self::get_label($field);
			echo '<div class="wp-editor-wrapper">';
			wp_editor( $current_value, $field->get_input_id( $field->index, '_' ), $field->args['wp_editor_args'] );
			echo '</div>';
			echo !empty( $field->description ) ? ('<br><span class="description">' . wp_kses( $field->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</span>') : '';
			?>
		</div>
		<?php
	}

	/**
	 * @method display_wp_editor_template
	 *
	 * @param iVoce_Meta_Field $field
	 * @param type $current_value
	 * @param integer $post_id
	 */
	public static function display_wp_editor_template( $field, $current_value, $post_id ) {
		$field->args['wp_editor_args']['textarea_name'] = $field->get_name();
		?>
		<input type="hidden" name="<?php echo esc_attr( $field->get_name() ); ?>" />
		<div id="<?php echo esc_attr( $field->get_wrapper_id() ); ?>" class="<?php echo esc_attr( $field->get_wrapper_id() ); ?> voce-post-meta-wp-editor">
			<?php echo self::get_label($field);
			echo '<div class="wp-editor-wrapper"></div>';
			echo !empty( $field->description ) ? ('<br><span class="description">' . wp_kses( $field->description, Voce_Meta_API::GetInstance()->description_allowed_html ) . '</span>') : '';
			?>
		</div>
		<script type="application/javascript">
			var vpm_clone_wp_editor_args = vpm_clone_wp_editor_args || [];

			vpm_clone_wp_editor_args['<?php echo esc_js( $field->get_input_id() ); ?>'] = <?php echo json_encode( $field->args['wp_editor_args'] ); ?>
		</script>
	<?php
	}

	/**
	 * @method sanitize_wp_editor
	 *
	 * @param string $field
	 * @param type $old_value
	 * @param type $new_value
	 * @param integer $post_id
	 *
	 * @return mixed
	 */
	public static function sanitize_wp_editor( $field, $old_value, $new_value, $post_id ) {
		return wp_kses( $new_value, wp_kses_allowed_html( 'post' ) );
	}
}

Voce_Meta_Field_WP_Editor::init();


/**
 * @method add_metadata_group
 *
 * @param string $id
 * @param string $title
 * @param array $args
 *
 * @return Voce_Meta_Group
 */
function add_metadata_group( $id, $title, $args = array() ) {
	return Voce_Meta_API::GetInstance()->add_group( $id, $title, $args );
}

/**
 * @method add_metadata_field
 *
 * @param string $group
 * @param integer $id
 * @param string $label
 * @param string $type
 * @param array $args
 *
 * @return boolean
 */
function add_metadata_field( $group, $id, $label, $type = 'text', $args = array() ) {
	$api = Voce_Meta_API::GetInstance();
	if ( isset( $api->groups[ $group ] ) ) {
		$func = "add_field_{$type}";
		return $api->groups[ $group ]->$func( $id, $label, $args );
	}

	return false;
}

/**
 * @method remove_metadata_field
 *
 * @param string $group
 * @param integer $id
 *
 * @return boolean
 */
function remove_metadata_field( $group, $id ) {
	$api = Voce_Meta_API::GetInstance();
	if ( isset( $api->groups[ $group ] ) ) {
		return $api->groups[ $group ]->remove_field( $id );
	}

	return false;
}

/**
 * @method get_vpm_value
 *
 * @param string $group
 * @param string $field
 * @param integer $post_id
 */
function get_vpm_value( $group, $field, $post_id = false ){
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	return Voce_Meta_API::GetInstance()->get_meta_value( $post_id, $group, $field );
}

endif;