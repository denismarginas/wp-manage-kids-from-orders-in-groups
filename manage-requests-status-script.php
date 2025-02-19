<?php 
function dm_camp_group_changes_table() {
    if (!current_user_can('manage_woocommerce') && !current_user_can('editor') && !current_user_can('administrator')) {
        return '<p class="dm-error-section">אין לך הרשאה לצפות בתוכן.</p>';
    }

    $kid_id_param = isset($_GET['kid_id']) ? $_GET['kid_id'] : null;

    $args = [
        'post_type'      => 'camp-group-changes',
        'posts_per_page' => -1,
        'post_status'    => 'publish'
    ];

    $query = new WP_Query($args);
    if (!$query->have_posts()) {
        return '<p>No records found.</p>';
    }

    ob_start();
    ?>
    <div class="dm-title-section">
        <h2 class="dm-title">בקשות קבוצות קמפ</h2>
    </div>
    <div id="dm-filters" class="woocommerce-orders-filters">
        <div>
            <label class="dm-label">
                    <span>ת.ז ילד</span>
                    <input type="number" id="kid-id-filter" name="kid_id" placeholder="123456789" value="<?php echo $kid_id_param; ?>">
            </label>
        </div>
        <div class="dm-flex">
            <button class="btn-primary-dm btn-filters-dm" onclick="applyFilters()">החל מסננים &#128269</button>
            <button class="btn-primary-dm" onclick="resetFilters()">לאפס מסננים</button>
        </div>
    </div>
    <table id="camp-group-changes-table" class="dm-table" border="1">
        <thead>
            <tr>
                <th>שם לקוח</th> <!-- Client Name -->
                <th>מספר הזמנה</th> <!-- Order ID -->
                <th>שם הילד</th> <!-- Kid Name -->
                <th>תעודת זהות של הילד</th> <!-- Kid ID -->
                <th>בקשה</th> <!-- Request -->
                <th>סטטוס</th> <!-- Status -->
                <th>עדכן</th> <!-- Update -->
            </tr>
        </thead>
        <tbody>
            <?php while ($query->have_posts()) : $query->the_post(); 
                $post_id = get_the_ID();
                $client_name = get_post_meta($post_id, 'client_name', true);
                $order_id = get_post_meta($post_id, 'order_id', true);
                $kid_name = get_post_meta($post_id, 'kid_name', true);
                $kid_id = get_post_meta($post_id, 'kid_id', true);
                $request = get_post_meta($post_id, 'request', true);
                $status = get_post_meta($post_id, 'status', true);

                if (!empty($kid_id_param) && $kid_id_param != $kid_id) {
                    continue;
                }
            ?>
                <tr data-post-id="<?php echo $post_id; ?>">
                    <td><?php echo esc_html($client_name); ?></td>
                    <td><?php echo esc_html($order_id); ?></td>
                    <td><?php echo esc_html($kid_name); ?></td>
                    <td><?php echo esc_html($kid_id); ?></td>
                    <td><?php echo esc_html($request); ?></td>
                    <td class="status">
                        <select class="status-select">
                            <?php
                            $status_choices = get_field_object('status')['choices'] ?? ['To do', 'Done'];
                            foreach ($status_choices as $key => $label) {
                                $selected = ($status === $key) ? 'selected' : '';
                                echo "<option value='" . esc_attr($key) . "' $selected>" . esc_html($label) . "</option>";
                            }
                            ?>
                        </select>
                    </td>

                    <td>
                        <button class="btn-primary-dm update-status" data-post-id="<?php echo $post_id; ?>" onclick="updateStatus()">Update Status</button>
                    </td>
                </tr>
            <?php endwhile; wp_reset_postdata(); ?>
        </tbody>
    </table>

    <script>
        function updateStatus() {
            document.querySelectorAll('.update-status').forEach(button => {
                button.addEventListener('click', function () {
                    let row = this.closest('tr');
                    let postId = row.dataset.postId;
                    let newStatus = row.querySelector('.status-select').value;

                    fetch(ajaxurl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'update_camp_group_status',
                            post_id: postId,
                            status: newStatus,
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Status updated successfully!');
                        } else {
                            alert('Error updating status.');
                        }
                    })
                    .catch(error => console.error('Error:', error));
                });
            });
        }
        updateStatus();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('camp_group_changes_table', 'dm_camp_group_changes_table');


add_action('wp_ajax_update_camp_group_status', 'update_camp_group_status');

function update_camp_group_status() {
    if (!isset($_POST['post_id'], $_POST['status'])) {
        wp_send_json_error('Missing data');
    }

    $post_id = intval($_POST['post_id']);
    $status = sanitize_text_field($_POST['status']);

    if (update_field('status', $status, $post_id)) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Update failed');
    }
}

