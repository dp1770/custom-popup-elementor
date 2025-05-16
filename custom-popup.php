<?php
/**
 * Plugin Name: Custom Popup - Dynamic Fields
 * Plugin URI: https://profiles.wordpress.org/dhaval1770/
 * Description: Theme options with dynamic Heading + Form Shortcode + Popup Trigger repeater. 
 * Version: 1.0
 * Author: Dhaval Pansuriya
 * Author URI: https://profiles.wordpress.org/dhaval1770/
 */

if (!defined('ABSPATH')) exit;


function dp_register_settings() {
    register_setting('dp_options_group', 'dp_options');
}
add_action('admin_init', 'dp_register_settings');

function dp_get_field_definitions() {
    return [
        'popup_trigger' => 'Popup Trigger Class',
        'heading' => 'Popup Heading',
        'dp_shortcode' => 'Shortcode',
        'dp_description' => 'Description',
    ];
}

function dp_plugin_action_links($links) {
    $settings_link = '<a href="admin.php?page=dp-custom-popup">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'dp_plugin_action_links');

add_action('admin_menu', 'dp_plugin_add_admin_menu');
function dp_plugin_add_admin_menu() {
    add_menu_page(
        __('Custom Popup Options', 'dp-plugin'),  // Page title
        __('Custom Popup', 'dp-plugin'),         // Menu title
        'manage_options',                       // Capability
        'dp-custom-popup',                     // Menu slug (URL)
        'dp_custom_popup_page',               // Callback function to display the page
        'dashicons-welcome-widgets-menus',      // Dashicon icon
        25                                      // Position in the sidebar
    );
}

function dp_custom_popup_page() {
    $options = get_option('dp_options');
    $items = $options['items'] ?? [];
    $fields = dp_get_field_definitions();
    ?>
    <div class="wrap">
        <h1>Custom Popup Options</h1>
        <form method="post" action="options.php">
            <?php settings_fields('dp_options_group'); ?>

            <div id="dp-repeater">
                <?php if (!empty($items)) : foreach ($items as $index => $item) : ?>
                    <div class="dp-item" draggable="true">
                        <div class="dp-header">
                            <strong>Popup <?php echo $index + 1; ?></strong>
                            <button type="button" class="button dp-toggle">Toggle</button>
                            <button type="button" class="button dp-remove">Remove</button>
                        </div>
                        <div class="dp-fields">
                            <?php foreach ($fields as $field_key => $field_label) : ?>
                                <label><?php echo esc_html($field_label); ?></label>
                                <input type="text" name="dp_options[items][<?php echo $index; ?>][<?php echo $field_key; ?>]"
                                    value="<?php echo esc_attr($item[$field_key] ?? ''); ?>" class="regular-text" required/>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>

            <button type="button" class="button button-primary" id="dp-add">+ Add New</button>

            <?php submit_button(); ?>
        </form>
    </div>

    <style>
        .dp-item { border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; background: #f9f9f9; cursor: grab; }
        .dp-item.dragging { opacity: 0.5; }
        .dp-header { display: flex; justify-content: space-between; align-items: center; }
        .dp-fields { margin-top: 10px; }
        .dp-fields.collapsed { display: none; }
        .dp-fields label { display: block; margin-top: 8px; font-weight: 600; }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const repeater = document.getElementById('dp-repeater');
        const addBtn = document.getElementById('dp-add');
        const fields = <?php echo json_encode($fields); ?>;

        function updateLabels() {
            repeater.querySelectorAll('.dp-item').forEach((item, i) => {
                item.querySelector('.dp-header strong').textContent = 'Popup ' + (i + 1);
                item.querySelectorAll('input').forEach(input => {
                    input.name = input.name.replace(/items\[\d+\]/, 'items[' + i + ']');
                });
            });
        }

        addBtn.addEventListener('click', function() {
            const index = repeater.children.length;
            const div = document.createElement('div');
            div.classList.add('dp-item');
            div.setAttribute('draggable', 'true');

            let html = '<div class="dp-header"><strong>Popup ' + (index + 1) + '</strong>' +
                       '<button type="button" class="button dp-toggle">Toggle</button>' +
                       '<button type="button" class="button dp-remove">Remove</button></div>' +
                       '<div class="dp-fields">';
            for (const key in fields) {
                html += `<label>${fields[key]}</label>`;
                html += `<input type="text" name="dp_options[items][${index}][${key}]" class="regular-text" />`;
            }
            html += '</div>';

            div.innerHTML = html;
            repeater.appendChild(div);
        });

        repeater.addEventListener('click', function(e) {
            if (e.target.classList.contains('dp-remove')) {
                e.target.closest('.dp-item').remove();
                updateLabels();
            }
            if (e.target.classList.contains('dp-toggle')) {
                const fieldsDiv = e.target.closest('.dp-item').querySeledpr('.dp-fields');
                fieldsDiv.classList.toggle('collapsed');
            }
        });

        // Drag & Drop Logic
        let dragItem = null;
        repeater.addEventListener('dragstart', function(e) {
            if (e.target.classList.contains('dp-item')) {
                dragItem = e.target;
                e.target.classList.add('dragging');
            }
        });
        repeater.addEventListener('dragover', function(e) {
            e.preventDefault();
            const afterElement = getDragAfterElement(repeater, e.clientY);
            if (afterElement == null) {
                repeater.appendChild(dragItem);
            } else {
                repeater.insertBefore(dragItem, afterElement);
            }
        });
        repeater.addEventListener('dragend', function() {
            if (dragItem) {
                dragItem.classList.remove('dragging');
                updateLabels();
                dragItem = null;
            }
        });

        function getDragAfterElement(container, y) {
            const draggableElements = [...container.querySeledprAll('.dp-item:not(.dragging)')];
            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        }
    });
    </script>
    <?php
}

add_action( 'wp_footer', 'custom_popup_function' );
function custom_popup_function(){ 
    $options = get_option('dp_options');
    if (!empty($options['items'])) :
        /* Custom Popup */
        foreach ($options['items'] as $item) :
            echo '<!--'.esc_html($item['dp_description'] ?? '').'-->';
            echo '<div class="gf-lightbox ' . esc_html($item['popup_trigger'] ?? '') . '"><div class="gf-overlay"></div>';
            echo '<div class="gf-content"><span class="gf-close">&times;</span>';
            echo '<div class="popup-heading">' . esc_html($item['heading'] ?? '') . '</div>';
            echo do_shortcode($item['dp_shortcode'] ?? '');
            echo ' </div></div>';
        endforeach;
        ?>
        <style>
            .gf-lightbox {display: none;position: fixed;z-index: 9999;top: 0;left: 0;width: 100%; height: 100%;background: rgba(0, 0, 0, 0.6);padding-top: 20vh;padding-inline: 20px;overflow:auto;}
            .gf-lightbox.active {display: block;}
            .gf-lightbox .popup-heading{font-size: 2.6rem;text-align: center;font-weight: bold;font-family: var( --e-global-typography-primary-font-family ), sans-serif;line-height: 2.8rem;margin-bottom: 20px;}
            .gf-overlay {position: absolute;top: 0; left: 0;width: 100%; height: 100%;z-index: 1;}
            .gf-content {position: relative;z-index: 2;background: #fff;max-width: 600px;width: auto;margin: 0 auto;padding: 30px;border-radius: 10px;}
            .gf-close {cursor:pointer;position: absolute;top: 10px;right: 10px;color: #000000;text-decoration: none;font-size: 40px;}
            .gf-lightbox .gform-footer.gform_footer{padding:0px!important;}
        </style>
        <script>
                document.addEventListener('DOMContentLoaded', function () {
                // Open Popup
                document.querySelectorAll('[class*="custom-popup-"]').forEach(trigger => {
                    trigger.addEventListener('click', function () {
                    this.classList.forEach(cls => {
                        if (cls.startsWith('custom-popup-')) {
                        const popup = document.querySelector('.gf-lightbox.' + cls);
                        if (popup) popup.classList.add('active');
                        }
                    });
                    });
                });

                // Close Popup when clicking overlay or close button (Event Delegation)
                document.addEventListener('click', function (e) {
                    // Overlay click
                    if (e.target.classList.contains('gf-overlay')) {
                    const popup = e.target.closest('.gf-lightbox');
                    popup?.classList.remove('active');
                    }

                    // Close button click
                    if (e.target.classList.contains('gf-close')) {
                    const popup = e.target.closest('.gf-lightbox');
                    popup?.classList.remove('active');
                    }
                });

                // Close Popup on ESC key
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                    document.querySelectorAll('.gf-lightbox.active').forEach(popup => {
                        popup.classList.remove('active');
                    });
                    }
                });
                });
           </script>
       <?php
    endif;
};
