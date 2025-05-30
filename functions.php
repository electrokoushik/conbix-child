<?php
/**
 * Conbix Child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Conbix Child
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_CONBIX_CHILD_VERSION', '1.0.0' );

/**
 * Enqueue styles
 */
function child_enqueue_styles() {
	wp_enqueue_style( 'conbix-child-theme-css', get_stylesheet_directory_uri() . '/style.css', array('conbix-theme-css'), CHILD_THEME_CONBIX_CHILD_VERSION, 'all' );

}
add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );
//Added By Me
if (!function_exists('delete_post_type')){
    function delete_post_type() {
        if (post_type_exists('portfolio')){
            unregister_post_type('portfolio');
        }
		if (post_type_exists('service')){
            unregister_post_type('service');
        }
    }
    add_action('init', 'delete_post_type', 100);
}

function conbix_woocommerce_sidebar(){
    register_sidebar( array(
        'name'          => __('WooCommerce Sidebar', 'conbix'),
        'id'            => 'woocommerce-sidebar',
        'before_widget' => '<div id="%1$s" class="%2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h2>',
		'after_title'   => '</h2>',
    ) );
}
add_action('widgets_init','conbix_woocommerce_sidebar');

add_action('pre_get_posts', 'exclude_subscription_category');
function exclude_subscription_category($query) {
    if (!is_admin() && $query->is_main_query()) {
        $exclude_category_slug = 'subscription';
        if (is_category($exclude_category_slug)) {
            $query->set('cat', '-' . $exclude_category_slug);
        }
    }
}

add_action('pre_get_posts', 'hide_products_from_shop_page');
function hide_products_from_shop_page($query) {
    if (!is_admin() && $query->is_main_query() && is_shop()) {
        $product_ids_to_hide = [5303, 5301, 5302, 5300, 5633, 5631];
        $query->set('post__not_in', $product_ids_to_hide);
    }
}

add_action('woocommerce_before_shop_loop_item_title', 'display_category_before_product_title', 10);
function display_category_before_product_title() {
    global $post;
    $terms = get_the_terms($post->ID, 'product_cat');
    if ($terms && !is_wp_error($terms)) {
        $category = array_shift($terms); 
        echo '<div class="product-category"> Category: ' . esc_html($category->name) . '</div>';
    }
}

add_filter('woocommerce_product_single_add_to_cart_text', 'woocommerce_add_to_cart_button_text_single'); 
function woocommerce_add_to_cart_button_text_single() {
    return __('Download', 'woocommerce'); 
}

add_filter('woocommerce_product_add_to_cart_text', 'woocommerce_add_to_cart_button_text_archives');  
function woocommerce_add_to_cart_button_text_archives() {
    return __('Download', 'woocommerce');
}

add_filter('woocommerce_related_products', 'exclude_from_related_products', 10, 2);
function exclude_from_related_products($related_products, $product_id){
    $product_ids_to_hide = [5303, 5301, 5302, 5300, 5633, 5631];
    return array_diff($related_products, $product_ids_to_hide);
}

add_action('pre_get_posts', 'exclude_products_from_new_in_store');
function exclude_products_from_new_in_store($query) {
    if (!is_admin() && $query->is_main_query() && is_shop()) {
        $product_ids_to_hide = [5303, 5301, 5302, 5300, 5633, 5631];
        $query->set('post__not_in', $product_ids_to_hide);
    }
}

add_action('woocommerce_product_options_general_product_data', 'add_min_max_quantity_fields');
function add_min_max_quantity_fields(){
    woocommerce_wp_text_input( array(
        'id'          => '_min_quantity',
        'label'       => __('Minimum Order Quantity', 'woocommerce'),
        'description' => __('Enter the minimum quantity that can be purchased for this product.', 'woocommerce'),
        'desc_tip'    => true,
        'type'        => 'number',
        'custom_attributes' => array(
            'min' => 1,
        ),
    ) );

    woocommerce_wp_text_input(array(
        'id'          => '_max_quantity',
        'label'       => __('Maximum Order Quantity', 'woocommerce'),
        'description' => __('Enter the maximum quantity that can be purchased for this product.', 'woocommerce'),
        'desc_tip'    => true,
        'type'        => 'number',
        'custom_attributes' => array(
            'min' => 1,
        ),
    ) );
}

add_action('woocommerce_process_product_meta', 'save_min_max_quantity_fields');
function save_min_max_quantity_fields($post_id){
    if (isset($_POST['_min_quantity'])){
        update_post_meta( $post_id, '_min_quantity', sanitize_text_field( $_POST['_min_quantity']));
    }
    if (isset($_POST['_max_quantity'])){
        update_post_meta($post_id, '_max_quantity', sanitize_text_field($_POST['_max_quantity']));
    }
}

add_action('woocommerce_before_calculate_totals', 'set_min_max_quantity_for_cart');
function set_min_max_quantity_for_cart($cart){
    if (is_admin() && !defined('DOING_AJAX')){
        return;
    }

    foreach ($cart->get_cart() as $cart_item_key => $cart_item){

        $product = $cart_item['data'];
        $min_quantity = get_post_meta($product->get_id(), '_min_quantity', true);
        $max_quantity = get_post_meta($product->get_id(), '_max_quantity', true);

        $min_quantity = !empty($min_quantity) ? $min_quantity : 1;
        $max_quantity = !empty($max_quantity) ? $max_quantity : 10;

        if ($cart_item['quantity'] < $min_quantity){
            $cart_item['quantity'] = $min_quantity;
            wc_add_notice(sprintf('The minimum quantity for %s is %d.', $product->get_name(), $min_quantity), 'error');
        }

        if ($cart_item['quantity'] > $max_quantity) {
            $cart_item['quantity'] = $max_quantity;
            wc_add_notice(sprintf('The maximum quantity for %s is %d.', $product->get_name(), $max_quantity ), 'error');
        }
    }
}

add_action('woocommerce_after_cart_item_quantity_update', 'restrict_cart_quantity_to_one', 10, 3);
function restrict_cart_quantity_to_one($cart_item_key, $quantity, $cart_item){
    if ($quantity > 1) {
        WC()->cart->set_quantity($cart_item_key, 1);
    }
}

add_action('template_redirect', 'redirect_logged_out_users_to_login');
function redirect_logged_out_users_to_login(){
    if (!is_user_logged_in() && is_cart()){
        wp_redirect(site_url('/login'));
        exit();
    }
}

add_action('template_redirect', 'custom_redirect_after_add_to_cart');
function custom_redirect_after_add_to_cart(){
    if (isset($_GET['add-to-cart']) && !empty($_GET['add-to-cart'])){
        if (isset($_GET['quantity']) && $_GET['quantity'] > 1){
            $_GET['quantity'] = 1;
        }
        WC()->cart->empty_cart();
        $product_id = intval($_GET['add-to-cart']);
        $quantity = intval($_GET['quantity']);

        WC()->cart->add_to_cart($product_id, $quantity);
        wp_redirect(wc_get_checkout_url());
        exit;
    }
}

add_action('woocommerce_checkout_process', 'check_if_user_is_logged_in_before_checkout');
function check_if_user_is_logged_in_before_checkout(){
    if (!is_user_logged_in()){
        wp_redirect(wp_login_url(wc_get_checkout_url()));
        exit;
    }
}

add_action('woocommerce_checkout_process', 'notify_user_to_log_in_before_checkout');
function notify_user_to_log_in_before_checkout(){
    if(!is_user_logged_in()){
        wc_print_notice('You must be logged in to complete the checkout process. Please log in or register.', 'error');
    }
}

add_action( 'woocommerce_checkout_before_customer_details', 'disable_place_order_button_for_logged_out_users');
function disable_place_order_button_for_logged_out_users(){
    if (!is_user_logged_in()){
        ?>
        <script type="text/javascript">
            jQuery(function($){
                $('button#place_order').prop('disabled', true);
                $('button#place_order').after('<p class="woocommerce-error">You must be logged in to complete the checkout process.</p>');
            });
        </script>
        <?php
    }
}

add_action( 'woocommerce_order_status_changed', 'auto_complete_woocommerce_order', 10, 1 );
function auto_complete_woocommerce_order( $order_id ) {
    $order = new WC_Order( $order_id );
    if ( $order->has_status( 'processing' ) && ! $order->has_shipping_address() ) {
        $order->update_status( 'completed' );
    }
}

//Modify Ultimate Member
add_filter('um_account_page_default_tabs_hook', 'um_account_custom_tabs', 100);
function um_account_custom_tabs($tabs){
    if (class_exists('WooCommerce')) {
        $tabs[150]['my_orders'] = array(
            'icon'   => 'um-faicon-shopping-cart',
            'title'  => __('My Orders', 'ultimate-member'),
            'custom' => true,
        );
    }
    return $tabs;
}

add_filter('um_account_content_hook_my_orders', 'um_account_content_my_orders', 20);
function um_account_content_my_orders($output = '') {
    if (!function_exists('wc_get_orders')) {
        return '<div class="um-notice"><p>' . esc_html__('WooCommerce is not active.', 'ultimate-member') . '</p></div>';
    }

    $user_id = get_current_user_id();
    $paged = (get_query_var('um_page')) ? absint(get_query_var('um_page')) : 1;
    $per_page = 10;

    $customer_orders = wc_get_orders(array(
        'customer_id' => $user_id,
        'limit'       => $per_page,
        'paged'       => $paged,
        'orderby'     => 'date',
        'order'       => 'DESC',
        'paginate'    => true,
        'return'      => 'objects',
        'status'      => array_keys(wc_get_order_statuses()), // Include all order statuses
    ));

	$user = wp_get_current_user();
	$user_roles = [
		'um_pro-user' => 'Pro Plan',
		'um_free-user' => 'Free Plan',
		'um_business-user' => 'Business Plan',
	];

	$user_role = (array) $user->roles;
	$current_user_plan = '';

	foreach ($user_role as $role) {
		if (array_key_exists($role, $user_roles)) {
			$current_user_plan = $user_roles[$role];
			break;
		}
	}
	$existing_expiration = date_i18n('Y-m-d',get_user_meta($user_id, 'subscription_expiration', true));
	
    ob_start();
	echo '<div class="um-woocommerce-orders">';
	echo '<strong>Current Plan:</strong> '. $current_user_plan .'</br>';
	echo '<strong>Valid Till:</strong> '. $existing_expiration;
    echo '</div>';
    if (empty($customer_orders->orders)) {
        echo '<div class="um-message"><p>' . esc_html__('You have no orders yet.', 'ultimate-member') . '</p></div>';
    } else {
        echo '<div class="um-woocommerce-orders mt-3">';
		echo '<table class="um-woocommerce-orders-table shop_table shop_table_responsive">';
		echo '<thead>
				<tr>
					<th class="order-number">' . esc_html__('Order', 'ultimate-member') . '</th>
					<th class="order-date">' . esc_html__('Date', 'ultimate-member') . '</th>
					<th class="order-products">' . esc_html__('Products', 'ultimate-member') . '</th> <!-- New Column for Products --> 
					<th class="order-status">' . esc_html__('Status', 'ultimate-member') . '</th>
					<th class="order-total">' . esc_html__('Total', 'ultimate-member') . '</th>
					<th class="order-actions">' . esc_html__('Actions', 'ultimate-member') . '</th>
				</tr>
			  </thead><tbody>';

		foreach ($customer_orders->orders as $order) {
			$order_id = $order->get_id();
			$order_number = $order->get_order_number();
			$order_date = $order->get_date_created()->date('M j, Y');
			$order_status = wc_get_order_status_name($order->get_status());
			$order_total = $order->get_formatted_order_total();
			$order_view_url = $order->get_view_order_url();
			$actions = wc_get_account_orders_actions($order);

			echo '<tr class="order">';
			echo '<td class="order-number" data-title="' . esc_attr__('Order', 'ultimate-member') . '">';
			echo '<a href="' . esc_url($order_view_url) . '">#' . esc_html($order_number) . '</a>';
			echo '</td>';
			echo '<td class="order-date" data-title="' . esc_attr__('Date', 'ultimate-member') . '">' . esc_html($order_date) . '</td>';

			echo '<td class="order-products" data-title="' . esc_attr__('Products', 'ultimate-member') . '">';
			$order_items = $order->get_items();
			$product_list = '';

			foreach ($order_items as $item_id => $item) {
				$product = $item->get_product();
				if ($product) {
					$product_name = $product->get_name();
					$quantity = $item->get_quantity();
					$product_list .= esc_html($product_name) . '<br>';
				}
			}

			echo $product_list;
			echo '</td>';
			echo '<td class="order-status" data-title="' . esc_attr__('Status', 'ultimate-member') . '">' . esc_html($order_status) . '</td>';
			echo '<td class="order-total" data-title="' . esc_attr__('Total', 'ultimate-member') . '">' . wp_kses_post($order_total) . '</td>';
			echo '<td class="order-actions" data-title="' . esc_attr__('Actions', 'ultimate-member') . '">';
			if (!empty($actions)) {
				foreach ($actions as $key => $action) {
					echo '<a href="' . esc_url($action['url']) . '" class="um-button um-alt-button ' . esc_attr($key) . '">' . esc_html($action['name']) . '</a> ';
				}
			}
			echo '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';

        $total_pages = $customer_orders->max_num_pages;
        if ($total_pages > 1) {
            $base = um_get_core_page('account');
            echo '<div class="um-pagination woocommerce-pagination">';
            echo paginate_links(array(
                'base'      => add_query_arg('um_page', '%#%', $base),
                'format'    => '',
                'current'   => max(1, $paged),
                'total'     => $total_pages,
                'prev_text' => '&larr; ' . esc_html__('Previous', 'ultimate-member'),
                'next_text' => esc_html__('Next', 'ultimate-member') . ' &rarr;',
                'type'      => 'list',
            ));
            echo '</div>';
        }
        
        echo '</div>';
    }

    return ob_get_clean();
}

add_action('wp_head', 'um_woocommerce_orders_styles');
function um_woocommerce_orders_styles() {
    if (!function_exists('wc_get_orders')) return;
    
    echo '<style>
    .um-woocommerce-orders {
        margin: 20px 0;
        overflow-x: auto;
    }
    .um-woocommerce-orders-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0 0 20px;
        font-size: 0.9em;
    }
    .um-woocommerce-orders-table th {
        background-color: #f8f8f8;
        font-weight: 600;
        padding: 12px 15px;
        text-align: left;
        border: 1px solid #e0e0e0;
    }
    .um-woocommerce-orders-table td {
        padding: 12px 15px;
        border: 1px solid #e0e0e0;
        vertical-align: middle;
    }
    .um-woocommerce-orders-table .um-button {
        padding: 6px 12px;
        font-size: 0.85em;
        margin-right: 5px;
        display: inline-block;
    }
    .um-pagination.woocommerce-pagination {
        text-align: center;
        margin: 20px 0;
    }
    .um-pagination.woocommerce-pagination .page-numbers {
        display: inline-block;
        margin: 0 2px;
        padding: 8px 14px;
        background: #f5f5f5;
        border: 1px solid #ddd;
        text-decoration: none;
        color: #444;
        border-radius: 3px;
    }
    .um-pagination.woocommerce-pagination .current,
    .um-pagination.woocommerce-pagination a:hover {
        background: #2b6cb0;
        color: #fff;
        border-color: #2b6cb0;
    }
	.um-account-tab-my_orders .um-col-alt {
        display: none !important
	}
	.um-account-side i {
        color: #196164;
	}
    @media (max-width: 768px) {
        .um-woocommerce-orders-table {
            display: block;
        }
        .um-woocommerce-orders-table td:before {
            content: attr(data-title);
            font-weight: 600;
            display: inline-block;
            margin-right: 10px;
        }
    }
    </style>';
}


?>