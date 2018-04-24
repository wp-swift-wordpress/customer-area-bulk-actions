<?php
/*
Plugin Name: Customer Area Bulk Actions (Unofficial Add-on)
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

    echo '<pre>cuar_process_file_action( $post_id = '.$post_id.', $action = '.$action.' )</pre>';echo "<hr>";
    $post = array( 'ID' => $post_id, 'post_status' => 'publish' );


    if (function_exists('cuar_addon')) {
        $po_addon = cuar_addon('post-owner'); 
        $no_addon = cuar_addon('notifications');
    }  

    if ( isset($po_addon) && isset($no_addon) ) {

        $owners = $po_addon->get_post_owners($post_id);

        if (isset( $owners["grp"] )) {
            $recipient_ids = $owners["grp"];


            ?>
            <div class="notice notice-success is-dismissible">
                <p>Bulk action triggered on post <strong><?php echo $post_id; ?></strong> with action <code><?php echo $action; ?></code>.</p>
                <small>(This is still under developemnt while we are testing notfications.)</small>
                <hr>
                <p>Attempting to send to these recipients:</p>
                <?php echo '<pre>$recipient_ids: '; var_dump($recipient_ids); echo '</pre>'; ?>
            </div> 
            <?php
			$no_addon->mailer()->send_mass_notification(
			    $recipient_ids, 
			    'private-content-published', 
			    $post_id, 
			    array('email_format' => $no_addon->settings()->get_email_format())
			);

        }

    }  
}