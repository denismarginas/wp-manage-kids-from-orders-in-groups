<?php

// -------------------------
// Define Functions - Start
// -------------------------

function cleanMetaValue($value) {
    if (empty($value)) {
        return 'N/A';
    }
	$value = preg_replace('/<span class="woocommerce-Price-currencySymbol.*?<\/span>/si', '', $value);
	$value = preg_replace('/<span class="woocommerce-Price-amount.*?<\/span>/si', '', $value);

	return $value;
}

function showChildUrl($child_name, $child_id) {
    if(empty($child_name) || ($child_name == 'N/A') || empty($child_id)) {
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
function get_all_groups_checkboxes() {
    // Fetch all 'camp-group' posts
    $groups = get_posts(array(
        'post_type' => 'camp-group',
        'numberposts' => -1
    ));

    // If no groups found, return a message
    if (empty($groups)) {
        return '<p>No Groups Found</p>';
    }

    // Initialize variable to store checkbox HTML
    $group_options = '';

    // Loop through each group and generate checkbox
    foreach ($groups as $group) {
        $group_options .= '<p><input type="checkbox" value="' . esc_attr($group->ID) . '" id="group-' . esc_attr($group->ID) . '"> ' . esc_html($group->post_title) . '</p>';
    }

    // Return the checkbox options
    return $group_options;
}


function getChildPostId($kid_id) {
    // Query the 'kids' custom post type to find the post where 'kid_id' field matches $kid_id
    $args = array(
        'post_type' => 'kids', // Custom post type 'kids'
        'posts_per_page' => 1, // We only need one result
        'meta_query' => array(
            array(
                'key'   => 'kid_id', // The ACF field to search
                'value' => $kid_id,  // The value to match with
                'compare' => '=',    // Comparison operator
            ),
        ),
    );

    // Run the query
    $query = new WP_Query($args);

    // Check if a post was found
    if ($query->have_posts()) {
        // Get the post ID of the first post that matches the query
        $post_id = $query->posts[0]->ID;
        wp_reset_postdata(); // Reset the query after use
        return $post_id;
    }

    // Return null if no matching post is found
    return null;
}

// Function to display custom fields for a post
function customFieldsOfPost($postId) {
    // Retrieve all custom fields for the post
    $all_fields = get_post_meta($postId);

    // Check if any custom fields exist
    if ($all_fields) {
        foreach ($all_fields as $field_name => $field_value) {
            // Retrieve the ACF field object to get the label
            $field_object = get_field_object($field_name, $postId);
            
            // If the field exists in ACF and has a label
            if ($field_object) {
                $label = $field_object['label']; // Get the field label
            } else {
                // If no ACF field exists for this key, use the field name as fallback
                $label = ucwords(str_replace('_', ' ', $field_name));
            }

            // Each $field_value is an array, so we get the first element (if it's not an empty array)
            $value = isset($field_value[0]) ? $field_value[0] : '';

            // If the field has a value, display it
            if (!empty($value)) {
                echo '<p><span class="field-label">' . esc_html($label) . ':</span> <span class="field-value">' . esc_html($value) . '</span></p>';
            } else {
                echo '<p>N/A: "' . esc_html($label) . '".</p>';
            }
        }
    } else {
        echo '<p>No custom fields found.</p>';
    }
}

function getCustomFieldsFromProductOrder($order_item, $excluded_keys = []) {
    $meta_data = $order_item->get_meta_data(); 
    $custom_fields = [];

    if (!empty($meta_data)) {
        foreach ($meta_data as $meta) {
            $key = esc_html($meta->key);
            $value = $meta->value;

            if (!in_array($key, $excluded_keys, true)) {
                $custom_fields[$key] = $value;
            }
        }
    }
    return $custom_fields;
}


function renderCustomFieldsFromProductOrder($custom_fields) {
    if (!empty($custom_fields)) {
        foreach ($custom_fields as $key => $value) {
            echo '<p><span class="field-label">' . $key . ':</span> <span class="field-value">';
            if (is_array($value)) {
                echo implode(', ', $value);
            } else {
                echo cleanMetaValue($value);
            }
            echo '</span></p>';
        }
    } else {
        echo '<p>No custom fields found for this product.</p>';
    }
}

function enqueue_my_custom_script() {
    wp_enqueue_script('my-custom-script', get_template_directory_uri() . '/js/my-custom-script.js', array('jquery'), null, true);

    // Pass the AJAX URL to the JavaScript file
    wp_localize_script('my-custom-script', 'ajaxurl', admin_url('admin-ajax.php'));
}
add_action('wp_enqueue_scripts', 'enqueue_my_custom_script');


add_action('wp_ajax_update_groups_for_kid', 'update_groups_for_kid_callback');

function update_groups_for_kid_callback() {
    // Make sure the required data is sent and sanitize it
    if (isset($_POST['groups']) && isset($_POST['kid_id'])) {
        // Sanitize incoming data
        $kid_id = $_POST['kid_id'];
        $kid_name = $_POST['kid_name'];
        $kid_details = $_POST['kid_details'];
        $order_id = $_POST['order_id'];
        $order_date = $_POST['order_date'];
        $order_details = $_POST['order_details'];
        $product_details = $_POST['product_details'];
        $product_field = $_POST['product_field'];

        // Get selected groups
        $groups = is_array($_POST['groups']) ? $_POST['groups'] : [];

        // If "No group" is selected, remove kid from all groups
        if (in_array('no-group', $groups)) {
            $groups = []; // Force an empty group list (removing kid from all groups)
        }

        // Remove the kid from all groups if 'no-group' is selected
        if (empty($groups)) {
            // Remove the kid from all groups (if 'no-group' is selected)
            $all_groups = get_posts([
                'post_type'      => 'camp-group',
                'posts_per_page' => -1
            ]);

            foreach ($all_groups as $group_post) {
                $kids_list = (array) get_field('kids_list', $group_post->ID);
                $updated_list = [];

                foreach ($kids_list as $kid) {
                    // Only remove the kid if the kid_id, order_id, and product_field match
                    if ($kid['kid_id'] == $kid_id && $kid['order_id'] == $order_id && $kid['product_field'] == $product_field) {
                        // Do not add this kid back to the group if it's "no-group"
                    } else {
                        // Keep other kids (even if they have the same kid_id and order_id but different product_field)
                        $updated_list[] = $kid;
                    }
                }

                // Update the group if a kid was removed
                if (count($updated_list) !== count($kids_list)) {
                    update_field('kids_list', $updated_list, $group_post->ID);
                }
            }
        }

        // Now process the groups that are actually selected (excluding 'no-group')
        foreach ($groups as $group_id) {
            $group_post = get_post($group_id);
            if ($group_post && $group_post->post_type === 'camp-group') {
                $kids_list = (array) get_field('kids_list', $group_post->ID);

                // Check if the kid is already in the list
                $kid_exists = false;
                foreach ($kids_list as $kid) {
                    if (($kid['kid_id'] == $kid_id) && ($kid['product_field'] == $product_field) && ($kid['order_id'] == $order_id)) {
                        $kid_exists = true;
                        break;
                    }
                }

                // Add the kid if not present
                if (!$kid_exists) {
                    $kids_list[] = [
                        'kid_name'    => $kid_name,
                        'kid_id'      => $kid_id,
                        'kid_details' => $kid_details,
                        'order_id'    => $order_id,
                        'order_date'  => $order_date,
                        'order_details' => $order_details,
                        'product_details' => $product_details,
                        'product_field' => $product_field
                    ];
                    update_field('kids_list', $kids_list, $group_post->ID);
                }
            }
        }

        // Return a success response
        wp_send_json_success('Groups updated successfully!');
    } else {
        wp_send_json_error('Required data missing.');
    }

    wp_die();
}



function checkGroup($order_id, $kid_id, $product_field) {
    // Clean the $order_id and $kid_id by removing leading/trailing spaces
    $order_id = trim($order_id);  // Remove leading/trailing spaces
    $kid_id = trim($kid_id);      // Remove leading/trailing spaces

    // Check if order_id and kid_id are set
    if (empty($order_id) || empty($kid_id)) {
        return 'No group'; // Return if either ID is missing
    }

    // Assuming 'camp-group' is a custom post type and 'kids_list' is the repeater field
    $groups = [];
    
    // Query the camp-group posts
    $args = [
        'post_type' => 'camp-group',
        'posts_per_page' => -1 // Get all posts
    ];
    
    $camp_groups = get_posts($args); // Get all camp-group posts
    
    // Loop through each camp-group post
    if ($camp_groups) {
        foreach ($camp_groups as $group_post) {
            // Get the kids list from the current group post
            $kids_list = get_field('kids_list', $group_post->ID); // Adjust field name as necessary

            // If kids_list exists, loop through each kid
            if ($kids_list) {
                foreach ($kids_list as $kid) {
                    // Clean the kid_id and order_id from the repeater field in each group
                    $kid_order_id = trim($kid['order_id']);
                    $kid_kid_id = trim($kid['kid_id']);
                    $kid_product_field = trim($kid['product_field']);
                    
                    // Check if this group contains the kid (based on order_id and kid_id)
                    if ($kid_order_id == $order_id && $kid_kid_id == $kid_id && $kid_product_field == $product_field ) {
                        // The group is represented by the 'camp-group' post, so get the post title
                        $groups[] = get_the_title($group_post->ID); // Get the camp-group post title
                    }
                }
            }
        }
    }

    // If no groups found, return "No group"
    if (empty($groups)) {
        return 'No group';
    }

    // Return a string of group names, formatted as "Group: group_name_1, group_name_2"
    return '' . implode(', ', $groups);
}

function camp_group_kid_list() {
    if (!is_singular('camp-group')) {
        return ''; // Ensure it only runs on camp-group posts
        echo "<p>Incorect Type post</p>";
    }

    global $post;
    $kid_list = get_field('kids_list', $post->ID);

    if (empty($kid_list)) {
        return '<p>No kids found in this camp group.</p>';
    }
    $first_row = $kid_list[0];
    $headers = array_keys($first_row);

    // Start building the table
    ob_start();
    ?>
    <table id="products-from-orders" border="1">
        <thead>
            <tr>
                <?php foreach ($headers as $header): ?>
                    <th><?php echo ucwords(str_replace('_', ' ', $header)); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($kid_list as $kid): ?>
                <tr>
                    <?php foreach ($headers as $header): ?>
                        <td><?php echo $kid[$header]; ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    return ob_get_clean();
}

function custom_code_css_js_manage_kids_in_groups() {
    ob_start();

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
    #products-from-orders {
        min-width: 800px;
    }
    #products-from-orders p {
        margin-bottom: 4px;
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
        font-size: 14px;
        font-weight: 500;
        padding: 4px 12px;
        border: 1px solid #0073aa !important;
        line-height: 18px;
        border-radius: 6px;
        background-color: #fff !important;
        color: #0073aa !important;
    }

    .btn-dm:hover,
    .btn-dm:focus {
        color: #fff !important;
        background-color: #0073aa !important;
    }

    .woocommerce-orders-filters select,
    .woocommerce-orders-filters input {
        font-size: 16px;
        padding: 6px 12px;
        border: 1px solid #333;
        line-height: 18px;
        border-radius: 6px;
        background-color: #fff;
        color: #333;
    }
    #products-from-orders tr {
        position: relative;
    }
    #products-from-orders .kid-details {
        position: absolute;
        background: #fff;
        border: 1px solid #ddd;
        padding: 25px 25px;
        width: 100%;
        left: 0;
        right: 0;
        display: flex;
        flex-direction: row;
        gap: 26px;
        flex-wrap: wrap;
        z-index: 80;
    }
    #products-from-orders .acf-fields .field-label {
        font-weight: 500;
    }

    #products-from-orders tr[order-data-tabel-id="6452"] {
        background-color: #e4eef7;
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
        var details = button.closest("td").querySelector(".show-more-details");
        if (details) {
            if (details.style.display === "none") {
                details.style.display = "block";
                button.textContent = "Show less";
            } else {
                details.style.display = "none";
                button.textContent = "Show more";
            }
        }
    }

    function updatePostGroups(button) {
        // Extract the kid data from the script tag
        const kidId = button.getAttribute('kid_id');
        const orderItemCustomField = button.getAttribute('order_item_custom_field');
        const orderId = button.getAttribute('order_id'); // Fetch the order_id
        const kidDataScript = document.getElementById(`kid-data-${kidId}-${orderId}-${orderItemCustomField}`);


        if (!kidDataScript) {
            console.error('Kid data script not found!');
            return;
        }

        // Parse the JSON data
        let kidData;
        try {
            kidData = JSON.parse(kidDataScript.textContent);
        } catch (e) {
            console.error('Failed to parse kid data:', e);
            return;
        }

        // Ensure kid data exists before proceeding
        if (!kidData || !kidData.kid_id || !kidData.kid_name) {
            console.error('Incomplete kid data!');
            return;
        }

        // Debugging log to check what is fetched
        console.log("Kid Data:", kidData);

        // Find the closest parent `.show-more-details` container of the clicked button
        const showMoreDetailsContainer = button.closest('.show-more-details');
        if (!showMoreDetailsContainer) {
            console.error('No .show-more-details container found!');
            return;
        }

        // Find the `.group-checkboxes` within the `.show-more-details` container
        const groupCheckboxesContainer = showMoreDetailsContainer.querySelector('.group-checkboxes');
        if (!groupCheckboxesContainer) {
            console.error('No .group-checkboxes container found!');
            return;
        }

        // Get selected groups
        const selectedGroups = [];

        // Check if "No group" is selected (using class selector)
        const noGroupCheckbox = groupCheckboxesContainer.querySelector(".no-group-checkbox:checked");
        if (noGroupCheckbox) {
            selectedGroups.push("no-group"); // Ensure "No group" is the only value
        } else {
            // Get all selected checkboxes except "No group"
            groupCheckboxesContainer.querySelectorAll('input[type="checkbox"]:checked').forEach((checkbox) => {
                selectedGroups.push(checkbox.value);
            });
        }

        // Prepare the data for the AJAX request
        const data = {
            action: "update_groups_for_kid",
            groups: selectedGroups,
            kid_id: kidData.kid_id,
            kid_name: kidData.kid_name,
            kid_details: kidData.kid_details,
            order_id: kidData.order_id,
            order_date: kidData.order_date,
            order_details: kidData.order_details,
            product_details: kidData.product_details,
            product_field: kidData.product_field 
        };

        console.log("Data to send:", data);

        // Make an AJAX request to update the posts
        jQuery.post(ajaxurl, data, function(response) {
            if (response.success) {
                if (selectedGroups.includes("no-group")) {
                    alert("Kid removed from all groups.");
                } else {
                    alert("Groups updated successfully!");
                }
            } else {
                alert("Error updating groups.");
            }
        });
    }


</script>

<?php

// -------------------------
// Custom JS - End
// -------------------------

    return ob_get_clean();
}

// -------------------------
// Define Functions - End
// -------------------------

// -------------------------
// Define Main Function - Start
// -------------------------

function manage_kids_in_groups()
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

    $excluded_keys = json_decode($_GET['exclude_product_fields'] ?? '[]', true);
    if (!is_array($excluded_keys) || empty($excluded_key)) {
        $excluded_keys = [
            'product_extras', 
            'kid_name', 
            '_ת.ז ילד', 
            'kid_id', 
            '_שם הילד/ה',
            '_ת.ז ילד/ה',
            '_גיל הילד/ה',
            '_שם ההורה המלווה',
            '_מספר טלפון',
            '_כתובת אימייל (לשליחת חשבונית) )'
        ]; // Ensure it's always an array
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
        <label>Exclude Product Fields: <input type="text" name="exclude_product_fields" id="exclude_product_fields"
                value="<?php print_r(array_values($excluded_keys)); ?>">
        </label>
        <button class="btn-dm" onclick="applyFilters()">Filter</button>
    </div>

    <div id="products-from-orders" class="tab-content active">
        <table class="woocommerce-orders-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Order Details</th>
                    <th>Product</th>
                    <th>Product Field</th>
                    <th>Child's Name</th>
                    <th>Child Details</th>
                    <th>Group</th>
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
                <?php 
                $order_id = $order->get_id() ?? null; 
                $order_item_custom_fields = getCustomFieldsFromProductOrder($item );

                foreach ($order_item_custom_fields as $order_item_custom_field => $key_item): 

                    if (in_array($order_item_custom_field, $excluded_keys, true)) {
                        continue; // Skip this field if it’s in the excluded list
                    }
                    ?>
                    <tr order-data-tabel-id="<?php echo $order_id; ?>">
                        <td>
                            <?php 
                            $order_id = $order->get_id() ?? null;
                            $order_link = '<a class="link-dm" href="' . $order_url . '">#' . esc_html($order_id) . '</a>';
                            echo $order_link;
                            ?>
                        </td>
                        <td>
                            <?php $order_date = $order->get_date_created()->date('Y-m-d') ?? null; ?>
                            <p>תַאֲרִיך: <?php echo $order_date; ?></p>
                            <p>
                                <button type="button" class="btn-dm show-more-btn" onclick="toggleOrderDetails(this)">Show more</button>
                                <div class="show-more-details" style="display: none;">

                                    <?php
                                    $order_details = '
                                    <p> לָקוּחַ: ';

                                    $customer_id = $order->get_customer_id();
                                    if ($customer_id) {
                                        $user = get_user_by('id', $customer_id);
                                        $order_details .= $user ? esc_html($user->display_name) : 'Guest';
                                    } else {
                                        $order_details .= 'Guest';
                                    }

                                    $order_details .= '</p>
                                    <p>תַאֲרִיך: ' . $order->get_date_created()->date('Y-m-d') . '</p>
                                    <p>סטָטוּס: ' . wc_get_order_status_name($order->get_status()) . '</p>
                                    <p>סַך הַכֹּל: ' . $order->get_formatted_order_total() . '</p>
                                    <p>
                                        <a class="link-dm" href="' . esc_url($order->get_edit_order_url()) . '">Edit</a>
                                    </p>';

                                    // Output the stored HTML variable
                                    echo $order_details;
                                    ?>
                                </div>
                            </p>
                        </td>
                        <td>
                            <?php
                            $product_details = '
                            <a href="' . esc_url(get_permalink($item->get_product_id())) . '" class="link-dm">
                                ' . esc_html($product_name) . '
                            </a>
                            <p>
                                <button type="button" class="btn-dm show-more-btn" onclick="toggleOrderDetails(this)">Show more</button>
                                <div class="show-more-details" style="display: none;">';
                                ob_start();
                                renderCustomFieldsFromProductOrder($order_item_custom_fields);
                                $product_details .= ob_get_clean();

                            $product_details .= '
                                </div>
                            </p>';

                            // Output the stored HTML variable
                            echo $product_details;
                            ?>
                        </td>
                        <td>
                            <?php 
                            echo $order_item_custom_field; 
                            ?>
                        </td>         
                        <td>
                            <?php 
                            $child_name = isset($child_name) ? cleanMetaValue($child_name) : null;
                            $child_id = isset($child_id) ? cleanMetaValue($child_id) : null;

                            $child_url = ($child_name && $child_id) ? showChildUrl($child_name, $child_id) : null;

                            echo $child_url ?? 'N/A';
                            ?>
                        </td>
                        <td>
                            <p>
                            ID: <?php echo $child_id ? $child_id : 'N/A'; ?>
                            </p>
                            <?php
                            $postChildId = getChildPostId(cleanMetaValue($child_id));

                            // Start building the HTML content for child details
                            $child_details = '
                            <p>
                                <button type="button" class="btn-dm show-more-btn" onclick="toggleOrderDetails(this)">Show more</button>

                                <div class="show-more-details" style="display: none;"><div class="kid-details">
                            ';

                            // Add the child ID
                            $child_details .= '<p>ת.ז. הילד: ' . ($postChildId ? esc_html($postChildId) : 'N/A') . '</p>';

                            if ($postChildId) {
                                $author_id = get_post_field('post_author', $postChildId);
                                $author_name = $author_id ? esc_html(get_the_author_meta('display_name', $author_id)) : 'Unknown';
                                $child_details .= '<p>מְחַבֵּר: ' . $author_name . '</p>';

                                // Capture and add custom fields
                                ob_start();
                                customFieldsOfPost($postChildId);
                                $child_details .= ob_get_clean();
                            }

                            // Close the show-more-details and the p tag
                            $child_details .= $child_url;
                            $child_details .= '
                                    </div>
                                </div>
                            </p>
                            ';

                            // Now echo the child details
                            echo $child_details;

                            ?>

                        </td>
                        <td class="group-td">
                            <p>
                                <?php 
                                // Call the checkGroup function with order_id and kid_id
                                echo checkGroup($order_id, $child_id, $order_item_custom_field);
                                ?>
                            </p>

                            <p>
                                <button type="button" class="btn-dm show-more-btn" onclick="toggleOrderDetails(this)">Show more</button>
                                <div class="show-more-details" style="display: none;">
                                    <div class="group-checkboxes">
                                        <p><input type="checkbox" value="no-group" class="no-group"> No group</p>
                                        <?php echo get_all_groups_checkboxes(); ?> <!-- Dynamically load more checkboxes here -->
                                    </div>
                                    <button type="button" class="btn-dm" name="manage-groups" onclick="updatePostGroups(this)" 
                                        kid_id="<?php echo esc_attr($child_id); ?>"
                                        kid_name="<?php echo esc_attr($child_name); ?>"
                                        order_id="<?php echo esc_attr($order_id); ?>"
                                        order_date="<?php echo esc_attr($order_date); ?>"
                                        order_item_custom_field="<?php echo esc_attr($order_item_custom_field); ?>"> 
                                        Apply
                                    </button>

                                    <!-- JSON Data Storage -->
                                    <script type="application/json" id="kid-data-<?php echo esc_attr($child_id . '-' . $order_id . '-' . $order_item_custom_field); ?>">
                                        <?php echo json_encode([
                                            'kid_id' => $child_id,
                                            'kid_name' => $child_name,
                                            'kid_details' => $child_details,
                                            'order_id' => $order_id,
                                            'order_date' => $order_date,
                                            'order_details' => $order_details,
                                            'product_details' => $product_details,
                                            'product_field' => $order_item_custom_field
                                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>
                                    </script>

                                </div>
                            </p>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php endforeach;
                endforeach; ?>
            </tbody>
        </table>
    </div>
<?php
    echo custom_code_css_js_manage_kids_in_groups();

    return ob_get_clean();
}
// -------------------------
// Define Main Function - End
// -------------------------

// -------------------------
// Custom Shortcode - Start
// -------------------------

add_shortcode('manage_kids_in_groups', 'manage_kids_in_groups');
add_shortcode('custom_code_css_js_manage_kids_in_groups', 'custom_code_css_js_manage_kids_in_groups');
add_shortcode('camp_group_kid_list', 'camp_group_kid_list');


// -------------------------
// Custom Shortcode - End
// -------------------------