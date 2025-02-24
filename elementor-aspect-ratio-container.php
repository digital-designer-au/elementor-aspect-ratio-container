<?php
/**
 * Plugin Name: Elementor Aspect Ratio Container
 * Plugin URI: https://fellow.agency/
 * Description: Adds an Aspect Ratio option to the default Elementor Container widget.
 * Version: 1.1.1
 * Author: Fellow Agency
 * Author URI: https://fellow.agency/
 * Text Domain: elementor-aspect-ratio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Check if Elementor is active.
 */
function ear_check_elementor_active() {
	// Check if Elementor is installed.
	if ( ! defined( 'ELEMENTOR_VERSION' ) || ! defined( 'ELEMENTOR_PATH' ) ) {
		add_action( 'admin_notices', 'ear_admin_notice_missing_elementor' );
		return false;
	}
	// Check if Elementor is activated.
	if ( ! class_exists( '\Elementor\Plugin' ) ) {
		add_action( 'admin_notices', 'ear_admin_notice_missing_elementor' );
		return false;
	}
	return true;
}

function ear_admin_notice_missing_elementor() {
	echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Elementor Aspect Ratio Container requires Elementor to be installed and activated.', 'elementor-aspect-ratio' ) . '</p></div>';
}

/**
 * Initialise the plugin.
 */
function ear_init() {
	if ( ear_check_elementor_active() ) {
		// Add custom controls to the container widget.
		add_action( 'elementor/element/container/section_layout/after_section_end', 'ear_add_aspect_ratio_control', 10, 2 );
		// Enqueue editor-specific styles.
		add_action( 'elementor/editor/after_enqueue_scripts', 'ear_enqueue_editor_styles' );
		// Apply the aspect ratio CSS for both frontend and editor.
		add_action( 'elementor/frontend/container/before_render', 'ear_apply_aspect_ratio_css' );
		add_action( 'elementor/element/after_add_attributes', 'ear_apply_aspect_ratio_css' );
	}
}
add_action( 'plugins_loaded', 'ear_init' );

/**
 * Extend the container widget controls.
 */
function ear_add_aspect_ratio_control( $element, $args ) {

	$element->start_controls_section(
		'section_aspect_ratio',
		[
			'label' => __( 'Aspect Ratio', 'elementor-aspect-ratio' ),
			'tab'   => \Elementor\Controls_Manager::TAB_LAYOUT,
		]
	);

	$element->add_control(
		'aspect_ratio',
		[
			'label'   => __( 'Aspect Ratio', 'elementor-aspect-ratio' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'none',
			'options' => [
				'none'   => __( 'None - default', 'elementor-aspect-ratio' ),
				'1-1'    => '1:1',
				'16-9'   => '16:9',
				'3-2'    => '3:2',
				'2-3'    => '2:3',
				'4-3'    => '4:3',
				'3-4'    => '3:4',
				'custom' => __( 'Custom', 'elementor-aspect-ratio' ),
			],
		]
	);

	$element->add_control(
		'aspect_ratio_custom_x',
		[
			'label'     => __( 'Aspect Ratio X', 'elementor-aspect-ratio' ),
			'type'      => \Elementor\Controls_Manager::NUMBER,
			'default'   => '',
			'condition' => [
				'aspect_ratio' => 'custom',
			],
		]
	);

	$element->add_control(
		'aspect_ratio_custom_y',
		[
			'label'     => __( 'Aspect Ratio Y', 'elementor-aspect-ratio' ),
			'type'      => \Elementor\Controls_Manager::NUMBER,
			'default'   => '',
			'condition' => [
				'aspect_ratio' => 'custom',
			],
		]
	);

	$element->end_controls_section();
}

/**
 * Enqueue editor-specific styles for a better aspect ratio preview.
 */
function ear_enqueue_editor_styles() {
	wp_add_inline_style( 'elementor-editor', '
		/* Ensure the widget container is positioned for the fallback */
		.elementor-edit-mode .elementor-element[data-aspect-ratio="true"] {
			position: relative !important;
			overflow: hidden !important;
		}
		/* Fallback using a padding hack for editor preview */
		.elementor-edit-mode .elementor-element[data-aspect-ratio="true"]::before {
			content: "";
			display: block;
			/* Calculate padding-top as 100% divided by the numeric ratio */
			padding-top: calc(100% / var(--ear-aspect-ratio-value)) !important;
		}
		/* Ensure the inner container fills the parent */
		.elementor-edit-mode .elementor-element[data-aspect-ratio="true"] > .elementor-container {
			position: absolute !important;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
		}
	' );
}

/**
 * Apply inline CSS to enforce the selected aspect ratio.
 */
function ear_apply_aspect_ratio_css( $widget ) {
	// Only target the default container widget.
	if ( 'container' !== $widget->get_name() ) {
		return;
	}

	$settings = $widget->get_settings_for_display();
	if ( empty( $settings['aspect_ratio'] ) || 'none' === $settings['aspect_ratio'] ) {
		return;
	}

	$ratio_value  = '';
	$numeric_ratio = '';

	if ( 'custom' === $settings['aspect_ratio'] ) {
		if ( ! empty( $settings['aspect_ratio_custom_x'] ) && ! empty( $settings['aspect_ratio_custom_y'] ) ) {
			$ratio_value  = $settings['aspect_ratio_custom_x'] . ' / ' . $settings['aspect_ratio_custom_y'];
			$numeric_ratio = (float) $settings['aspect_ratio_custom_x'] / (float) $settings['aspect_ratio_custom_y'];
		}
	} else {
		$parts = explode( '-', $settings['aspect_ratio'] );
		if ( count( $parts ) === 2 ) {
			$ratio_value  = $parts[0] . ' / ' . $parts[1];
			$numeric_ratio = (float) $parts[0] / (float) $parts[1];
		}
	}

	if ( $ratio_value && $numeric_ratio ) {
		$styles = sprintf(
			'--ear-aspect-ratio: %s; --ear-aspect-ratio-value: %s; aspect-ratio: %s; display: block;',
			esc_attr( $ratio_value ),
			esc_attr( $numeric_ratio ),
			esc_attr( $ratio_value )
		);
		$widget->add_render_attribute( '_wrapper', 'style', $styles );
		$widget->add_render_attribute( '_wrapper', 'data-aspect-ratio', 'true' );
	}
}
