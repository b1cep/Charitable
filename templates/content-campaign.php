<?php 
/**
 * Displays the content of the campaign.
 *
 * @author 	Studio 164a
 * @since 	1.0.0
 */

$campaign = charitable_get_current_campaign();

/**
 * @hook charitable_campaign_content_before
 */
do_action( 'charitable_campaign_content_before', $campaign ); 

/**
 * Display the summary of the campaign. 
 */
charitable_template_part( 'campaign/summary' );

echo get_the_content();

/**
 * @hook charitable_campaign_content_after
 */
do_action( 'charitable_campaign_content_after', $campaign );