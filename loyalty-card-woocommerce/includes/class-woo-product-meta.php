<?php
/**
 * Gestione meta box prodotti WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class Loyalty_Woo_Product_Meta {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Aggiungi meta box ai prodotti
        add_action('add_meta_boxes', array($this, 'add_loyalty_meta_box'));

        // Salva meta box
        add_action('save_post_product', array($this, 'save_loyalty_meta'), 10, 2);

        // Aggiungi colonna nella lista prodotti
        add_filter('manage_product_posts_columns', array($this, 'add_loyalty_column'));
        add_action('manage_product_posts_custom_column', array($this, 'display_loyalty_column'), 10, 2);
    }

    /**
     * Aggiungi meta box al prodotto
     */
    public function add_loyalty_meta_box() {
        add_meta_box(
            'loyalty_product_reward',
            '🎁 Carta Fedeltà - Premio',
            array($this, 'render_meta_box'),
            'product',
            'side',
            'default'
        );
    }

    /**
     * Render del meta box
     */
    public function render_meta_box($post) {
        // Nonce per sicurezza
        wp_nonce_field('loyalty_product_meta', 'loyalty_product_nonce');

        // Recupera valori salvati
        $enabled = get_post_meta($post->ID, '_loyalty_reward_enabled', true);
        $points_required = get_post_meta($post->ID, '_loyalty_points_required', true);
        $discount_type = get_post_meta($post->ID, '_loyalty_discount_type', true);
        $discount_value = get_post_meta($post->ID, '_loyalty_discount_value', true);

        // Default values
        if (!$discount_type) $discount_type = 'free';
        if (!$points_required) $points_required = 100;
        if (!$discount_value) $discount_value = 100;

        ?>
        <div class="loyalty-meta-box" style="padding: 10px 0;">

            <!-- Abilita come premio -->
            <p style="margin-bottom: 15px;">
                <label>
                    <input type="checkbox" name="loyalty_reward_enabled" value="1" <?php checked($enabled, '1'); ?>>
                    <strong>Disponibile come premio fedeltà</strong>
                </label>
            </p>

            <div id="loyalty-reward-settings" style="<?php echo $enabled ? '' : 'display: none;'; ?>">

                <!-- Punti richiesti -->
                <p style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">
                        ⭐ Punti Richiesti
                    </label>
                    <input type="number" name="loyalty_points_required" value="<?php echo esc_attr($points_required); ?>" min="1" step="1" class="widefat" style="width: 100%;">
                    <small style="color: #6b7280; display: block; margin-top: 3px;">
                        Quanti punti servono per riscattare questo premio
                    </small>
                </p>

                <!-- Tipo sconto -->
                <p style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">
                        🎯 Tipo Sconto
                    </label>
                    <select name="loyalty_discount_type" id="loyalty_discount_type" class="widefat" style="width: 100%;">
                        <option value="free" <?php selected($discount_type, 'free'); ?>>Prodotto Gratuito (100%)</option>
                        <option value="percentage" <?php selected($discount_type, 'percentage'); ?>>Sconto Percentuale</option>
                        <option value="fixed" <?php selected($discount_type, 'fixed'); ?>>Sconto Fisso (€)</option>
                    </select>
                </p>

                <!-- Valore sconto -->
                <p id="loyalty_discount_value_wrap" style="margin-bottom: 15px; <?php echo ($discount_type === 'free') ? 'display:none;' : ''; ?>">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">
                        💰 Valore Sconto
                    </label>
                    <input type="number" name="loyalty_discount_value" id="loyalty_discount_value" value="<?php echo esc_attr($discount_value); ?>" min="1" step="any" class="widefat" style="width: 100%;">
                    <small id="loyalty_discount_hint" style="color: #6b7280; display: block; margin-top: 3px;">
                        <?php
                        if ($discount_type === 'percentage') {
                            echo 'Percentuale di sconto (es: 20 per 20%)';
                        } elseif ($discount_type === 'fixed') {
                            echo 'Importo fisso di sconto in euro';
                        }
                        ?>
                    </small>
                </p>

                <!-- Anteprima -->
                <div style="background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 15px;">
                    <strong style="display: block; margin-bottom: 8px; color: #1f2937;">📋 Anteprima:</strong>
                    <p id="loyalty_preview" style="margin: 0; font-size: 13px; color: #4b5563;">
                        <!-- Riempito via JS -->
                    </p>
                </div>

            </div>

        </div>

        <script>
        jQuery(document).ready(function($) {
            // Toggle settings visibility
            $('input[name="loyalty_reward_enabled"]').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#loyalty-reward-settings').slideDown();
                } else {
                    $('#loyalty-reward-settings').slideUp();
                }
            });

            // Gestisci cambio tipo sconto
            $('#loyalty_discount_type').on('change', function() {
                const type = $(this).val();

                if (type === 'free') {
                    $('#loyalty_discount_value_wrap').hide();
                    $('#loyalty_discount_value').val(100);
                } else {
                    $('#loyalty_discount_value_wrap').show();
                    if (type === 'percentage') {
                        $('#loyalty_discount_hint').text('Percentuale di sconto (es: 20 per 20%)');
                        $('#loyalty_discount_value').attr('max', 100);
                    } else {
                        $('#loyalty_discount_hint').text('Importo fisso di sconto in euro');
                        $('#loyalty_discount_value').removeAttr('max');
                    }
                }

                updatePreview();
            });

            // Update preview quando cambiano i valori
            $('input[name="loyalty_points_required"], #loyalty_discount_type, #loyalty_discount_value').on('input change', updatePreview);

            function updatePreview() {
                const points = $('input[name="loyalty_points_required"]').val() || 100;
                const type = $('#loyalty_discount_type').val();
                const value = $('#loyalty_discount_value').val() || 0;

                let preview = `Cliente spende <strong>${points} punti</strong> e riceve: `;

                if (type === 'free') {
                    preview += '<strong>Prodotto GRATIS (100% sconto)</strong>';
                } else if (type === 'percentage') {
                    preview += `<strong>${value}% di sconto</strong> su questo prodotto`;
                } else {
                    preview += `<strong>€${value} di sconto</strong> su questo prodotto`;
                }

                $('#loyalty_preview').html(preview);
            }

            // Inizializza preview
            updatePreview();
        });
        </script>

        <style>
        .loyalty-meta-box input[type="number"],
        .loyalty-meta-box select {
            font-size: 14px;
            padding: 6px 8px;
        }
        </style>
        <?php
    }

    /**
     * Salva i meta dati
     */
    public function save_loyalty_meta($post_id, $post) {
        // Verifica nonce
        if (!isset($_POST['loyalty_product_nonce']) || !wp_verify_nonce($_POST['loyalty_product_nonce'], 'loyalty_product_meta')) {
            return;
        }

        // Verifica autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Verifica permessi
        if (!current_user_can('edit_product', $post_id)) {
            return;
        }

        // Salva enabled
        $enabled = isset($_POST['loyalty_reward_enabled']) ? '1' : '0';
        update_post_meta($post_id, '_loyalty_reward_enabled', $enabled);

        // Se disabilitato, rimuovi gli altri meta
        if ($enabled !== '1') {
            delete_post_meta($post_id, '_loyalty_points_required');
            delete_post_meta($post_id, '_loyalty_discount_type');
            delete_post_meta($post_id, '_loyalty_discount_value');
            return;
        }

        // Salva punti richiesti
        if (isset($_POST['loyalty_points_required'])) {
            $points = absint($_POST['loyalty_points_required']);
            update_post_meta($post_id, '_loyalty_points_required', $points);
        }

        // Salva tipo sconto
        if (isset($_POST['loyalty_discount_type'])) {
            $discount_type = sanitize_text_field($_POST['loyalty_discount_type']);
            update_post_meta($post_id, '_loyalty_discount_type', $discount_type);
        }

        // Salva valore sconto
        if (isset($_POST['loyalty_discount_value'])) {
            $discount_value = floatval($_POST['loyalty_discount_value']);
            update_post_meta($post_id, '_loyalty_discount_value', $discount_value);
        }
    }

    /**
     * Aggiungi colonna nella lista prodotti
     */
    public function add_loyalty_column($columns) {
        $new_columns = array();

        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;

            // Inserisci dopo il prezzo
            if ($key === 'price') {
                $new_columns['loyalty_reward'] = '🎁 Loyalty';
            }
        }

        return $new_columns;
    }

    /**
     * Mostra contenuto colonna
     */
    public function display_loyalty_column($column, $post_id) {
        if ($column !== 'loyalty_reward') {
            return;
        }

        $enabled = get_post_meta($post_id, '_loyalty_reward_enabled', true);

        if ($enabled === '1') {
            $points = get_post_meta($post_id, '_loyalty_points_required', true);
            $type = get_post_meta($post_id, '_loyalty_discount_type', true);
            $value = get_post_meta($post_id, '_loyalty_discount_value', true);

            echo '<span style="background: #10b981; color: white; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">✓ ' . $points . ' pt</span>';

            if ($type === 'free') {
                echo '<br><small style="color: #6b7280;">Gratuito</small>';
            } elseif ($type === 'percentage') {
                echo '<br><small style="color: #6b7280;">-' . $value . '%</small>';
            } else {
                echo '<br><small style="color: #6b7280;">-€' . $value . '</small>';
            }
        } else {
            echo '<span style="color: #9ca3af;">—</span>';
        }
    }
}
