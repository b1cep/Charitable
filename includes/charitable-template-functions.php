<?php 

/**
 * Charitable Template Functions. 
 *
 * Template functions.
 * 
 * @package 	Charitable/Functions/Template
 * @version     1.0.0
 * @author 		Eric Daams
 * @copyright 	Copyright (c) 2014, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License  
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Displays a template. 
 *
 * @param 	string|array 	$template_name 		A single template name or an ordered array of template
 * @param 	bool 		 	$load 				If true the template file will be loaded if it is found.
 * @param 	bool 			$require_once 		Whether to require_once or require. Default true. Has no effect if $load is false. 
 * @return 	Charitable_Template
 * @since 	1.0.0
 */
function charitable_template( $template_name, $load = true, $require_once = true ) {
	return new Charitable_Template( $template_name, $load, $require_once ); 
}

/**
 * Displays a template. 
 *
 * @param 	string 	$slug
 * @param 	string 	$name 		Optional name.
 * @return 	Charitable_Template_Part
 * @since 	1.0.0
 */
function charitable_template_part( $slug, $name = "" ) {
	return new Charitable_Template_Part( $slug, $name );
}

/**
 * Return the URL for a given page. 
 *
 * Example usage: 
 * 
 * - charitable_get_permalink( 'campaign_donation_page' );
 * - charitable_get_permalink( 'login_page' );
 * - charitable_get_permalink( 'registration_page' );
 * - charitable_get_permalink( 'profile_page' );
 *
 * @param 	string 	$page
 * @param   array 	$args 		Optional array of arguments.        
 * @return  string|false        String if page is found. False if none found.
 * @since   1.0.0
 */
function charitable_get_permalink( $page, $args = array() ) {
    return apply_filters( 'charitable_permalink_' . $page, false, $args );
}

/**
 * Checks whether we are currently looking at the given page. 
 *
 * Example usage: 
 * 
 * - charitable_is_page( 'campaign_donation_page' );
 * - charitable_is_page( 'login_page' );
 * - charitable_is_page( 'registration_page' );
 * - charitable_is_page( 'profile_page' );
 *
 * @param   string 	$page 
 * @param 	array 	$args 		Optional array of arguments.
 * @return  boolean
 * @since   1.0.0
 */
function charitable_is_page( $page, $args = array() ) {
    return apply_filters( 'charitable_is_page_' . $page, false, $args );
}

/**
 * Returns the URL for the campaign donation page. 
 *
 * This is used when you call charitable_get_permalink( 'campaign_donation_page' ). In
 * general, you should use charitable_get_permalink() instead since it will
 * take into account permalinks that have been filtered by plugins/themes.
 *
 * @global 	WP_Rewrite 	$wp_rewrite
 * @param 	string 		$url
 * @param 	array 		$args
 * @return 	string
 * @since 	1.0.0
 */
function charitable_get_campaign_donation_page_permalink( $url, $args = array() ) {
	global $wp_rewrite;

	$campaign_id = isset( $args[ 'campaign_id' ] ) ? $args[ 'campaign_id' ] : get_the_ID();

	if ( $wp_rewrite->using_permalinks() ) {
		$url = trailingslashit( get_permalink( $campaign_id ) ) . '/donate/';
	}
	else {
		$url = esc_url_raw( add_query_arg( array( 'donate' => 1 ), get_permalink( $campaign_id ) ) );	
	}
			
	return $url;
}	

add_filter( 'charitable_permalink_campaign_donation_page', 'charitable_get_campaign_donation_page_permalink', 2, 2 );		

/**
 * Returns the url of the widget page. 
 *
 * This is used when you call charitable_get_permalink( 'campaign_widget_page' ). In
 * general, you should use charitable_get_permalink() instead since it will
 * take into account permalinks that have been filtered by plugins/themes.
 *
 * @param 	string 		$url
 * @param 	array 		$args
 * @return  string
 * @since   1.0.0
 */
function charitable_get_campaign_widget_page_permalink( $url, $args = array() ) {	
	return $url;
}

add_filter( 'charitable_permalink_campaign_widget_page', 'charitable_get_campaign_widget_page_permalink', 2, 2 );

/**
 * Checks whether the current request is for the given page. 
 *
 * This is used when you call charitable_is_page( 'campaign_donation_page' ). 
 * In general, you should use charitable_is_page() instead since it will
 * take into account any filtering by plugins/themes.
 *
 * @global 	WP_Query 	$wp_query
 * @param 	boolean 	$ret	 
 * @param 	array 		$args 
 * @return 	boolean
 * @since 	1.0.0
 */
function charitable_is_campaign_donation_page( $ret = false, $args = array() ) {		
	global $wp_query;

	$ret = is_main_query() && isset ( $wp_query->query_vars[ 'donate' ] ) && is_singular( 'campaign' );

	return $ret;
}

add_filter( 'charitable_is_campaign_donation_page', 'charitable_is_campaign_donation_page', 2, 2 );

/**
 * Checks whether the current request is for the campaign widget page.
 *
 * This is used when you call charitable_is_page( 'campaign_widget_page' ). 
 * In general, you should use charitable_is_page() instead since it will
 * take into account any filtering by plugins/themes.
 *
 * @global 	WP_Query 	$wp_query
 * @param 	string 		$page
 * @param 	array 		$args 
 * @return 	boolean
 * @since 	1.0.0
 */
function charitable_is_campaign_widget_page( $ret = false, $args = array()  ) {		
	global $wp_query;

	$ret = is_main_query() && isset ( $wp_query->query_vars[ 'widget' ] ) && is_singular( 'campaign' );

	return $ret;
}

add_filter( 'charitable_is_campaign_widget_page', 'charitable_is_campaign_widget_page', 2, 2 );