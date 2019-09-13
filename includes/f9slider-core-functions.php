<?php
/**
 * F9slider Core Functions
 *
 * General core functions available on both the front-end and admin.
 *
 * @package F9slider\Functions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers navigation menu locations for a theme.
 *
 * @global array $_f9_registred_sliders
 *
 * @param array $locations Associative array of menu location identifiers (like a slug) and descriptive text.
 */
function register_image_sliders( $locations = array() ) {
	global $_f9_registred_sliders;

	add_theme_support( 'image-sliders' );

	$_f9_registred_sliders = array_merge( (array) $_f9_registred_sliders, $locations );
}

/**
 * Unregisters a navigation menu location for a theme.
 *
 * @global array $_f9_registred_sliders
 *
 * @param string $location The menu location identifier.
 * @return bool True on success, false on failure.
 */
function unregister_image_sliders( $location ) {
	global $_f9_registred_sliders;

	if ( is_array( $_f9_registred_sliders ) && isset( $_f9_registred_sliders[ $location ] ) ) {
		unset( $_f9_registred_sliders[ $location ] );
		if ( empty( $_f9_registred_sliders ) ) {
			_remove_theme_support( 'image-sliders' );
		}
		return true;
	}
	return false;
}

/**
 * Displays a slider of images.
 *
 * @staticvar array $slider_id_slugs
 *
 * @param array $args {
 *     Optional. Array of nav menu arguments.
 *
 *     @type int|string|WP_Term $slider            Desired menu. Accepts a menu ID, slug, name, or object. Default empty.
 *     @type string             $slider_class      CSS class to use for the ul element which forms the menu. Default 'menu'.
 *     @type string             $slider_id         The ID that is applied to the ul element which forms the menu.
 *                                               Default is the menu slug, incremented.
 *     @type string             $container       Whether to wrap the ul, and what to wrap it with. Default 'div'.
 *     @type string             $container_class Class that is applied to the container. Default 'menu-{menu slug}-container'.
 *     @type string             $container_id    The ID that is applied to the container. Default empty.
 *     @type callable|bool      $fallback_cb     If the menu doesn't exists, a callback function will fire.
 *                                               Default is 'wp_page_menu'. Set to false for no fallback.
 *     @type string             $before          Text before the link markup. Default empty.
 *     @type string             $after           Text after the link markup. Default empty.
 *     @type string             $link_before     Text before the link text. Default empty.
 *     @type string             $link_after      Text after the link text. Default empty.
 *     @type bool               $echo            Whether to echo the menu or return it. Default true.
 *     @type int                $depth           How many levels of the hierarchy are to be included. 0 means all. Default 0.
 *     @type object             $walker          Instance of a custom walker class. Default empty.
 *     @type string             $theme_location  Theme location to be used. Must be registered with register_image_sliders()
 *                                               in order to be selectable by the user.
 *     @type string             $items_wrap      How the list items should be wrapped. Default is a ul with an id and class.
 *                                               Uses printf() format with numbered placeholders.
 *     @type string             $item_spacing    Whether to preserve whitespace within the menu's HTML. Accepts 'preserve' or 'discard'. Default 'preserve'.
 * }
 * @return string|false|void Menu output if $echo is false, false if there are no items or no menu was found.
 */
function f9_image_slider( $args = array() ) {
	static $slider_id_slugs = array();

	$defaults = array(
		'menu'            => '',
		'container'       => 'div',
		'container_class' => '',
		'container_id'    => '',
		'menu_class'      => 'menu',
		'menu_id'         => '',
		'echo'            => true,
		'fallback_cb'     => 'wp_page_menu',
		'before'          => '',
		'after'           => '',
		'link_before'     => '',
		'link_after'      => '',
		'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
		'item_spacing'    => 'preserve',
		'depth'           => 0,
		'walker'          => '',
		'theme_location'  => '',
	);

	$args = wp_parse_args( $args, $defaults );

	if ( ! in_array( $args['item_spacing'], array( 'preserve', 'discard' ), true ) ) {
		// invalid value, fall back to default.
		$args['item_spacing'] = $defaults['item_spacing'];
	}

	/**
	 * Filters the arguments used to display a navigation menu.
	 *
	 * @see f9_image_slider()
	 *
	 * @param array $args Array of f9_image_slider() arguments.
	 */
	$args = apply_filters( 'f9_image_slider_args', $args );
	$args = (object) $args;

	/**
	 * Filters whether to short-circuit the f9_image_slider() output.
	 *
	 * Returning a non-null value to the filter will short-circuit
	 * f9_image_slider(), echoing that value if $args->echo is true,
	 * returning that value otherwise.=
	 *
	 * @see f9_image_slider()
	 *
	 * @param string|null $output Nav menu output to short-circuit with. Default null.
	 * @param stdClass    $args   An object containing f9_image_slider() arguments.
	 */
	$image_sliders = apply_filters( 'pre_f9_image_slider', null, $args );

	if ( null !== $image_sliders ) {
		if ( $args->echo ) {
			echo $image_sliders;
			return;
		}

		return $image_sliders;
	}

	// Get the nav menu based on the requested menu
	$slider = wp_get_image_slider_object( $args->menu );

	// Get the nav menu based on the theme_location
	if ( ! $slider && $args->theme_location && ( $locations = get_image_sliders_locations() ) && isset( $locations[ $args->theme_location ] ) ) {
		$slider = wp_get_image_slider_object( $locations[ $args->theme_location ] );
	}

	// get the first menu that has items if we still can't find a menu
	if ( ! $slider && ! $args->theme_location ) {
		$sliders = wp_get_image_sliderss();
		foreach ( $sliders as $slider_maybe ) {
			if ( $slider_items = wp_get_image_sliders_items( $slider_maybe->term_id, array( 'update_post_term_cache' => false ) ) ) {
				$slider = $slider_maybe;
				break;
			}
		}
	}

	if ( empty( $args->menu ) ) {
		$args->menu = $slider;
	}

	// If the menu exists, get its items.
	if ( $slider && ! is_wp_error( $slider ) && ! isset( $slider_items ) ) {
		$slider_items = wp_get_image_sliders_items( $slider->term_id, array( 'update_post_term_cache' => false ) );
	}

	/*
	 * If no menu was found:
	 *  - Fall back (if one was specified), or bail.
	 *
	 * If no menu items were found:
	 *  - Fall back, but only if no theme location was specified.
	 *  - Otherwise, bail.
	 */
	if ( ( ! $slider || is_wp_error( $slider ) || ( isset( $slider_items ) && empty( $slider_items ) && ! $args->theme_location ) )
		&& isset( $args->fallback_cb ) && $args->fallback_cb && is_callable( $args->fallback_cb ) ) {
			return call_user_func( $args->fallback_cb, (array) $args );
	}

	if ( ! $slider || is_wp_error( $slider ) ) {
		return false;
	}

	$image_sliders = $items = '';

	$show_container = false;
	if ( $args->container ) {
		/**
		 * Filters the list of HTML tags that are valid for use as menu containers.
		 *
		 * @param array $tags The acceptable HTML tags for use as menu containers.
		 *                    Default is array containing 'div' and 'nav'.
		 */
		$allowed_tags = apply_filters( 'f9_image_slider_container_allowedtags', array( 'div', 'nav' ) );
		if ( is_string( $args->container ) && in_array( $args->container, $allowed_tags ) ) {
			$show_container = true;
			$class          = $args->container_class ? ' class="' . esc_attr( $args->container_class ) . '"' : ' class="menu-' . $slider->slug . '-container"';
			$id             = $args->container_id ? ' id="' . esc_attr( $args->container_id ) . '"' : '';
			$image_sliders      .= '<' . $args->container . $id . $class . '>';
		}
	}

	// Set up the $slider_item variables
	_wp_menu_item_classes_by_context( $slider_items );

	$sorted_menu_items = $slider_items_with_children = array();
	foreach ( (array) $slider_items as $slider_item ) {
		$sorted_menu_items[ $slider_item->menu_order ] = $slider_item;
		if ( $slider_item->menu_item_parent ) {
			$slider_items_with_children[ $slider_item->menu_item_parent ] = true;
		}
	}

	// Add the menu-item-has-children class where applicable
	if ( $slider_items_with_children ) {
		foreach ( $sorted_menu_items as &$slider_item ) {
			if ( isset( $slider_items_with_children[ $slider_item->ID ] ) ) {
				$slider_item->classes[] = 'menu-item-has-children';
			}
		}
	}

	unset( $slider_items, $slider_item );

	/**
	 * Filters the sorted list of menu item objects before generating the menu's HTML.
	 *
	 * @param array    $sorted_menu_items The menu items, sorted by each menu item's menu order.
	 * @param stdClass $args              An object containing f9_image_slider() arguments.
	 */
	$sorted_menu_items = apply_filters( 'f9_image_slider_objects', $sorted_menu_items, $args );

	$items .= walk_image_sliders_tree( $sorted_menu_items, $args->depth, $args );
	unset( $sorted_menu_items );

	// Attributes
	if ( ! empty( $args->menu_id ) ) {
		$wrap_id = $args->menu_id;
	} else {
		$wrap_id = 'menu-' . $slider->slug;
		while ( in_array( $wrap_id, $slider_id_slugs ) ) {
			if ( preg_match( '#-(\d+)$#', $wrap_id, $matches ) ) {
				$wrap_id = preg_replace( '#-(\d+)$#', '-' . ++$matches[1], $wrap_id );
			} else {
				$wrap_id = $wrap_id . '-1';
			}
		}
	}
	$slider_id_slugs[] = $wrap_id;

	$wrap_class = $args->menu_class ? $args->menu_class : '';

	/**
	 * Filters the HTML list content for navigation menus.
	 *
	 * @see f9_image_slider()
	 *
	 * @param string   $items The HTML list content for the menu items.
	 * @param stdClass $args  An object containing f9_image_slider() arguments.
	 */
	$items = apply_filters( 'f9_image_slider_items', $items, $args );
	/**
	 * Filters the HTML list content for a specific navigation menu.
	 *
	 * @see f9_image_slider()
	 *
	 * @param string   $items The HTML list content for the menu items.
	 * @param stdClass $args  An object containing f9_image_slider() arguments.
	 */
	$items = apply_filters( "f9_image_slider_{$slider->slug}_items", $items, $args );

	// Don't print any markup if there are no items at this point.
	if ( empty( $items ) ) {
		return false;
	}

	$image_sliders .= sprintf( $args->items_wrap, esc_attr( $wrap_id ), esc_attr( $wrap_class ), $items );
	unset( $items );

	if ( $show_container ) {
		$image_sliders .= '</' . $args->container . '>';
	}

	/**
	 * Filters the HTML content for navigation menus.
	 *
	 * @see f9_image_slider()
	 *
	 * @param string   $image_sliders The HTML content for the navigation menu.
	 * @param stdClass $args     An object containing f9_image_slider() arguments.
	 */
	$image_sliders = apply_filters( 'f9_image_slider', $image_sliders, $args );

	if ( $args->echo ) {
		echo $image_sliders;
	} else {
		return $image_sliders;
	}
}

/**
 * Returns a image slider object.
 *
 * @param int|string|WP_Term $slider Menu ID, slug, name, or object.
 * @return WP_Term|false False if $slider param isn't supplied or term does not exist, menu object if successful.
 */
function wp_get_image_slider_object( $slider ) {
	$slider_obj = false;

	if ( is_object( $slider ) ) {
		$slider_obj = $slider;
	}

	if ( $slider && ! $slider_obj ) {
		$slider_obj = get_term( $slider, 'nav_menu' );

		if ( ! $slider_obj ) {
			$slider_obj = get_term_by( 'slug', $slider, 'nav_menu' );
		}

		if ( ! $slider_obj ) {
			$slider_obj = get_term_by( 'name', $slider, 'nav_menu' );
		}
	}

	if ( ! $slider_obj || is_wp_error( $slider_obj ) ) {
		$slider_obj = false;
	}

	/**
	 * Filters the nav_menu term retrieved for wp_get_image_slider_object().
	 *
	 * @param WP_Term|false      $slider_obj Term from nav_menu taxonomy, or false if nothing had been found.
	 * @param int|string|WP_Term $slider     The menu ID, slug, name, or object passed to wp_get_image_slider_object().
	 */
	return apply_filters( 'wp_get_image_slider_object', $slider_obj, $slider );
}
