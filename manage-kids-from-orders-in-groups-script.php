<?php

// -------------------------
// Define Functions - Start
// -------------------------


// Add Prefered Date in Site Settings /wp-admin/options-general.php
add_action('admin_init', function () {
    register_setting('general', 'preferred_date', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ]);

    add_settings_field(
        'preferred_date',
        'Preferred Date',
        function () {
            $value = get_option('preferred_date');
            echo '<input type="date" id="preferred_date" name="preferred_date" value="' . esc_attr($value) . '" class="regular-text">';
        },
        'general'
    );
});


function cleanMetaValue($value)
{
    if (empty($value)) {
        return 'N/A';
    }
    $value = preg_replace('/<span class="woocommerce-Price-currencySymbol.*?<\/span>/si', '', $value);
    $value = preg_replace('/<span class="woocommerce-Price-amount.*?<\/span>/si', '', $value);
    $value = preg_replace('/<span class="(?:woocommerce-Price-(?:currencySymbol|amount)|dashicons dashicons-yes)".*?<\/span>/si', '', $value);


    return $value;
}

function showChildUrl($child_name, $child_id)
{
    if (empty($child_name) || ($child_name == 'N/A') || empty($child_id)) {
        return $child_name;
    }

    $args = array(
        'post_type' => 'kids',
        'meta_key' => 'kid_id',
        'meta_value' => $child_id,
        'posts_per_page' => 1,
        'post_status' => 'publish',
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $post = $query->posts[0];
        $post_url = get_permalink($post->ID);
        return '<a class="link-dm" href="' . esc_url($post_url) . '">' . esc_html($child_name) . '</a>';
    }
    return esc_html($child_name);
}

function getChildUrl($child_id)
{
    if (empty($child_id)) {
        return '';
    }

    $args = array(
        'post_type' => 'kids',
        'meta_key' => 'kid_id',
        'meta_value' => $child_id,
        'posts_per_page' => 1,
        'post_status' => 'publish',
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $post = $query->posts[0];
        return get_permalink($post->ID);
    }

    return '';
}

function get_all_products($product_id_param = null, $include_only_prod_tax = [])
{
    $args = ['limit' => -1];

    if (!empty($include_only_prod_tax)) {
        $tax_query = ['relation' => 'AND'];

        if (array_values($include_only_prod_tax) === $include_only_prod_tax) {
            $tax_query[] = [
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => $include_only_prod_tax,
                'operator' => 'IN'
            ];
        } else {
            foreach ($include_only_prod_tax as $taxonomy => $terms) {
                if (empty($terms))
                    continue;

                $tax_query[] = [
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $terms,
                    'operator' => 'IN'
                ];
            }
        }

        if (count($tax_query) > 1) {
            $args['tax_query'] = $tax_query;
        }
    }

    $products = wc_get_products($args);

    if (empty($products)) {
        return '<option value="">No Products Found</option>';
    }

    $product_id_param = (isset($product_id_param)) ? (string) $product_id_param : null;

    $product_options = '<option value="all" ' . selected($product_id_param, "all", false) . '>כל המוצרים</option>';

    foreach ($products as $product) {
        $product_id = (string) $product->get_id();
        $product_name = esc_html($product->get_name());

        $product_options .= '<option value="' . esc_attr($product_id) . '" ' . selected($product_id_param, $product_id, false) . '>' . $product_name . '</option>';
    }

    return $product_options;
}


function render_buttons_for_requests()
{
    $page_id_1 = '6650';
    $page_id_2 = '6633';
    $html_btn_1 = '<a href="' . get_permalink($page_id_2) . '" class="btn-primary-dm btn-requests-dm">הוסף בקשה</a>';
    $html_btn_2 = '<a href="' . get_permalink($page_id_1) . '" class="btn-primary-dm btn-requests-dm">הצג את כל הבקשות</a>';

    $html_section = $html_btn_1 . $html_btn_2;

    return $html_section;
}

function get_all_groups_options($group_value_selected = null, $args = array())
{
    $args = wp_parse_args($args, array(
        'nogroup' => true,
        'allgroups' => true
    ));

    $groups = get_posts(array('post_type' => 'camp-group', 'numberposts' => -1));

    if (empty($groups)) {
        return '<option value="">No Groups Found</option>';
    }

    $group_options = '';

    if ($args['allgroups']) {
        $group_options .= '<option value="all-groups" ' . selected($group_value_selected, "all-groups", false) . '>כל הקבוצות</option>';
    }

    if ($args['nogroup']) {
        $group_options .= '<option value="no-group" ' . selected($group_value_selected, "no-group", false) . '>אין קבוצות</option>';
    }

    foreach ($groups as $group) {
        $group_id = esc_attr($group->ID);
        $group_name = esc_html($group->post_title);
        $group_options .= '<option value="' . $group_id . '" ' . selected($group_value_selected, $group_id, false) . '>' . $group_name . '</option>';
    }

    return $group_options;
}

function get_all_pruducts_fields($excluded_keys = [], $include_only_keys = [], $args = null)
{
    if (!$args) {
        $args = ['limit' => -1];
    }
    $orders = wc_get_orders($args);

    if (!$orders) {
        return '<p>No orders found.</p>';
    }

    $product_fields = ['all'];
    foreach ($orders as $order) {
        foreach ($order->get_items() as $item_id => $item) {
            $order_item_custom_fields = getCustomFieldsFromProductOrder($item);

            foreach ($order_item_custom_fields as $order_item_custom_field => $key_item) {
                if (in_array($order_item_custom_field, $excluded_keys)) {
                    continue;
                }
                /*
                if (!in_array($order_item_custom_field, $include_only_keys)) {
                    continue;
                }*/
                if (!empty($include_only_keys) && !in_array($order_item_custom_field, $include_only_keys)) {
                    continue;
                }
                if (!in_array($order_item_custom_field, $product_fields)) {
                    $product_fields[] = $order_item_custom_field;
                }
            }
        }
    }

    return $product_fields;
}

function get_all_product_fields_with_values($excluded_keys = [], $include_only_keys = [], $args = null)
{
    if (!$args) {
        $args = ['limit' => -1];
    }

    $orders = wc_get_orders($args);
    if (!$orders) {
        return ['all'];
    }

    $field_value_combinations = ['all'];
    $seen = [];

    foreach ($orders as $order) {
        foreach ($order->get_items() as $item_id => $item) {
            $order_item_custom_fields = getCustomFieldsFromProductOrder($item);

            foreach ($order_item_custom_fields as $field_name => $key_item) {
                if (in_array($field_name, $excluded_keys)) {
                    continue;
                }
                if (!empty($include_only_keys) && !in_array($field_name, $include_only_keys)) {
                    continue;
                }

                $value = cleanMetaValue(getValueOfCustomFieldFromProductOrder($field_name, $item));
                $combo = "{$field_name}: {$value}";

                if (!isset($seen[$combo])) {
                    $field_value_combinations[] = $combo;
                    $seen[$combo] = true;
                }
            }
        }
    }

    return $field_value_combinations;
}



function render_select_options($options = [], $default = null)
{
    $output = '';

    foreach ($options as $value) {
        $selected = ($default !== null && $default == $value) ? ' selected' : '';
        $output .= '<option value="' . $value . '"' . $selected . '>' . $value . '</option>';
    }

    return $output;
}
function render_order_status_list($statuses = [], $default = null)
{
    $output = '';
    foreach ($statuses as $slug => $label) {
        $selected = ($default !== null && $default === $slug) ? ' selected' : '';
        $output .= '<option value="' . esc_attr($slug) . '"' . $selected . '>' . esc_html($label) . '</option>';
    }
    return $output;
}


function get_all_groups_checkboxes()
{
    $groups = get_posts(array(
        'post_type' => 'camp-group',
        'numberposts' => -1
    ));
    if (empty($groups)) {
        return '<p>No Groups Found</p>';
    }
    $group_options = '';
    $group_options .= '<p><input type="checkbox" value="no-group" class="no-group"> אין קבוצה</p>';
    foreach ($groups as $group) {
        $group_options .= '<p><input type="checkbox" value="' . esc_attr($group->ID) . '" id="group-' . esc_attr($group->ID) . '"> ' . esc_html($group->post_title) . '</p>';
    }
    return $group_options;
}


function getChildPostId($kid_id)
{
    $args = array(
        'post_type' => 'kids',
        'posts_per_page' => 1,
        'meta_query' => array(
            array(
                'key' => 'kid_id',
                'value' => $kid_id,
                'compare' => '=',
            ),
        ),
    );
    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $post_id = $query->posts[0]->ID;
        wp_reset_postdata();
        return $post_id;
    }
    return null;
}

function customFieldsOfPost($postId)
{
    $all_fields = get_post_meta($postId);
    if ($all_fields) {
        foreach ($all_fields as $field_name => $field_value) {
            $field_object = get_field_object($field_name, $postId);

            if ($field_object) {
                $label = $field_object['label'];
            } else {
                $label = ucwords(str_replace('_', ' ', $field_name));
            }
            $value = isset($field_value[0]) ? $field_value[0] : '';
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

function get_post_id_from_url($input)
{
    if (is_numeric($input)) {
        return intval($input);
    }
    return url_to_postid($input);
}

function renderAgeFieldsOfPost($post_id)
{
    if (empty($post_id)) {
        echo 'N/A';
        return;
    }

    $keys_to_try = ['Age', 'age', 'AGE'];
    $age = '';

    foreach ($keys_to_try as $key) {
        $value = get_post_meta($post_id, $key, true);
        if (!empty($value)) {
            $age = $value;
            break;
        }
    }

    echo !empty($age) ? esc_html($age) : 'N/A';
}

function renderGenderFieldsOfPost($post_id)
{
    if (empty($post_id)) {
        echo 'N/A';
        return;
    }

    // Try both post_meta and ACF (if available)
    $gender = get_post_meta($post_id, 'Gender', true);

    if (empty($gender)) {
        $gender = get_field('gender', $post_id); // ACF fallback
    }

    echo !empty($gender) ? esc_html($gender) : 'N/A';
}


function getCustomFieldsFromProductOrder($order_item, $excluded_keys = [])
{
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

function getValueOfCustomFieldFromProductOrder($key, $order_item, $excluded_keys = [])
{
    $custom_fields = getCustomFieldsFromProductOrder($order_item, $excluded_keys);

    return isset($custom_fields[$key]) ? $custom_fields[$key] : null;
}

function renderCustomFieldsFromProductOrder($custom_fields)
{
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

function enqueue_my_custom_script()
{
    wp_enqueue_script('my-custom-script', get_template_directory_uri() . '/js/my-custom-script.js', array('jquery'), null, true);
    wp_localize_script('my-custom-script', 'ajaxurl', admin_url('admin-ajax.php'));
}
add_action('wp_enqueue_scripts', 'enqueue_my_custom_script');

add_action('wp_ajax_update_groups_for_kid', 'update_groups_for_kid_callback');

function update_groups_for_kid_callback()
{
    if (isset($_POST['groups']) && isset($_POST['kid_id'])) {
        $kid_id = $_POST['kid_id'];
        $kid_name = $_POST['kid_name'];
        $kid_details = $_POST['kid_details'];
        $kid_school = $_POST['kid_school'];
        $order_id = $_POST['order_id'];
        $order_date = $_POST['order_date'];
        $order_details = $_POST['order_details'];
        $product_details = $_POST['product_details'];
        $product_field = $_POST['product_field'];
        $product_field_value = $_POST['product_field_value'];

        $groups = is_array($_POST['groups']) ? $_POST['groups'] : [];

        if (in_array('no-group', $groups)) {
            $groups = [];
        }

        $all_groups = get_posts([
            'post_type' => 'camp-group',
            'posts_per_page' => -1
        ]);

        foreach ($all_groups as $group_post) {
            $kids_list = (array) get_field('kids_list', $group_post->ID);
            $updated_list = [];

            foreach ($kids_list as $kid) {
                if ($kid['kid_id'] == $kid_id && $kid['order_id'] == $order_id && $kid['product_field'] == $product_field) {
                    continue;
                }
                $updated_list[] = $kid;
            }

            if (count($updated_list) !== count($kids_list)) {
                update_field('kids_list', $updated_list, $group_post->ID);
            }
        }

        foreach ($groups as $group_id) {
            $group_post = get_post($group_id);
            if ($group_post && $group_post->post_type === 'camp-group') {
                $kids_list = (array) get_field('kids_list', $group_post->ID);
                $kid_exists = false;

                foreach ($kids_list as $kid) {
                    if (($kid['kid_id'] == $kid_id) && ($kid['product_field'] == $product_field) && ($kid['order_id'] == $order_id)) {
                        $kid_exists = true;
                        break;
                    }
                }

                if (!$kid_exists) {
                    $kids_list[] = [
                        'kid_name' => $kid_name,
                        'kid_id' => $kid_id,
                        'kid_details' => $kid_details,
                        'kid_school' => $kid_school,
                        'order_id' => $order_id,
                        'order_date' => $order_date,
                        'order_details' => $order_details,
                        'product_details' => $product_details,
                        'product_field' => $product_field,
                        'product_field_value' => $product_field_value
                    ];
                    update_field('kids_list', $kids_list, $group_post->ID);
                }
            }
        }

        wp_send_json_success('Groups updated successfully!');
    } else {
        wp_send_json_error('Required data missing.');
    }

    wp_die();
}

function groupsLinks($groups)
{
    if (empty($groups)) {
        return 'No groups found';
    }

    $group_list = array_map('trim', explode(',', $groups));
    $group_links = [];

    foreach ($group_list as $group_name) {
        if ($group_name === 'no-group') {
            $group_links[] = 'אין קבוצה';
        } else {
            $group_post = get_page_by_title($group_name, OBJECT, 'camp-group');

            if ($group_post) {
                $group_links[] = '<a class="link-dm" target="_blank" href="' . get_permalink($group_post->ID) . '">' . esc_html($group_name) . '</a>';
            } else {
                $group_links[] = esc_html($group_name);
            }
        }
    }

    return implode(', ', $group_links);
}


function checkGroup($order_id, $kid_id, $product_field)
{
    $order_id = trim($order_id);
    $kid_id = trim($kid_id);
    if (empty($order_id) || empty($kid_id)) {
        return 'no-group';
    }
    $groups = [];
    $args = [
        'post_type' => 'camp-group',
        'posts_per_page' => -1
    ];

    $camp_groups = get_posts($args);


    if ($camp_groups) {
        foreach ($camp_groups as $group_post) {

            $kids_list = get_field('kids_list', $group_post->ID);

            if ($kids_list) {
                foreach ($kids_list as $kid) {
                    $kid_order_id = trim($kid['order_id']);
                    $kid_kid_id = trim($kid['kid_id']);
                    $kid_product_field = trim($kid['product_field']);

                    if ($kid_order_id == $order_id && $kid_kid_id == $kid_id && $kid_product_field == $product_field) {
                        $groups[] = get_the_title($group_post->ID);
                    }
                }
            }
        }
    }

    if (empty($groups)) {
        return 'no-group';
    }

    return '' . implode(', ', $groups);
}

function camp_group_kid_list()
{
    if (!current_user_can('manage_woocommerce') && !current_user_can('editor') && !current_user_can('administrator')) {
        return '<p class="dm-error-section">אין לך הרשאה לצפות בתוכן.</p>';
    }
    if (!is_singular('camp-group')) {
        return '';
        echo "<p>Incorrect Type post</p>";
    }

    global $post;
    $kid_list = get_field('kids_list', $post->ID);

    if (empty($kid_list)) {
        return '<p>No kids found in this camp group.</p>';
    }
    $first_row = $kid_list[0];
    $headers = array_keys($first_row);

    ob_start();
    ?>
    <table id="kids-from-groups" class="dm-table" border="1"
        name="table_products-from_orders<?php echo "-" . date('Y-m-d'); ?>">
        <thead>
            <tr>
                <?php
                foreach ($headers as $header) {
                    $field_object = get_field_object($header, $post->ID);
                    $label = $field_object ? $field_object['label'] : ucwords(str_replace('_', ' ', $header));
                    ?>
                    <th column_name="<?php echo $label; ?>">
                        <?php echo $label; ?>
                    </th>
                <?php } ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($kid_list as $kid): ?>
                <tr>
                    <?php foreach ($headers as $header):
                        $field_object = get_field_object($header, $post->ID);
                        $label = $field_object ? $field_object['label'] : ucwords(str_replace('_', ' ', $header));
                        ?>
                        <td column_name="<?php echo $label; ?>">
                            <?php echo $kid[$header]; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>

    </table>
    <?php
    return ob_get_clean();
}

function getCustomField($postId, $fieldName)
{
    $fieldValue = get_field($fieldName, $postId);

    if (!$fieldValue) {
        return null;
    }

    return $fieldValue;
}

function get_order_status_list()
{
    if (!function_exists('wc_get_order_statuses')) {
        return array();
    }

    $statuses = wc_get_order_statuses(); // Returns array like: ['wc-pending' => 'Pending payment', ...]

    // Optionally, remove the 'wc-' prefix for cleaner keys
    $cleaned_statuses = array();
    foreach ($statuses as $key => $label) {
        $cleaned_key = str_replace('wc-', '', $key);
        $cleaned_statuses[$cleaned_key] = $label;
    }

    return $cleaned_statuses;
}


function custom_code_css_js_manage_kids_in_groups()
{
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

        .dm-error-section {
            width: 100%;
            min-height: 400px;
            text-align: center;
            font-weight: 600;
            font-size: 24px;
            line-height: 32px;
            color: rgb(149, 7, 52);
            background-color: rgba(103, 31, 54, 0.12);
            padding: 32px;
            border-radius: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .dm-table {
            position: relative;
            min-width: 800px;
        }

        .dm-table.animation:before {
            z-index: 200;
            content: "";
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            inset: 0;
            background-color: rgba(226, 226, 226, 0.62);
            min-width: 32px;
            min-height: 32px;
            background-repeat: no-repeat;
            background-position: center;
            background-image: url(data:image/gif;base64,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);
        }

        .dm-table p {
            margin-bottom: 4px;
        }

        .dm-label {
            display: flex;
            flex-direction: column;
            gap: 3px;
            font-weight: 600 !important;
            margin-bottom: 6px !important;
            font-size: 17px !important;
            line-height: 22px !important;
        }

        .dm-label span {
            margin-bottom: 2px !important;
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

        .link-dm.view-order {
            font-weight: 600;
            text-decoration: underline;
            padding: 3px 8px 7px 8px;
            background-color: rgba(164, 245, 250, 0.62);
            border-radius: 8px;
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

        .dm-title-section {
            background-color: #00bcf1;
            width: 100%;
            padding: 12px 25px;
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .dm-title {
            color: #fff !important;
            font-size: 22px !important;
            line-height: 26px !important;
            font-weight: 600 !important;
        }

        .btn-dm {
            font-size: 14px;
            min-width: 20px;
            font-weight: 600;
            padding: 3px 4px;
            border: 1px solid #0073aa !important;
            line-height: 14px;
            border-radius: 6px;
            background-color: #fff !important;
            color: #0073aa !important;
        }

        .btn-dm:hover,
        .btn-dm:focus {
            color: #fff !important;
            background-color: #0073aa !important;
        }

        .btn-primary-dm {
            font-size: 16px;
            font-weight: 600;
            padding: 5px 16px;
            border: 1px solid #0073aa !important;
            line-height: 22px;
            border-radius: 6px;
            background-color: #fff !important;
            color: #0073aa !important;
        }

        .btn-filters-dm {
            background-color: rgb(199, 232, 247) !important;
        }

        .btn-primary-dm:hover,
        .btn-primary-dm:focus {
            color: #fff !important;
            background-color: #0073aa !important;
        }

        .btn-dm[name="manage-groups"] {
            width: 100%;
            padding: 8px 14px;
        }

        .btn-requests-dm {
            color: rgb(255, 255, 255) !important;
            background-color: rgb(234, 171, 89) !important;
            border: 1px solid rgb(234, 171, 89) !important;
        }

        .btn-requests-dm:hover,
        .btn-requests-dm:focus {
            color: #fff !important;
            background-color: #ff9c1d !important;
            border: 1px solid #ff9c1d !important;
        }

        .woocommerce-orders-filters {
            padding-bottom: 26px;
        }

        .woocommerce-orders-filters select,
        .woocommerce-orders-filters input {
            font-size: 16px;
            padding: 6px 12px;
            border: 1px solid #333;
            line-height: 23px;
            border-radius: 6px;
            background-color: #fff;
            color: #333;
        }

        .dm-table tr {
            position: relative;
        }

        .dm-table .popup-style {
            position: absolute;
            background: #fff;
            border: 1px solid #b4b4b4;
            padding: 25px 25px;
            width: 100%;
            left: 0;
            right: 0;
            display: flex;
            flex-direction: row;
            gap: 26px;
            row-gap: 12px;
            flex-wrap: wrap;
            z-index: 12;
            box-shadow: 4px 4px 14px #33333370;
            border-radius: 6px;
        }

        .dm-table .popup-style.group-box {
            right: auto;
            margin-left: 93px;
            width: auto;
            margin-top: -22px;
            min-width: 160px;
        }

        .dm-table .popup-style.group-box .group-checkboxes {
            width: 100%;
        }

        .dm-table .popup-style.child-details-box {
            right: auto;
            left: auto;
            width: auto;
            min-width: 260px;
        }

        .dm-table .popup-style.order-details-box {
            right: auto;
            left: auto;
            width: auto;
            max-width: 240px;
        }

        .dm-table tbody .field-label {
            font-weight: 600;
        }

        .dm-flex {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            gap: 16px;
        }

        .dm-table tr[order-data-product-name^="Test"] {
            background-color: rgb(209, 209, 209);
        }

        .dm-table tr[group="false"] {
            background-color: #e4eef7;
        }

        .dm-table tr[group="true"] {
            background-color: rgb(224, 244, 218);
        }

        .dm-export-buttons {
            width: 100%;
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            gap: 24px;
            align-items: end;
        }

        .dm-export-buttons #export_delimiter {
            font-size: 14px;
            line-height: 14px;
            padding: 2px 12px;
            width: 45px;
            border-radius: 8px;
        }

        .dm-export-buttons .dm-label {
            display: flex;
            flex-direction: column;
            gap: 2px;
            margin-bottom: 0px !important;
        }

        .dm-export-buttons .dm-label span {
            font-size: 12px;
            line-height: 14px;
        }

        .dm-bulk-section {
            justify-content: end;
            align-items: end;
        }

        @media only screen and (min-width: 900px) {
            .dm-bulk-section {
                margin-bottom: -42px;
                width: 50%;
                position: absolute;
                left: 0;
            }
        }

        .dm-bulk-section select {
            padding: 4px 15px;
            width: 200px;
        }

        .dm-bulk-section label {
            margin-bottom: 0px !important;
        }

        .dm-bulk-section label span {
            font-size: 12px;
            line-height: 14px;
        }

        .row_kid_id,
        .tab_kid_id {
            display: none;
        }

        [column_name="Kid Id"] {
            display: none;
        }

        [column="Product Parameters"] {
            font-size: 10px;
        }

        [column_name="Updates"],
        [class="row_update"] {
            /*display: none;*/
        }

        [column="Group"] {
            text-align: end;
        }

        .order-status {
            font-size: 10px;
            line-height: 14px;
            font-weight: 600;
            width: 100%;
            display: block;
            position: absolute;
            opacity: 0;
            bottom: 4px;
            right: 10px;
            transition: all 0.2s ease-in-out;
        }

        .woocommerce-orders-table tr:hover [column="Order ID"] .order-status {
            opacity: 1;
            transition: all 0.2s ease-in-out;
        }

        .count-items {
            font-size: 16px;
            font-weight: 600;
            padding: 5px 16px;
            border: 1px solid rgb(64, 66, 66) !important;
            line-height: 22px;
            border-radius: 6px;
            color: rgb(64, 66, 66) !important;
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

        function toggleDetails(button) {
            var details = button.closest("td").querySelector(".show-more-details");
            if (details) {
                if (button.closest('.row_groups')) {
                    let allRowGroups = document.querySelectorAll('.row_groups .show-more-details');
                    allRowGroups.forEach(function (otherDetails) {
                        if (otherDetails !== details) {
                            otherDetails.style.display = 'none';
                        }
                    });

                }

                if (details.style.display === "none") {
                    details.style.display = "block";
                    button.textContent = "-";
                } else {
                    details.style.display = "none";
                    button.textContent = "+";
                }
            }
        }
        function toggleThis(button) {
            let details = button.closest("p").nextElementSibling;

            if (!details || !details.classList.contains("show-more-details")) {
                console.error("No corresponding .show-more-details found");
                return;
            }
            if (!details) {
                console.log(details);
            }
            if (details.style.display === "none" || details.style.display === "") {
                details.style.display = "block";
                button.textContent = "-";
            } else {
                details.style.display = "none";
                button.textContent = "+";
            }
        }
        function updatePostGroups(button, preselectedGroup = null) {
            const kidId = button.getAttribute('kid_id');
            const orderItemCustomField = button.getAttribute('order_item_custom_field');
            const orderId = button.getAttribute('order_id');
            const kidDataScript = document.getElementById(`kid-data-${kidId}-${orderId}-${orderItemCustomField}`);

            const dmTable = document.querySelector('.dm-table');
            if (dmTable) {
                dmTable.classList.add('animation');
            }

            if (!kidDataScript) {
                console.error('Kid data script not found!');
                return;
            }

            let kidData;
            try {
                kidData = JSON.parse(kidDataScript.textContent);
            } catch (e) {
                console.error('Failed to parse kid data:', e);
                return;
            }

            if (!kidData || !kidData.kid_id || !kidData.kid_name) {
                console.error('Incomplete kid data!');
                return;
            }

            const showMoreDetailsContainer = button.closest('.show-more-details');
            if (!showMoreDetailsContainer) {
                console.error('No .show-more-details container found!');
                return;
            }

            const groupCheckboxesContainer = showMoreDetailsContainer.querySelector('.group-checkboxes');
            if (!groupCheckboxesContainer) {
                console.error('No .group-checkboxes container found!');
                return;
            }

            const selectedGroups = [];
            if (preselectedGroup) {
                selectedGroups.push(preselectedGroup);
            } else {
                const noGroupCheckbox = groupCheckboxesContainer.querySelector(".no-group:checked");
                if (noGroupCheckbox) {
                    selectedGroups.push("no-group");
                } else {
                    groupCheckboxesContainer.querySelectorAll('input[type="checkbox"]:checked').forEach((checkbox) => {
                        selectedGroups.push(checkbox.value);
                    });
                }
            }

            const data = {
                action: "update_groups_for_kid",
                groups: selectedGroups,
                kid_id: kidData.kid_id,
                kid_name: kidData.kid_name,
                kid_details: kidData.kid_details,
                kid_school: kidData.kid_school,
                order_id: kidData.order_id,
                order_date: kidData.order_date,
                order_details: kidData.order_details,
                product_details: kidData.product_details,
                product_field: kidData.product_field,
                product_field_value: kidData.product_field_value
            };

            console.log("Data to send:", data);

            return jQuery.post(ajaxurl, data, function (response) {
                console.log("Server response:", response);

                if (response.success) {
                    console.log("Data:", data);
                    if (selectedGroups.includes("no-group")) {
                        //alert("Kid removed from all groups.\n\nServer Message: " + response.data);
                        //location.reload();
                    } else {
                        //alert("Groups updated successfully!\n\nServer Message: " + response.data);
                        //location.reload();
                    }
                    if (button.closest('.show-more-details').style.display === 'block') {
                        button.closest('.show-more-details').style.display = 'none';
                    }
                    button.closest('td[column="Group"]').style.background = 'rgb(121 181 89 / 51%)';
                } else {
                    alert("Error updating groups.\n\nServer Message: " + (response.data || "Unknown error"));
                }

                if (dmTable) {
                    dmTable.classList.remove('animation');
                }
            });
        }
        async function updateTable(queryTable) {
            const dmTable = document.querySelector('.dm-table');
            if (dmTable) { dmTable.classList.add('animation'); }

            let table = document.querySelector(`#${queryTable}`);
            if (!table) {
                console.warn(`Table with ID "${queryTable}" not found.`);
                return;
            }

            console.log(`Updating table: ${queryTable}`);

            let rows = table.querySelectorAll("tr");

            const selectedGroup = document.querySelector('#group-update-bulk')?.value || null;

            for (let row of rows) {
                let updateCheckbox = row.querySelector("td[column='Updates'].row_update input[type='checkbox'][value='update']");

                if (updateCheckbox && updateCheckbox.checked) {
                    let button = row.querySelector("td[column='Group'] button[name='manage-groups']");

                    if (button) {
                        try {
                            await updatePostGroups(button, selectedGroup);
                        } catch (error) {
                            console.error("Error updating group:", error);
                        }
                    } else {
                        console.warn(`Button not found in row: ${row.rowIndex}`);
                    }
                }
            }

            if (dmTable) { dmTable.classList.remove('animation'); }
            location.reload();
        }
        function applyFilters() {
            let filters = [];

            let productFilter = document.getElementById("product-filter")?.value || "all";
            if (productFilter !== "all") {
                filters.push("product_id=" + encodeURIComponent(productFilter));
            }

            let productFieldFilter = document.getElementById("product-field-filter")?.value || "all";
            if (productFieldFilter !== "all") {
                filters.push("product_field=" + encodeURIComponent(productFieldFilter));
            }

            let statusFilter = document.getElementById("status-filter")?.value || "all";
            if (statusFilter !== "all") {
                filters.push("status=" + encodeURIComponent(statusFilter));
            }

            let groupFilter = document.getElementById("group-filter")?.value || "all-groups";
            if (groupFilter !== "all-groups") {
                filters.push("group_id=" + encodeURIComponent(groupFilter));
            }

            let ageFromFilter = document.getElementById("age-from-filter")?.value || "";
            if (ageFromFilter) {
                filters.push("age_from=" + encodeURIComponent(ageFromFilter));
            }

            let ageToFilter = document.getElementById("age-to-filter")?.value || "";
            if (ageToFilter) {
                filters.push("age_to=" + encodeURIComponent(ageToFilter));
            }

            let kidIdFilter = document.getElementById("kid-id-filter")?.value || "";
            if (kidIdFilter) {
                filters.push("kid_id=" + encodeURIComponent(kidIdFilter));
            }

            let kidNameFilter = document.getElementById("kid-name-filter")?.value || "";
            if (kidNameFilter) {
                filters.push("kid_name=" + encodeURIComponent(kidNameFilter));
            }

            let dateFilter = document.getElementById("date-filter")?.value || "";
            if (dateFilter) {
                filters.push("date=" + encodeURIComponent(dateFilter));
            }

            let includeAdvancedFilters = document.querySelector(".include-advance-filters")?.checked ?? false;
            if (includeAdvancedFilters) {
                let excludeProductFields = document.getElementById("exclude_product_fields")?.value || "";
                if (excludeProductFields) {
                    filters.push("exclude_product_fields=" + encodeURIComponent(excludeProductFields));
                }
                let includeOnlyProductFields = document.getElementById("include_only_product_fields")?.value || "";
                if (includeOnlyProductFields) {
                    filters.push("include_only_product_fields=" + encodeURIComponent(includeOnlyProductFields));
                }
                let includeOnlyProdTax = document.getElementById("include_only_prod_tax")?.value || "";
                if (includeOnlyProdTax) {
                    filters.push("include_only_prod_tax=" + encodeURIComponent(includeOnlyProdTax));
                }
                let includeOnlyStatusOrder = document.getElementById("include_only_order_status")?.value || "";
                if (includeOnlyStatusOrder) {
                    filters.push("include_only_order_status=" + encodeURIComponent(includeOnlyStatusOrder));
                }

                let excludeOrdersNotFinalizedCheckbox = document.getElementById("excule_orders_not_finalized");
                if (excludeOrdersNotFinalizedCheckbox?.checked) {
                    filters.push("excule_orders_not_finalized=1");
                }

            }

            let queryString = filters.join("&");
            let currentUrl = window.location.href.split('?')[0];
            window.location.href = currentUrl + "?" + queryString;
        }
        function resetFilters() {
            window.location.href = window.location.pathname;
        }
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll("tr .group-checkboxes input[type='checkbox']").forEach(function (checkbox) {
                checkbox.addEventListener("change", function () {
                    let row = this.closest("tr");

                    if (row) {
                        let updateCheckbox = row.querySelector("td[column='Updates'] input[type='checkbox'][value='update']");
                        let allCheckboxes = row.querySelectorAll(".group-checkboxes input[type='checkbox']");
                        let anyChecked = Array.from(allCheckboxes).some(cb => cb.checked);

                        if (updateCheckbox) {
                            updateCheckbox.checked = anyChecked;
                        }

                        if (this.checked) {
                            row.querySelectorAll('.group-checkboxes input[type="checkbox"]').forEach(otherCheckbox => {
                                if (otherCheckbox !== this) {
                                    otherCheckbox.checked = false;
                                }
                            });
                        }
                    }
                });
            });
        });
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll(".dm-table").forEach((table) => {
                let tableName = table.getAttribute("name") || "table";

                const container = document.createElement("div");
                container.className = "dm-export-buttons export-buttons-container";

                const delimiterLabel = document.createElement("label");
                delimiterLabel.className = "dm-label";
                delimiterLabel.innerHTML = `<span>תוחם</span>`;

                const delimiterInput = document.createElement("input");
                delimiterInput.type = "text";
                delimiterInput.id = "export_delimiter";
                delimiterInput.name = "export_delimiter";
                delimiterInput.placeholder = ";/,";
                delimiterInput.value = ",";

                delimiterLabel.appendChild(delimiterInput);
                container.appendChild(delimiterLabel);

                const advancedButton = document.createElement("button");
                advancedButton.className = "btn-primary-dm";
                advancedButton.innerHTML = "&#8603; טבלת ייצוא מתקדמת";
                advancedButton.setAttribute("export_type", "advance");
                advancedButton.onclick = function () {
                    const delimiter = delimiterInput.value || ",";
                    downloadTableCSV(table, `${tableName}_advanced.csv`, delimiter);
                };

                const simpleButton = document.createElement("button");
                simpleButton.className = "btn-primary-dm";
                simpleButton.innerHTML = "&#8594; טבלת ייצוא פשוטה";
                simpleButton.setAttribute("export_type", "simple");
                simpleButton.onclick = function () {
                    const delimiter = delimiterInput.value || ",";
                    downloadTableCSV(table, `${tableName}_simple.csv`, delimiter);
                };

                container.appendChild(advancedButton);
                container.appendChild(simpleButton);

                table.insertAdjacentElement("afterend", container);
            });
        });
        function downloadTableCSV(table, filename, delimiter) {
            delimiter = delimiter || ",";
            let csv = [];
            let headers = [];
            let rows = [];
            let isSimpleExport = filename.includes("_simple");

            table.querySelectorAll("tr").forEach((row, rowIndex) => {
                let rowData = [];
                let extraFields = {};

                row.querySelectorAll("th, td").forEach((cell) => {
                    let text = cell.cloneNode(true);
                    text.querySelectorAll("button, script, .group-box, .view-order, span.order-status").forEach(el => el.remove());

                    if (isSimpleExport) {
                        text.querySelectorAll(".show-more-details, .popup-style, .child-details-box").forEach(el => el.remove());
                    }

                    text.querySelectorAll("select").forEach(selectEl => {
                        let selectedText = selectEl.options[selectEl.selectedIndex]?.text.trim() || "";
                        let selectedDiv = document.createElement("div");
                        selectedDiv.innerText = selectedText;
                        selectEl.replaceWith(selectedDiv);
                    });

                    let extraDetails = [];
                    text.querySelectorAll(".show-more-details p").forEach(p => {
                        let labelEl = p.querySelector(".field-label");
                        let valueEl = p.querySelector(".field-value");

                        if (!isSimpleExport && labelEl && valueEl) {
                            let label = labelEl.innerText.trim();
                            value = valueEl.innerText.trim();

                            extraFields[label] = value;
                            if (!headers.includes(label)) {
                                headers.push(label);
                            }
                            p.remove();
                        } else if (!p.querySelector(".field-label") && !p.querySelector(".field-value")) {
                            p.remove();
                        }
                    });

                    let finalText = text.innerText.trim();
                    if (extraDetails.length > 0) {
                        finalText += "\n" + extraDetails.join("\n");
                    }
                    finalText = finalText.replaceAll(delimiter, "");
                    finalText = finalText.replace(/"/g, '""');
                    rowData.push(finalText);
                });

                rows.push({ rowData, extraFields });
            });

            if (rows.length > 0) {
                if (!isSimpleExport) {
                    rows[0].rowData = [...rows[0].rowData, ...headers];
                }
            }


            console.log("Rows:", rows.map((r, i) => `Index ${i}: ${r.rowData.join(delimiter)}`));

            rows.forEach(({ rowData, extraFields }) => {
                let extraValues = isSimpleExport ? [] : headers.map(label => `"${extraFields[label] || ""}"`);
                csv.push([...rowData, ...extraValues].join(delimiter));
            });

            console.log("Final CSV Output:\n", csv.join("\n"));
            /*
            let csvContent = "data:text/csv;charset=utf-8,\uFEFF" + csv.join("\n");
            let encodedUri = encodeURI(csvContent);
            let link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", filename);
            */
            const csvContent = csv.join('\n');
            const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' }); // BOM for Hebrew
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
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
    if (!current_user_can('manage_woocommerce') && !current_user_can('editor') && !current_user_can('administrator')) {
        return '<p class="dm-error-section">אין לך הרשאה לצפות בתוכן.</p>';
    }

    $args = array('limit' => -1);

    $date_param = null;

    if (!empty($_GET['date']) && strtotime($_GET['date']) !== false) {
        $date_param = $_GET['date'];
    } else {
        $preferred_date = get_option('preferred_date');
        if (!empty($preferred_date) && strtotime($preferred_date) !== false) {
            $date_param = $preferred_date;
        } else {
            $date_param = date('Y-m-d', strtotime('-7 days'));
        }
    }

    $date = DateTime::createFromFormat('Y-m-d', $date_param);

    if ($date instanceof DateTime) {
        $args['date_query'] = array(
            'after' => $date->format('Y-m-d'),
            'inclusive' => true, // <-- include the date itself
        );
    }

    $age_from_param = isset($_GET['age_from']) ? $_GET['age_from'] : null;
    $age_to_param = isset($_GET['age_to']) ? $_GET['age_to'] : null;
    $birth_date = isset($_GET['bith_date']) ? $_GET['bith_date'] : null;
    $kid_name_param = isset($_GET['kid_name']) ? $_GET['kid_name'] : null;
    $kid_id_param = isset($_GET['kid_id']) ? $_GET['kid_id'] : null;
    $group_param = isset($_GET['group_id']) ? $_GET['group_id'] : null;
    $product_param = isset($_GET['product_id']) ? $_GET['product_id'] : null;
    $product_field_param = isset($_GET['product_field']) ? $_GET['product_field'] : null;

    $use_pagination = isset($_GET['use_pagination']) ? ($_GET['use_pagination'] == '1') : true;
    $current_page = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : (isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1);
    $items_per_page = 20;

    $exclude_product_fields = isset($_GET['exclude_product_fields']) ? array_map('trim', explode(',', $_GET['exclude_product_fields'])) : [];
    $excluded_keys = $exclude_product_fields;

    if (!is_array($excluded_keys) || empty($excluded_keys)) {
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
            '_כתובת אימייל (לשליחת חשבונית) )',
            '_תאריך',
            '_כתובת אימייל (לשליחת חשבונית)',
            '_אישור פרסום תמונות',
            ' _זיהוי ילד',
            '_ת.ז ילד',
            '_pewc_product_extra_id',
            '_מספר שבועות',
            '_הנחת שבועיים',
            '_הנחת שבועיים צהרון'
        ];
    }

    $include_only_keys = isset($_GET['include_only_product_fields']) ? array_map('trim', explode(',', $_GET['include_only_product_fields'])) : [];

    if (!is_array($include_only_keys) || empty($include_only_keys)) {
        $include_only_keys = [
            '_שבוע 1: 1/8 - 28/7',
            '_שבוע 2: 8/8 - 4/8',
            '_שבוע 3: 11/8-16/8',
            '_שבוע 1: 27/7-31/7',
            '_שבוע 1',
            '_שבוע 2',
            '_שבוע 3',
            '_שבוע 4',
            '_שבוע 5'
        ];
    }

    $include_only_prod_tax = isset($_GET['include_only_prod_tax']) ? array_map('trim', explode(',', $_GET['include_only_prod_tax'])) : [];
    if (!is_array($include_only_prod_tax) || empty($include_only_prod_tax)) {
        $include_only_prod_tax = [
            'show-camp'
        ];
    }
    $order_status_list = array_merge(['all' => 'All'], get_order_status_list());


    $include_only_order_status = isset($_GET['include_only_order_status'])
        ? ($_GET['include_only_order_status'] !== 'all'
            ? sanitize_text_field($_GET['include_only_order_status'])
            : null)
        : 'completed';
    $exclude_orders_not_finalized = isset($_GET['excule_orders_not_finalized']) && $_GET['excule_orders_not_finalized'] == '1';


    $orders = wc_get_orders($args);

    if (!$orders) {
        return '<p>No orders found.</p>';
    }

    ob_start();
    ?>

    <div class="dm-title-section">
        <h2 class="dm-title">שיבוץ לקייטנות</h2>
    </div>
    <div id="dm-filters" class="woocommerce-orders-filters">
        <div class="filters dm-flex">
            <label class="dm-label"><span> מוצר </span>
                <select id="product-filter" name="product">
                    <?php echo get_all_products($product_param, $include_only_prod_tax); ?>
                </select>
            </label>
            <label class="dm-label"><span>קבוצה</span>
                <select id="group-filter" name="group">
                    <?php echo get_all_groups_options($group_param); ?>
                </select>
            </label>
            <label class="dm-label">
                <span>מגיל</span>
                <input type="number" id="age-from-filter" name="age_from" placeholder="5"
                    value="<?php echo $age_from_param; ?>">
            </label>
            <label class="dm-label">
                <span>עד גיל</span>
                <input type="number" id="age-to-filter" name="age_to" placeholder="20" value="<?php echo $age_to_param; ?>">
            </label>
            <label class="dm-label">
                <span>תאריך לידה</span>
                <input type="text" id="birth_date" name="birth_date" placeholder="yyyy-mm-dd"
                    value="<?php echo $birth_date; ?>">
            </label>
            <label class="dm-label">
                <span>הרשמה לקבוצה</span>
                <?php $product_fields = get_all_product_fields_with_values($excluded_keys, $include_only_keys); ?>
                <select id="product-field-filter" name="product-field">
                    <?php echo render_select_options($product_fields, $product_field_param); ?>
                </select>
            </label>
            <label class="dm-label">
                <span>ת.ז ילד</span>
                <input type="number" id="kid-id-filter" name="kid_id" placeholder="123456789"
                    value="<?php echo $kid_id_param; ?>">
            </label>
            <label class="dm-label">
                <span> שם ילד</span>
                <input type="text" id="kid-name-filter" name="kid_name" placeholder="John Smith"
                    value="<?php echo $kid_name_param; ?>">
            </label>
            <label class="dm-label"><span>מתאריך</span><input type="text" id="date-filter" name="date"
                    placeholder="10-02-2025" value="<?php echo $date_param; ?>">
            </label>
        </div>
        <p>
            <button type="button" class="btn-dm show-more-btn" onclick="toggleThis(this)">+</button>
        <div class="show-more-details" style="display: none;">
            <div>
                <label class="dm-label"><span>הפעל מסננים מתקדמים</span></label>
                <input type="checkbox" value="include-advance-filters" class="include-advance-filters">
                <span>כלול מסננים מתקדמים</span>
            </div>
            <div class="advance-filters dm-flex">
                <label class="dm-label"> <span>אל תכלול שדות מוצר</span><input type="text" name="exclude_product_fields"
                        id="exclude_product_fields" name="exclude_product_fields"
                        value="<?php echo implode(',', array_values($excluded_keys)); ?>">
                </label>
                <label class="dm-label"> <span>כולל רק שדות מוצר</span><input type="text" name="include_only_product_fields"
                        id="include_only_product_fields" name="include_only_product_fields"
                        value="<?php echo implode(',', array_values($include_only_keys)); ?>">
                </label>
                <label class="dm-label"> <span>כלול רק טקסונומיות של מוצרים</span><input type="text"
                        name="include_only_prod_tax" id="include_only_prod_tax" name="include_only_prod_tax"
                        value="<?php echo implode(',', array_values($include_only_prod_tax)); ?>">
                </label>

                <label class="dm-label"> <span>כלול סטטוס הזמנה בלבד</span>
                    <select id="include_only_order_status" name="include_only_order_status">
                        <?php
                        echo render_order_status_list($order_status_list, !empty($include_only_order_status) ? $include_only_order_status : 'all');
                        ?>
                    </select>
                </label>
                <label class="dm-label"> <span>הסתר הזמנות שלא שולמו</span>
                    <div>
                        <input type="checkbox" value="excule_orders_not_finalized" id="excule_orders_not_finalized" <?php if ($excule_orders_not_finalized)
                            echo "checked"; ?>>
                        <span> הצג הזמנות ששולמו בלבד</span>
                    </div>
                </label>
                <label class="dm-label"> <span>הפעל חלוקה לעמודים</span>
                    <div>
                        <input type="checkbox" value="use_pagination" id="use_pagination" <?php if ($use_pagination)
                            echo "checked"; ?>>
                        <span>השתמש בדפדוף (20 לשורה)</span>
                    </div>
                </label>
                <?php
                add_action('admin_post_update_preferred_date', function () {
                    if (
                        isset($_POST['preferred_date'], $_POST['update_preferred_date_nonce']) &&
                        current_user_can('manage_options') &&
                        wp_verify_nonce($_POST['update_preferred_date_nonce'], 'update_preferred_date_action')
                    ) {
                        update_option('preferred_date', sanitize_text_field($_POST['preferred_date']));
                    }
                    wp_redirect($_POST['_wp_http_referer'] ?? admin_url());
                    exit;
                });
                $preferred_date = get_option('preferred_date');
                ?>

                <?php if (isset($preferred_date)): ?>
                    <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>" class="d-flex"
                        style="width: 100%; min-width: 100%; display:none">
                        <input type="hidden" name="action" value="update_preferred_date">
                        <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
                        <?php wp_nonce_field('update_preferred_date_action', 'update_preferred_date_nonce'); ?>
                        <div class="dm-flex" style="align-items: end;">
                            <label class="dm-label" style="margin: 0px !important;">
                                <span>Preferred Date</span>
                                <input type="date" name="preferred_date" id="preferred_date"
                                    value="<?php echo esc_attr($preferred_date); ?>">
                            </label>
                            <button type="submit" class="btn-primary-dm">Save Date</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        </p>
        <div class="dm-flex">
            <button class="btn-primary-dm btn-filters-dm" onclick="applyFilters()">החל מסננים &#128269</button>
            <button class="btn-primary-dm" onclick="resetFilters()">לאפס מסננים</button>
            <?php echo render_buttons_for_requests(); ?>
        </div>
    </div>

    <div id="products-from-orders" class="dm-table tab-content active"
        name="tabel_products-from-orders<?php echo "-" . $date_param; ?>">
        <table class="woocommerce-orders-table">
            <thead>
                <tr>
                    <th class="tab_order_id" column_name="Order Id">מזהה הזמנה</th>
                    <th class="tab_order_details" column_name="Order Details">פרטי הזמנה</th>
                    <th class="tab_product" column_name="Product">מוצר</th>
                    <th class="tab_product_parameter" column_name="Product Parameter">שדה מוצר</th>
                    <th class="tab_kid_name" column_name="Kid Name">שם הילד</th>
                    <th class="tab_kid_id" column_name="Kid Id">תעודת זהות הילד</th>
                    <th class="tab_kid_age" column_name="Kid Age"> גיל הילד </th>
                    <th class="tab_kid_gender" column_name="Kid Gender"> מין הילד </th>
                    <th class="tab_kid_school" column_name="Kid School">שם בית ספר, גן וכיתה</th>
                    <th class="tab_product_parameters" column_name="Prod Params">פרמטרי מוצר</th>
                    <th class="tab_groups" column_name="Groups">קבוצה</th>
                    <th class="tab_updates" column_name="Updates">*</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $count_items = 0;
                foreach ($orders as $order):
                    foreach ($order->get_items() as $item_id => $item):
                        $product_name = $item->get_name();
                        $product_id = $item->get_product_id();
                        $child_name = '';
                        $child_id = '';
                        $child_age = '';

                        // Check for custom fields for child name and child id
                        foreach ($item->get_formatted_meta_data('') as $meta) {
                            if (strpos($meta->key, 'ת.ז ילד') !== false) {
                                $child_id = cleanMetaValue($meta->value);
                            }
                            if (strpos($meta->key, 'שם הילד/ה') !== false) {
                                $child_name = cleanMetaValue($meta->value);
                            }
                        }
                        $product_categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'names']);
                        $match = false;

                        foreach ($include_only_prod_tax as $taxonomy_name) {
                            if (in_array($taxonomy_name, $product_categories, true)) {
                                $match = true;
                                break;
                            }
                        }

                        if (!$match) {
                            continue;
                        }

                        $child_name = isset($child_name) ? cleanMetaValue($child_name) : null;
                        $child_id = isset($child_id) ? cleanMetaValue($child_id) : null;
                        $post_child_url = getChildPostId(cleanMetaValue($child_id));
                        $child_url = ($child_name && $child_id) ? showChildUrl($child_name, $child_id) : null;
                        $child_age = getCustomField($post_child_url, "age");
                        $child_birth_date = getCustomField($post_child_url, "birth_date");
                        $child_school = implode(' ', array_filter([
                            getCustomField($post_child_url, "school_name"),
                            getCustomField($post_child_url, "class"),
                            getCustomField($post_child_url, "class_number"),
                            getCustomField($post_child_url, "kindergarden_type")
                        ]));

                        $order_date = $order->get_date_created()->date('Y-m-d') ?? null;
                        $order_status = $order->get_status();
                        $order_id = $order->get_id() ?? null;
                        $order_item_custom_fields = getCustomFieldsFromProductOrder($item);

                        if ($order_id == 9489) {
                            echo "<p>found</p>";
                        }
                        foreach ($order_item_custom_fields as $order_item_custom_field => $key_item):
                            $groups = checkGroup($order_id, $child_id, $order_item_custom_field);
                            $order_item_custom_field_value = cleanMetaValue(getValueOfCustomFieldFromProductOrder($order_item_custom_field, $item));

                            if (is_numeric($child_age) && is_numeric($age_from_param) && ((int) $child_age < (int) $age_from_param)) {
                                continue;
                            }

                            if (is_numeric($child_age) && is_numeric($age_to_param) && ((int) $child_age > (int) $age_to_param)) {
                                continue;
                            }
                            if (!empty($child_birth_date) && !empty($birth_date)) {
                                $child_birth_date_fmt = date('Y-m-d', strtotime($child_birth_date));
                                $birth_date_fmt = date('Y-m-d', strtotime($birth_date));

                                if ($child_birth_date_fmt === $birth_date_fmt) {
                                    continue;
                                }
                            }

                            if (!empty($kid_id_param) && $kid_id_param != $child_id) {
                                continue;
                            }

                            if (!empty($kid_name_param) && strpos(preg_replace('/\s+/', '', $child_name), preg_replace('/\s+/', '', $kid_name_param)) === false) {
                                continue;
                            }

                            if (!empty($product_param) && $product_param != $product_id) {
                                continue;
                            }
                            $combined_value = "{$order_item_custom_field}: {$order_item_custom_field_value}";

                            if (
                                !empty($product_field_param) &&
                                $product_field_param !== 'all' &&
                                $product_field_param !== $combined_value
                            ) {
                                continue;
                            }

                            if (!empty($group_param)) {
                                if ($group_param === "no-group") {
                                    if ($groups !== "no-group") {
                                        continue;
                                    }
                                } else {
                                    $group_param_name = get_the_title((int) $group_param);

                                    if (!empty($groups)) {
                                        $group_list = array_map('trim', explode(',', $groups));

                                        if (!in_array($group_param_name, $group_list, true)) {
                                            continue;
                                        }
                                    } else {
                                        continue;
                                    }
                                }
                            }
                            if (in_array($order_item_custom_field, $excluded_keys, true)) {
                                continue;
                            }
                            /* Deactivated cause the list is not correct.
                            if (!in_array($order_item_custom_field, $include_only_keys, true)) {
                                continue;
                            }
                            */
                            if ($exclude_orders_not_finalized && !in_array($order_status, $finalized_statuses = ['completed', 'processing'], true)) {
                                continue;
                            }

                            if (!empty($include_only_order_status) && $order_status !== $include_only_order_status) {
                                continue;
                            }
                            $count_items = $count_items + 1;
                            $should_render = !$use_pagination || ($count_items > ($items_per_page * ($current_page - 1)) && $count_items <= ($items_per_page * $current_page));
                            ?>
                            <?php if ($should_render): ?>
                                <tr order-data-tabel-id="<?php echo $order_id; ?>" order-data-product-name="<?php echo $product_name; ?>"
                                    <?php echo ($groups == "no-group") ? 'group="false"' : 'group="true"'; ?>>
                                    <td column="Order ID">
                                        <?php
                                        $order_id = $order->get_id() ?? null;
                                        $order_url = get_permalink($order_id);
                                        $order_link = '<a class="link-dm" href="' . $order_url . '">#' . esc_html($order_id) . '</a>';
                                        echo $order_link;

                                        $order_status = $order->get_status();
                                        $status_color = '#c19718';

                                        if (in_array($order_status, ['completed', 'processing'])) {
                                            $status_color = '#28a745';
                                        } elseif (in_array($order_status, ['cancelled', 'failed'])) {
                                            $status_color = '#dc3545';
                                        }
                                        ?>
                                        <span class="order-status" style="color: <?php echo esc_attr($status_color); ?>">
                                            <?php echo esc_html($order_status); ?>
                                        </span>
                                    </td>
                                    <td column="Order Details" class="row_order_details">
                                        <span><?php echo $order_date; ?></span>
                                        <?php
                                        $order_details = '<span>
                                        <button type="button" class="btn-dm show-more-btn" onclick="toggleDetails(this)">+</button>
                                        <div class="show-more-details" style="display: none;">
                                            <div class="popup-style order-details-box">
                                                <p><span class="field-label"> לקוח: </span>
                                                <span class="field-value">';
                                        $customer_id = $order->get_customer_id();
                                        if ($customer_id) {
                                            $user = get_user_by('id', $customer_id);
                                            $order_details .= $user ? esc_html($user->display_name) : 'Guest';
                                        } else {
                                            $order_details .= 'Guest';
                                        }
                                        $order_details .= '</span></p>

                                    <p><span class="field-label">סטטוס:</span><span class="field-value">' . wc_get_order_status_name($order->get_status()) . '</span></p>

                                    <p>
                                        <span class="field-label" style="display:none;"> קישור להזמנה: </span>
                                        <span class="field-value" style="display:none;">' . esc_url($order->get_edit_order_url()) . '</span>
                                        <a class="link-dm view-order" href="' . esc_url($order->get_edit_order_url()) . '">צפייה בהזמנה</a>
                                    </p>
                                            </div>
                                        </div>
                                    </span>';

                                        echo $order_details;
                                        ?>

                                    </td>
                                    <td column="Product" class="row_product">
                                        <?php
                                        $product_details = '
                                    <a href="' . esc_url(get_permalink($product_id)) . '" class="link-dm">
                                        ' . esc_html($product_name) . '
                                    </a>';

                                        echo $product_details;
                                        ?>
                                    </td>
                                    <td column="Product Field" class="row_product_parameter">
                                        <?php echo '<div style="display: flex; gap: 4px;">';
                                        echo '<span style="font-weight: 500;">' . $order_item_custom_field . '</span>';
                                        echo '<span>' . $order_item_custom_field_value . '</span>';
                                        echo '</div>'; ?>
                                    </td>
                                    <td column="Child's Name" class="row_kid_name">
                                        <?php
                                        echo "<span>" . $child_url ?? 'N/A' . "</span>";
                                        $child_details = '
                                    <span>
                                        <button type="button" class="btn-dm show-more-btn" onclick="toggleDetails(this)">+</button>

                                        <div class="show-more-details" style="display: none;"><div class="popup-style child-details-box">
                                    ';
                                        $child_details .= '<p><span class="field-label">ת.ז. הילד: </span><span class="field-value">' . ($post_child_url ? esc_html($post_child_url) : 'N/A') . '</span></p>';

                                        if ($post_child_url) {
                                            $author_id = get_post_field('post_author', $post_child_url);
                                            $author_name = $author_id ? esc_html(get_the_author_meta('display_name', $author_id)) : 'Unknown';
                                            $child_details .= '<p><span class="field-label">לקוח: </span><span class="field-value">' . $author_name . '</span></p>';
                                            ob_start();
                                            customFieldsOfPost($post_child_url);
                                            $child_details .= ob_get_clean();
                                        }
                                        $child_details .= '<p>
                                        <span class="field-label" style="display:none;">קישור ילד:  </span>
                                        <span class="field-value" style="display:none;">' . getChildUrl($child_id) . '</span>
                                        </p>';
                                        $child_details .= '
                                            </div>
                                        </div>
                                    </span>
                                    ';
                                        echo $child_details;
                                        ?>
                                    </td>
                                    <td column="Child ID" class="row_kid_id">
                                        <p>
                                            <?php echo $child_id ? $child_id : 'N/A'; ?>
                                        </p>
                                    </td>
                                    <td column="Child Age" class="row_kid_age">
                                        <?php renderAgeFieldsOfPost($post_child_url); ?>
                                    </td>
                                    <td column="Child Gender" class="row_kid_gender">
                                        <?php renderGenderFieldsOfPost($post_child_url); ?>
                                    </td>
                                    <td column="Child School" class="row_kid_school">
                                        <p>
                                            <?php echo $child_school ? $child_school : 'N/A'; ?>
                                        </p>
                                    </td>
                                    <td column="Product Parameters" class="row_product_parameters">
                                        <?php
                                        if (!empty($order_item_custom_fields) && is_array($order_item_custom_fields)) {
                                            foreach ($order_item_custom_fields as $field => $key_field) {
                                                if (!in_array($field, $excluded_keys, true)) {
                                                    $field_value = cleanMetaValue(getValueOfCustomFieldFromProductOrder($field, $item));
                                                    echo "{$field} {$field_value} <br>";
                                                }
                                            }
                                        }
                                        ?>
                                    </td>
                                    <td column="Group" class="row_groups group-td">
                                        <span>
                                            <?php echo groupsLinks($groups); ?>
                                        </span>

                                        <span>
                                            <button type="button" class="btn-dm show-more-btn" onclick="toggleDetails(this)">+</button>
                                            <div class="show-more-details" style="display: none;">
                                                <div class="popup-style group-box">
                                                    <div class="group-checkboxes">
                                                        <div>
                                                            <?php echo get_all_groups_checkboxes(); ?>
                                                        </div>
                                                        <button type="button" class="btn-dm" name="manage-groups"
                                                            onclick="updatePostGroups(this)" kid_id="<?php echo esc_attr($child_id); ?>"
                                                            kid_name="<?php echo esc_attr($child_name); ?>"
                                                            order_id="<?php echo esc_attr($order_id); ?>"
                                                            order_date="<?php echo esc_attr($order_date); ?>"
                                                            order_item_custom_field="<?php echo esc_attr($order_item_custom_field); ?>">
                                                            להחיל
                                                        </button>

                                                        <?php $dataJson = json_encode([
                                                            'kid_id' => $child_id,
                                                            'kid_name' => $child_name,
                                                            'kid_school' => $child_school,
                                                            'kid_details' => $child_details,
                                                            'order_id' => $order_id,
                                                            'order_date' => $order_date,
                                                            'order_details' => $order_details,
                                                            'product_details' => $product_details,
                                                            'product_field' => $order_item_custom_field,
                                                            'product_field_value' => $order_item_custom_field_value
                                                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>

                                                        <!-- JSON Data Storage -->
                                                        <script type="application/json"
                                                            id="kid-data-<?php echo esc_attr($child_id . '-' . $order_id . '-' . $order_item_custom_field); ?>"><?php echo $dataJson; ?> </script>
                                                    </div>
                                                </div>
                                            </div>
                                        </span>
                                    </td>
                                    <td column="Updates" class="row_update">
                                        <input type="checkbox" value="update"
                                            json-id="kid-data-<?php echo esc_attr($child_id . '-' . $order_id . '-' . $order_item_custom_field); ?>">
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>

                    <?php endforeach;
                endforeach; ?>
            </tbody>
        </table>
        <?php if ($use_pagination): ?>
            <?php
            $total_pages = (int) ceil(max(1, $count_items) / $items_per_page);
            if ($total_pages < 1) {
                $total_pages = 1;
            }
            $query_params = $_GET;
            $query_params['use_pagination'] = '1';
            $build_link = function ($page) use ($query_params) {
                $query_params['pg'] = (int) $page;
                unset($query_params['page']);
                return add_query_arg($query_params);
            };
            $pages_to_show = [];
            $pages_to_show[] = 1;
            $pages_to_show[] = max(1, $current_page - 1);
            $pages_to_show[] = $current_page;
            $pages_to_show[] = min($total_pages, $current_page + 1);
            $pages_to_show[] = $total_pages;
            $pages_to_show = array_values(array_unique(array_filter($pages_to_show, function ($p) use ($total_pages) {
                return $p >= 1 && $p <= $total_pages;
            })));
            sort($pages_to_show);
            ?>
            <div class="dm-flex" style="justify-content:center; margin: 12px 0; gap:8px;">
                <?php if ($current_page > 1): ?>
                    <a class="btn-dm" href="<?php echo esc_url($build_link($current_page - 1)); ?>">&lt;</a>
                <?php else: ?>
                    <span class="btn-dm" style="opacity:0.5; pointer-events:none;">&lt;</span>
                <?php endif; ?>
                <?php
                $last = 0;
                foreach ($pages_to_show as $p) {
                    if ($last && $p > $last + 1) {
                        echo '<span style="padding:4px 6px">...</span>';
                    }
                    if ($p == $current_page) {
                        echo '<span class="btn-dm" style="background:#0073aa;color:#fff">' . (int) $p . '</span>';
                    } else {
                        echo '<a class="btn-dm" href="' . esc_url($build_link($p)) . '">' . (int) $p . '</a>';
                    }
                    $last = $p;
                }
                ?>
                <?php if ($current_page < $total_pages): ?>
                    <a class="btn-dm" href="<?php echo esc_url($build_link($current_page + 1)); ?>">&gt;</a>
                <?php else: ?>
                    <span class="btn-dm" style="opacity:0.5; pointer-events:none;">&gt;</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="dm-flex dm-bulk-section">

            <label class="dm-label">
                <span>סה"כ</span>
                <div class="count-items"><?php echo $count_items; ?></div>
            </label>
            <label class="dm-label">
                <span>עדכון קבוצתי בכמות גדולה</span>
                <select id="group-update-bulk" name="group-update-bulk" style="width:200px;">
                    <?php echo get_all_groups_options('no-group', array('nogroup' => true, 'allgroups' => false)); ?>
                </select>
            </label>
            <button class="btn-primary-dm btn-requests-dm" onclick="updateTable('products-from-orders table')">עדכון
                טבלה
            </button>
        </div>
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