<?php
/**
 * Pagina Admin Settings per WooCommerce Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Loyalty_Woo_Admin_Settings {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Aggiungi pagina al menu
        add_action('admin_menu', array($this, 'add_settings_page'));

        // Registra impostazioni
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Aggiungi pagina settings
     */
    public function add_settings_page() {
        add_submenu_page(
            'loyalty-card',
            'Integrazione WooCommerce',
            '🛒 WooCommerce',
            'manage_options',
            'loyalty-woocommerce',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Registra impostazioni
     */
    public function register_settings() {
        // Punti automatici
        register_setting('loyalty_woo_settings', 'loyalty_woo_auto_points_enabled');
        register_setting('loyalty_woo_settings', 'loyalty_woo_points_per_euro');
        register_setting('loyalty_woo_settings', 'loyalty_woo_min_amount');
        register_setting('loyalty_woo_settings', 'loyalty_woo_exclude_shipping');
        register_setting('loyalty_woo_settings', 'loyalty_woo_exclude_taxes');
        register_setting('loyalty_woo_settings', 'loyalty_woo_exclude_coupon_orders');

        // Coupon
        register_setting('loyalty_woo_settings', 'loyalty_woo_coupon_expiry_days');

        // Notifiche
        register_setting('loyalty_woo_settings', 'loyalty_woo_send_notifications');
    }

    /**
     * Render pagina settings
     */
    public function render_settings_page() {
        // Salva impostazioni
        if (isset($_POST['loyalty_woo_save_settings'])) {
            check_admin_referer('loyalty_woo_settings');

            update_option('loyalty_woo_auto_points_enabled', isset($_POST['auto_points_enabled']));
            update_option('loyalty_woo_points_per_euro', floatval($_POST['points_per_euro']));
            update_option('loyalty_woo_min_amount', floatval($_POST['min_amount']));
            update_option('loyalty_woo_exclude_shipping', isset($_POST['exclude_shipping']));
            update_option('loyalty_woo_exclude_taxes', isset($_POST['exclude_taxes']));
            update_option('loyalty_woo_exclude_coupon_orders', isset($_POST['exclude_coupon_orders']));
            update_option('loyalty_woo_coupon_expiry_days', intval($_POST['coupon_expiry_days']));
            update_option('loyalty_woo_send_notifications', isset($_POST['send_notifications']));

            echo '<div class="notice notice-success"><p>Impostazioni salvate!</p></div>';
        }

        // Recupera valori
        $auto_points_enabled = get_option('loyalty_woo_auto_points_enabled', true);
        $points_per_euro = get_option('loyalty_woo_points_per_euro', 1);
        $min_amount = get_option('loyalty_woo_min_amount', 0);
        $exclude_shipping = get_option('loyalty_woo_exclude_shipping', false);
        $exclude_taxes = get_option('loyalty_woo_exclude_taxes', false);
        $exclude_coupon_orders = get_option('loyalty_woo_exclude_coupon_orders', false);
        $coupon_expiry_days = get_option('loyalty_woo_coupon_expiry_days', 30);
        $send_notifications = get_option('loyalty_woo_send_notifications', true);

        // Statistiche
        $this->render_statistics();

        ?>
        <div class="wrap">
            <h1>🛒 Integrazione WooCommerce</h1>
            <p>Configura come i clienti guadagnano punti dagli ordini WooCommerce e come possono riscattare prodotti.</p>

            <form method="post" action="">
                <?php wp_nonce_field('loyalty_woo_settings'); ?>

                <!-- Punti Automatici -->
                <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h2>⭐ Punti Automatici per Ordini</h2>

                    <table class="form-table">
                        <tr>
                            <th><label>Abilita Punti Automatici</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="auto_points_enabled" value="1" <?php checked($auto_points_enabled, true); ?>>
                                    I clienti guadagnano punti automaticamente quando un ordine viene completato
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="points_per_euro">Punti per Euro</label></th>
                            <td>
                                <input type="number" name="points_per_euro" id="points_per_euro" value="<?php echo esc_attr($points_per_euro); ?>" min="0" step="0.01" class="small-text">
                                <p class="description">Quanti punti guadagna il cliente per ogni euro speso (es: 1 = 1 punto per euro)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="min_amount">Importo Minimo</label></th>
                            <td>
                                €<input type="number" name="min_amount" id="min_amount" value="<?php echo esc_attr($min_amount); ?>" min="0" step="0.01" class="small-text">
                                <p class="description">Importo minimo ordine per guadagnare punti (0 = nessun minimo)</p>
                            </td>
                        </tr>
                    </table>

                    <h3>Esclusioni</h3>
                    <table class="form-table">
                        <tr>
                            <th>Escludi dalla Somma</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="exclude_shipping" value="1" <?php checked($exclude_shipping, true); ?>>
                                    Spedizione
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="exclude_taxes" value="1" <?php checked($exclude_taxes, true); ?>>
                                    Tasse
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="exclude_coupon_orders" value="1" <?php checked($exclude_coupon_orders, true); ?>>
                                    Ordini con coupon (nessun punto se ordine ha coupon)
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Coupon -->
                <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h2>🎫 Configurazione Coupon</h2>

                    <table class="form-table">
                        <tr>
                            <th><label for="coupon_expiry_days">Scadenza Coupon</label></th>
                            <td>
                                <input type="number" name="coupon_expiry_days" id="coupon_expiry_days" value="<?php echo esc_attr($coupon_expiry_days); ?>" min="0" class="small-text"> giorni
                                <p class="description">Dopo quanti giorni scade il coupon generato dal riscatto (0 = nessuna scadenza)</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Notifiche -->
                <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h2>📧 Notifiche</h2>

                    <table class="form-table">
                        <tr>
                            <th><label>Invia Notifiche</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="send_notifications" value="1" <?php checked($send_notifications, true); ?>>
                                    Invia email/WhatsApp quando il cliente guadagna punti da un ordine
                                </label>
                                <p class="description">Usa i template configurati in Carta Fedeltà > Notifiche</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button('💾 Salva Impostazioni', 'primary', 'loyalty_woo_save_settings'); ?>
            </form>

            <!-- Guida Rapida -->
            <div style="background: #fff4cc; padding: 20px; margin: 20px 0; border-left: 4px solid #f59e0b; border-radius: 5px;">
                <h3 style="margin-top: 0;">📚 Guida Rapida</h3>
                <ol style="line-height: 1.8;">
                    <li><strong>Configura punti automatici</strong> - I clienti guadagnano punti quando completano un ordine</li>
                    <li><strong>Marca prodotti come premi</strong> - Vai su Prodotti, modifica un prodotto, abilita "Disponibile come premio fedeltà"</li>
                    <li><strong>Cliente riscatta</strong> - Il cliente vede il prodotto nella sua dashboard loyalty e può riscattarlo</li>
                    <li><strong>Coupon generato</strong> - Il sistema crea automaticamente un coupon WooCommerce monouso</li>
                    <li><strong>Cliente usa coupon</strong> - Al checkout, il cliente inserisce il codice coupon per ottenere lo sconto</li>
                </ol>
            </div>

        </div>
        <?php
    }

    /**
     * Render statistiche
     */
    private function render_statistics() {
        global $wpdb;

        // Prodotti disponibili come premi
        $total_rewards = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_loyalty_reward_enabled'
            AND meta_value = '1'
        ");

        // Coupon generati
        $total_coupons = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_loyalty_redemption_id'
        ");

        // Punti totali assegnati da ordini (ultimi 30 giorni)
        $table = $wpdb->prefix . 'loyalty_transactions';
        $points_from_orders = $wpdb->get_var("
            SELECT COALESCE(SUM(points), 0)
            FROM $table
            WHERE transaction_type = 'earn'
            AND note LIKE 'Ordine #%'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");

        ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #667eea;">
                <div style="font-size: 12px; color: #6b7280; margin-bottom: 5px;">Prodotti Premio</div>
                <div style="font-size: 32px; font-weight: bold; color: #1f2937;"><?php echo intval($total_rewards); ?></div>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #10b981;">
                <div style="font-size: 12px; color: #6b7280; margin-bottom: 5px;">Coupon Generati</div>
                <div style="font-size: 32px; font-weight: bold; color: #1f2937;"><?php echo intval($total_coupons); ?></div>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #f59e0b;">
                <div style="font-size: 12px; color: #6b7280; margin-bottom: 5px;">Punti Ordini (30gg)</div>
                <div style="font-size: 32px; font-weight: bold; color: #1f2937;"><?php echo intval($points_from_orders); ?></div>
            </div>
        </div>
        <?php
    }
}
