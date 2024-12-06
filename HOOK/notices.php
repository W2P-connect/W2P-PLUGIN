<?php

add_action('admin_notices', 'w2p_guest_checkout_notification');


function w2p_guest_checkout_notification()
{
    global $pagenow;
    $guest_checkout_enabled = get_option('woocommerce_enable_guest_checkout') === 'yes';

    $is_w2p_settings = $pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'w2p-settings';

    if ($guest_checkout_enabled && $is_w2p_settings) {
?>
        <div class="notice notice-warning is-dismissible">
            <h2 style="font-weight:500; font-size:large">Guest Checkout Enabled on Your Online Store</h2>
            <p style="font-size:medium">
                You have enabled the "Guest Checkout" option on your online store. This allows customers to place orders without creating an account, which may result in anonymous orders being recorded in Pipedrive. Consider disabling guest checkout to ensure customer data is properly synced.
            </p>
        </div>
<?php
    }
}
