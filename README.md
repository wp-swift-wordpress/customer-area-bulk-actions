# WP Swift: Customer Area Bulk Actions

 * Plugin Name: WP Swift: Customer Area Bulk Actions
 * Plugin URI: https://github.com/wp-swift-wordpress/wp-swift-customer-area-bulk-actions
 * Description: Adds new bulk actions to Customer Area private files.
 * Version: 1
 * Author: Gary Swift
 * Author URI: https://github.com/wp-swift-wordpress-plugins
 * License: GPL2


As of WordPress 4.7 it is possible to add custom bulk actions to any post type. See this example here: 

[https://make.wordpress.org/core/2016/10/04/custom-bulk-actions/](https://make.wordpress.org/core/2016/10/04/custom-bulk-actions/)

This is a useful feature that I wanted to use with **[WP Customer Area - Notifications](http://wp-customerarea.com)** for bulk publishing files.

## Installation

Add the directory to your plugins folder and activate via the plugins admin page.

## Usage

This will add new custom bulk actions the **WP Customer Area** private files admin page.

![alt text][logo]

[logo]: image.png "New Bulk Actions"

The **Publish** bulk action works as expected.

### To-Do

The **Publish and Notify** bulk action is not yet implemented.

### Testing

There is also **Test Ajax** function that makes an ajax call to the server and outputs debug information to _debug.log_ Requires WordPress debugging to be turned on).

Note, this is still under development and there are bugs present.

## Bugs

The following function is used to to add the bulk actions:

```php
add_filter('cuar/core/admin/content-list-table/bulk-actions?post_type=cuar_private_file', 'cuar_add_file_actions');
function cuar_add_file_actions($actions) {
	$actions['cuar-publish-post'] = 'Publish';
	$actions['cuar-publish-post-notify'] = 'Publish and Notify';
  	return $actions;
}
```

The callback is handled by the following function:

```
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
```

This is the error log as a result of calling the `$no_addon->mailer()->send_mass_notification()` function:

```
[24-Apr-2018 20:01:57 UTC] PHP Notice:  Trying to get property of non-object in /app/public/wp-content/plugins/customer-area-notifications/src/php/helpers/notifications-placeholder-helper.class.php on line 215
[24-Apr-2018 20:01:57 UTC] PHP Notice:  Trying to get property of non-object in /app/public/wp-content/plugins/customer-area-notifications/src/php/helpers/notifications-placeholder-helper.class.php on line 216
[24-Apr-2018 20:01:57 UTC] PHP Notice:  Trying to get property of non-object in /app/public/wp-content/plugins/customer-area-notifications/src/php/helpers/notifications-placeholder-helper.class.php on line 215
[24-Apr-2018 20:01:57 UTC] PHP Notice:  Trying to get property of non-object in /app/public/wp-content/plugins/customer-area-notifications/src/php/helpers/notifications-placeholder-helper.class.php on line 216
[24-Apr-2018 20:01:57 UTC] PHP Notice:  Trying to get property of non-object in /app/public/wp-content/plugins/customer-area-notifications/src/php/helpers/notifications-placeholder-helper.class.php on line 215
[24-Apr-2018 20:01:57 UTC] PHP Notice:  Trying to get property of non-object in /app/public/wp-content/plugins/customer-area-notifications/src/php/helpers/notifications-placeholder-helper.class.php on line 216
[24-Apr-2018 20:01:57 UTC] PHP Notice:  Trying to get property of non-object in /app/public/wp-content/plugins/customer-area-notifications/src/php/helpers/notifications-mailer-helper.class.php on line 120
[24-Apr-2018 20:01:57 UTC] PHP Notice:  Trying to get property of non-object in /app/public/wp-content/plugins/customer-area-notifications/src/php/helpers/notifications-mailer-helper.class.php on line 121
```
This is the function from the notifications plugin which is causing the error.

It appears like `$placeholders` is not being instantiated correctly.


`src/php/helpers/notifications-placeholder-helper.class.php`

```php
    /**
     * Placeholders valid for the user receiving the email
     *
     * @param $user_id
     *
     * @return array
     */
    private function get_general_recipient_placeholders($user_id)
    {
        $placeholders = self::$RECIPIENT_PLACEHOLDERS; // line 210

        if ($user_id == null) return $placeholders;

        $user = get_userdata($user_id);
        $placeholders['to_name'] = $user->display_name; // line 215
        $placeholders['to_address'] = $user->user_email; // line 215

        return $placeholders;
    }
```
