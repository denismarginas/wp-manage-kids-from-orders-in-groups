<?php
// -------------------------
// Define Functions - Start
// -------------------------
function cleanMetaValue($value)
{
    if (empty($value)) {
        return 'N/A';
    }
    $value = preg_replace('/<span class="woocommerce-Price-currencySymbol.*?<\/span>/si', '', $value);
    $value = preg_replace('/<span class=" woocommerce-Price-amount.*?<\/span>/si', '', $value);

    return $value;
}

function get_all_products()
{
    $products = wc_get_products(array('limit' => -1));
    if (empty($products))
        return '<option value="">No Products Found</option>';

    $product_options = '';
    foreach ($products as $product) {
        $product_options .= '<option value="' . esc_attr($product->get_id()) . '">' . esc_html($product->get_name()) . '
    </option>';
    }
    return $product_options;
}

function get_all_groups()
{
    $groups = get_posts(array('post_type' => 'camp-group', 'numberposts' => -1));
    if (empty($groups))
        return '<option value="">No Groups Found</option>';

    $group_options = '';
    foreach ($groups as $group) {
        $group_options .= '<option value="' . esc_attr($group->ID) . '">' . esc_html($group->post_title) . '</option>';
    }
    return $group_options;
}

function showChildUrl($child_name, $child_id)
{
    if (empty($child_name) || ($child_name == 'N/A') || empty($child_id)) {
        return $child_name;
    }

    $args = array(
        'post_type' => 'kids',
        'meta_key' => 'kid_id', // The ACF field key
        'meta_value' => $child_id, // The value to match against
        'posts_per_page' => 1, // Only fetch one post
        'post_status' => 'publish', // Ensure the post is published
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        // Get the first post found
        $post = $query->posts[0];

        // Generate the post URL
        $post_url = get_permalink($post->ID);

        // Return the anchor tag with the URL and child name
        return '<a class="link-dm" href="' . esc_url($post_url) . '">' . esc_html($child_name) . '</a>';
    }

    // If no post is found, return the child name as plain text
    return esc_html($child_name);
}
// -------------------------
// Define Functions - End
// -------------------------



// -------------------------
// Define Main Function - Start
// -------------------------
function all_orders_with_products_shortcode()
{
    if (!current_user_can('manage_woocommerce')) {
        return '<p>You do not have permission to view all orders.</p>';
    }

    $args = array(
        'limit' => -1, // Get all orders
    );
    $date_param = isset($_GET['date']) ? $_GET['date'] : date('d-m-Y', strtotime('-7 days'));
    $date = DateTime::createFromFormat('d-m-Y', $date_param);
    if ($date) {
        $args['date_query'] = array(
            'after' => $date->format('Y-m-d'),
        );
    }


    $orders = wc_get_orders($args);

    if (!$orders) {
        return '<p>No orders found.</p>';
    }

    ob_start();
    ?>

    <div class="woocommerce-orders-filters">
        <label>Product:
            <select id="product-filter">
                <option value="all">All Products</option>
                <?php echo get_all_products(); ?>
            </select>
        </label>
        <label>Group:
            <select id="group-filter">
                <option value="no-groups">No Groups</option>
                <option value="all-groups">All Groups</option>
                <?php echo get_all_groups(); ?>
            </select>
        </label>
        <label>Age: <input type="number" id="age-filter" placeholder="Enter Age"></label>
        <label>From Date: <input type="text" id="date-filter"
                value="<?php echo date('d-m-Y', strtotime('-7 days')); ?>"></label>
        <button class="btn-dm" onclick="applyFilters()">Filter</button>
    </div>

    <div id="products-from-orders" class="tab-content active">
        <table class="woocommerce-orders-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Order Details</th>
                    <th>Product</th>
                    <th>Child's Name</th>
                    <th>Child ID</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order):
                    foreach ($order->get_items() as $item_id => $item):
                        $product_name = $item->get_name();
                        $child_name = '';
                        $child_id = '';

                        // Check for custom fields
                        foreach ($item->get_formatted_meta_data('') as $meta) {
                            if (strpos($meta->key, 'ת.ז ילד') !== false) {
                                $child_id = cleanMetaValue($meta->value);
                            }
                            if (strpos($meta->key, 'שם הילד/ה') !== false) {
                                $child_name = cleanMetaValue($meta->value);
                            }
                        }
                        ?>
                        <tr>
                            <td><a class="link-dm" href="<?php echo esc_url($order->get_edit_order_url()); ?>">#<?php echo $order->get_id(); ?></a>
                            </td>
                            <td>
                                <p> Customer:
                                    <?php
                                    $customer_id = $order->get_customer_id();
                                    if ($customer_id) {
                                        $user = get_user_by('id', $customer_id);
                                        echo $user ? esc_html($user->display_name) : 'Guest';
                                    } else {
                                        echo 'Guest';
                                    }
                                    ?>
                                </p>

                                <button type="button" class="class="btn-dm" show-more-btn" onclick="toggleOrderDetails(this)">Show more</button>

                                <div class="order-details" style="display: none;">
                                    <p>Date: <?php echo $order->get_date_created()->date('Y-m-d'); ?></p>
                                    <p>Status: <?php echo wc_get_order_status_name($order->get_status()); ?></p>
                                    <p>Total: <?php echo $order->get_formatted_order_total(); ?></p>
                                    <p>
                                        <a class="link-dm" href="<?php echo esc_url($order->get_edit_order_url()); ?>">Edit</a>
                                    </p>
                                </div>
                            </td>
                            <td><?php echo esc_html($product_name); ?></td>
                            <td><?php echo $child_name ? showChildUrl($child_name, $child_id) : 'N/A'; ?></td>
                            <td><?php echo $child_id ? $child_id : 'N/A'; ?></td>
                        </tr>
                    <?php endforeach;
                endforeach; ?>
            </tbody>
        </table>
    </div> 
    <?php
    // -------------------------
// Define Main Function - End
// -------------------------

    // -------------------------
// Custom CSS - Start
// -------------------------

    ?>

    <style>
        #products-from-order .woocommerce-orders-tabs {
            margin-bottom: 15px;
        }

        #products-from-order .orders-tab {
            padding: 10px 15px;
            cursor: pointer;
            border: none;
            background: #ddd;
            margin-right: 5px;
        }

        .orders-tab.active {
            background: #0073aa;
            color: white;
        }

        #products-from-order .tab-content {
            display: none;
        }

        #products-from-order.tab-content.active {
            display: block;
            background-color: #fff !important;
        }

        .link-dm {
            color: #0073aa !important;
        }

        #products-from-order .woocommerce-orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        #products-from-order .woocommerce-orders-table th,
        #products-from-order .woocommerce-orders-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        #products-from-order .woocommerce-orders-table th {
            background-color: #f4f4f4;
        }

        .btn-dm {
            font-size: 16px;
            padding: 4px 12px;
            border: 1px solid #0073aa;
            line-height: 18px;
            border-radius: 6px;
            background-color: #fff;
            color: #0073aa;
        }
        .btn-dm :hover {
            color: #fff;
            background-color: #0073aa;
        }
        .woocommerce-orders-filters select, .woocommerce-orders-filters input {
            font-size: 16px;
            padding: 6px 12px;
            border: 1px solid #333;
            line-height: 18px;
            border-radius: 6px;
            background-color: #fff;
            color: #333;
        }
    </style>

    <?php

    // -------------------------
// Custom CSS - End
// -------------------------

    // -------------------------
// Custom JS - Start
// -------------------------
    ?>

    <script>
        function toggleOrderDetails(button) {
            var details = button.nextElementSibling;
            if (details.style.display === "none") {
                details.style.display = "block";
                button.textContent = "Show less";
            } else {
                details.style.display = "none";
                button.textContent = "Show more";
            }
        }
    </script>

    <?php
    // -------------------------
// Custom JS - End
// -------------------------

    return ob_get_clean();
}

// -------------------------
// Custom Shortcode - Start
// -------------------------

add_shortcode('all_orders_with_products', 'all_orders_with_products_shortcode');

// -------------------------
// Custom Shortcode - End
// -------------------------