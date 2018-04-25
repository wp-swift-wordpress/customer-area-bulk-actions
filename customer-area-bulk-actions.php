<?php
/*
Plugin Name: WP Customer Area - Bulk Actions (Unofficial Add-on)
Plugin URI: https://github.com/wp-swift-wordpress/customer-area-bulk-actions
Description: Adds new bulk actions to Customer Area private files.
Version: 1
Author: Gary Swift
Author URI: https://github.com/wp-swift-wordpress-plugins
License: GPL2
*/


add_filter('cuar/core/admin/content-list-table/bulk-actions?post_type=cuar_private_file', 'cuar_add_file_actions');
function cuar_add_file_actions($actions) {
	$actions['cuar-publish-post'] = 'Publish';
	$actions['cuar-publish-post-notify'] = 'Publish and Notify';
  	return $actions;
}

add_action('cuar/core/admin/content-list-table/do-bulk-action?post_type=cuar_private_file', 'cuar_process_file_action', 10, 3);
function cuar_process_file_action($post_id, $action, $list_object) {
    if ( $action !== 'cuar-publish-post' && $action !== 'cuar-publish-post-notify' ) return;

    $current_post = get_post( $post_id );
    $post_status = $current_post->post_status;   

    if( function_exists('cuar_addon') && $post_status !== 'publish' ) {
    
        $post = array( 'ID' => $post_id, 'post_status' => 'publish' );
        $post_id = wp_update_post( $post );
        $po_addon = cuar_addon('post-owner'); 
        $no_addon = cuar_addon('notifications');
        
        if ( !is_wp_error($post_id) ): 

            $msg = '<strong>' . $current_post->post_title . '</strong> has been published';

            if ( $action === 'cuar-publish-post-notify' ) {

                $recipient_ids = $po_addon->get_post_owner_user_ids($post_id);

                $no_addon->mailer()->send_mass_notification(
                    $recipient_ids, 
                    'private-content-published', 
                    $post_id, 
                    array('email_format' => $no_addon->settings()->get_email_format())
                );
                $msg .= ' and notifications have been sent.';
            }
            else {
                $msg .= '.';
            }
            $msg = '<div>' . $msg . '</div>';
            ?>
            <div class="notice notice-success">
                <?php echo $msg; ?>
            </div>
            <?php
        endif;
    }
}