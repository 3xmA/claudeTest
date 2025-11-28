<?php
/**
 * Integrazione core con il plugin principale
 */

if (!defined('ABSPATH')) {
    exit;
}

class Loyalty_Woo_Integration {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Hook per aggiungere prodotti WooCommerce alla lista premi
        add_filter('loyalty_available_rewards', array($this, 'add_woo_products_to_rewards'), 10, 2);

        // Hook per gestire riscatto prodotto WooCommerce
        add_action('loyalty_redeem_reward', array($this, 'handle_woo_product_redeem'), 10, 3);

        // Shortcode per mostrare premi WooCommerce
        add_shortcode('loyalty_woo_rewards', array($this, 'render_woo_rewards'));
    }

    /**
     * Aggiungi prodotti WooCommerce alla lista premi disponibili
     */
    public function add_woo_products_to_rewards($rewards, $user_id = null) {
        // Query prodotti marcati come premi fedeltà
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_loyalty_reward_enabled',
                    'value' => '1',
                    'compare' => '='
                )
            ),
            'orderby' => 'meta_value_num',
            'meta_key' => '_loyalty_points_required',
            'order' => 'ASC'
        );

        $woo_products = get_posts($args);

        // Converti prodotti WooCommerce in formato premio
        foreach ($woo_products as $product_post) {
            $product = wc_get_product($product_post->ID);

            if (!$product) {
                continue;
            }

            $points_required = get_post_meta($product_post->ID, '_loyalty_points_required', true);
            $discount_type = get_post_meta($product_post->ID, '_loyalty_discount_type', true);
            $discount_value = get_post_meta($product_post->ID, '_loyalty_discount_value', true);

            // Descrizione del premio
            $description = '';
            if ($discount_type === 'free') {
                $description = 'Prodotto GRATUITO';
            } elseif ($discount_type === 'percentage') {
                $description = sprintf('%d%% di sconto su questo prodotto', $discount_value);
            } else {
                $description = sprintf('€%s di sconto su questo prodotto', number_format($discount_value, 2));
            }

            // Aggiungi alla lista premi
            $rewards[] = (object) array(
                'id' => 'woo_' . $product_post->ID, // Prefisso per distinguere
                'name' => $product->get_name(),
                'description' => $description,
                'points_required' => intval($points_required),
                'reward_type' => 'woo_product',
                'reward_value' => $product_post->ID,
                'is_active' => 1,
                'image_url' => get_the_post_thumbnail_url($product_post->ID, 'medium'),
                'product_url' => get_permalink($product_post->ID),
            );
        }

        return $rewards;
    }

    /**
     * Gestisce il riscatto di un prodotto WooCommerce
     */
    public function handle_woo_product_redeem($reward_id, $user_id, $points_used) {
        // Verifica se è un premio WooCommerce
        if (strpos($reward_id, 'woo_') !== 0) {
            return; // Non è un premio WooCommerce
        }

        // Estrai product ID
        $product_id = intval(str_replace('woo_', '', $reward_id));

        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }

        // Ottieni configurazione
        $discount_type = get_post_meta($product_id, '_loyalty_discount_type', true);
        $discount_value = get_post_meta($product_id, '_loyalty_discount_value', true);

        // Genera coupon
        $coupon_generator = Loyalty_Woo_Coupon_Generator::get_instance();
        $reward_object = (object) array(
            'id' => $product_id,
            'name' => $product->get_name(),
        );

        // Crea redemption nel database
        global $wpdb;
        $table = $wpdb->prefix . 'loyalty_redemptions';

        $wpdb->insert($table, array(
            'user_id' => $user_id,
            'reward_id' => $product_id,
            'points_used' => $points_used,
            'redemption_code' => '', // Verrà popolato dal coupon generator
            'status' => 'active',
        ));

        $redemption_id = $wpdb->insert_id;

        // Trigger azione per generazione coupon
        do_action('loyalty_reward_redeemed', $redemption_id, $user_id, $reward_object);

        error_log(sprintf(
            'Loyalty WooCommerce: Premio prodotto #%d riscattato da utente #%d',
            $product_id,
            $user_id
        ));
    }

    /**
     * Shortcode per mostrare solo premi WooCommerce
     */
    public function render_woo_rewards($atts) {
        if (!is_user_logged_in()) {
            return '<p>Devi effettuare il login per visualizzare i premi.</p>';
        }

        $user_id = get_current_user_id();

        // Ottieni solo prodotti WooCommerce
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_loyalty_reward_enabled',
                    'value' => '1',
                    'compare' => '='
                )
            ),
            'orderby' => 'meta_value_num',
            'meta_key' => '_loyalty_points_required',
            'order' => 'ASC'
        );

        $woo_products = get_posts($args);

        if (empty($woo_products)) {
            return '<p>Nessun prodotto disponibile come premio al momento.</p>';
        }

        // Ottieni saldo utente
        global $wpdb;
        $table = $wpdb->prefix . 'loyalty_transactions';
        $balance = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(points) FROM $table WHERE user_id = %d",
            $user_id
        ));
        $balance = intval($balance);

        ob_start();
        ?>
        <div class="loyalty-woo-rewards">
            <style>
            .loyalty-woo-rewards {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 20px;
                margin: 20px 0;
            }
            .loyalty-reward-card {
                background: white;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                transition: transform 0.2s;
            }
            .loyalty-reward-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 4px 16px rgba(0,0,0,0.15);
            }
            .loyalty-reward-image {
                width: 100%;
                height: 200px;
                object-fit: cover;
                background: #f3f4f6;
            }
            .loyalty-reward-content {
                padding: 20px;
            }
            .loyalty-reward-name {
                font-size: 18px;
                font-weight: 600;
                margin: 0 0 10px 0;
                color: #1f2937;
            }
            .loyalty-reward-desc {
                font-size: 14px;
                color: #6b7280;
                margin: 0 0 15px 0;
            }
            .loyalty-reward-points {
                font-size: 24px;
                font-weight: 700;
                color: #667eea;
                margin: 10px 0;
            }
            .loyalty-reward-button {
                width: 100%;
                padding: 12px;
                background: #10b981;
                color: white;
                border: none;
                border-radius: 8px;
                font-weight: 600;
                cursor: pointer;
                transition: background 0.2s;
            }
            .loyalty-reward-button:hover {
                background: #059669;
            }
            .loyalty-reward-button:disabled {
                background: #9ca3af;
                cursor: not-allowed;
            }
            </style>

            <?php foreach ($woo_products as $product_post) : ?>
                <?php
                $product = wc_get_product($product_post->ID);
                if (!$product) continue;

                $points_required = get_post_meta($product_post->ID, '_loyalty_points_required', true);
                $discount_type = get_post_meta($product_post->ID, '_loyalty_discount_type', true);
                $discount_value = get_post_meta($product_post->ID, '_loyalty_discount_value', true);

                $can_redeem = $balance >= $points_required;

                $description = '';
                if ($discount_type === 'free') {
                    $description = 'Prodotto GRATUITO';
                } elseif ($discount_type === 'percentage') {
                    $description = sprintf('%d%% di sconto', $discount_value);
                } else {
                    $description = sprintf('€%s di sconto', number_format($discount_value, 2));
                }
                ?>

                <div class="loyalty-reward-card">
                    <?php if (has_post_thumbnail($product_post->ID)) : ?>
                        <img src="<?php echo get_the_post_thumbnail_url($product_post->ID, 'medium'); ?>" alt="<?php echo esc_attr($product->get_name()); ?>" class="loyalty-reward-image">
                    <?php else : ?>
                        <div class="loyalty-reward-image"></div>
                    <?php endif; ?>

                    <div class="loyalty-reward-content">
                        <h3 class="loyalty-reward-name"><?php echo esc_html($product->get_name()); ?></h3>
                        <p class="loyalty-reward-desc"><?php echo esc_html($description); ?></p>
                        <div class="loyalty-reward-points">
                            ⭐ <?php echo esc_html($points_required); ?> punti
                        </div>

                        <?php if ($can_redeem) : ?>
                            <button class="loyalty-reward-button" onclick="location.href='<?php echo get_permalink($product_post->ID); ?>'">
                                Riscatta Ora
                            </button>
                        <?php else : ?>
                            <button class="loyalty-reward-button" disabled>
                                Punti insufficienti (hai <?php echo $balance; ?>)
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
