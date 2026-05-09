<?php
/**
 * Footer Settings
 *
 * @package Travel_Agency
 */

function travel_agency_customize_register_footer( $wp_customize ) {
    
    $wp_customize->add_section(
        'footer_settings',
        array(
            'title'      => __( 'Footer Settings', 'travel-agency' ),
            'priority'   => 500,
            'capability' => 'edit_theme_options',
        )
    );
    
    /** Footer Copyright */
    $wp_customize->add_setting(
        'footer_copyright',
        array(
            'default'           => '',
            'sanitize_callback' => 'wp_kses_post',
            'transport'         => 'postMessage'
        )
    );
    
    $wp_customize->add_control(
        'footer_copyright',
        array(
            'label'   => __( 'Footer Copyright', 'travel-agency' ),
            'section' => 'footer_settings',
            'type'    => 'textarea',
        )
    );
    
    $wp_customize->selective_refresh->add_partial( 'footer_copyright', array(
        'selector' => '.footer-b .copyright',
        'render_callback' => 'travel_agency_get_footer_copyright',
    ) );
    
    /** Note */
    $wp_customize->add_setting(
        'footer_text',
        array(
            'default'           => '',
            'sanitize_callback' => 'wp_kses_post' 
        )
    );

    $wp_customize->add_control(
        new Travel_Agency_Note_Control( 
            $wp_customize,
            'footer_text',
            array(
                'section'     => 'footer_settings',
                'description' => sprintf( __( '%1$sThis feature is available in Pro version.%2$s %3$sUpgrade to Pro%4$s ', 'travel-agency' ),'<div class="featured-pro"><span>', '</span>', '<a href="https://rarathemes.com/wordpress-themes/travel-agency-pro/?utm_source=travel_agency&utm_medium=customizer&utm_campaign=upgrade_to_pro" target="_blank">', '</a></div>' ),
            )
        )
    );


    $wp_customize->add_setting( 
        'footer_image_settings', 
        array(
            'default'           => 'one',
            'sanitize_callback' => 'travel_agency_sanitize_radio',
        ) 
    );

    $wp_customize->add_control(
        new Travel_Agency_Radio_Image_Control(
            $wp_customize,
            'footer_image_settings',
            array(
                'section'     => 'footer_settings',
                'feat_class' => 'upg-to-pro',
                'choices'     => array(
                    'one'       => get_template_directory_uri() . '/images/pro/footer.png',
                ),
            )
        )
    );
    
}
add_action( 'customize_register', 'travel_agency_customize_register_footer' );