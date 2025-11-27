<?php
/**
 * Admin Dashboard - Panoramica Sistema Carta Fedeltà
 */

if (!defined('ABSPATH')) exit;

global $wpdb;

// Statistiche generali
$table_transactions = $wpdb->prefix . 'loyalty_transactions';
$table_redemptions = $wpdb->prefix . 'loyalty_redemptions';
$table_rewards = $wpdb->prefix . 'loyalty_rewards';

// Totale utenti con punti
$total_users = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $table_transactions");

// Totale punti distribuiti
$total_points_earned = $wpdb->get_var("SELECT SUM(points) FROM $table_transactions WHERE transaction_type = 'earn'");

// Totale punti riscattati
$total_points_redeemed = $wpdb->get_var("SELECT SUM(points_used) FROM $table_redemptions");

// Totale transazioni oggi
$today = date('Y-m-d');
$transactions_today = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table_transactions WHERE DATE(created_at) = %s AND transaction_type = 'earn'",
    $today
));

// Punti distribuiti oggi
$points_today = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(points) FROM $table_transactions WHERE DATE(created_at) = %s AND transaction_type = 'earn'",
    $today
));

// Premi riscattati oggi
$redemptions_today = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table_redemptions WHERE DATE(redeemed_at) = %s",
    $today
));

// Ultime transazioni
$recent_transactions = $wpdb->get_results(
    "SELECT t.*, u.display_name, u.user_email 
    FROM $table_transactions t 
    LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID 
    WHERE t.transaction_type = 'earn'
    ORDER BY t.created_at DESC 
    LIMIT 10"
);

// Top utenti per punti
$top_users = $wpdb->get_results(
    "SELECT user_id, u.display_name, u.user_email, SUM(points) as total_points
    FROM $table_transactions t
    LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
    GROUP BY user_id
    ORDER BY total_points DESC
    LIMIT 10"
);

// Premi più riscattati
$popular_rewards = $wpdb->get_results(
    "SELECT r.name, COUNT(rd.id) as redemption_count
    FROM $table_redemptions rd
    LEFT JOIN $table_rewards r ON rd.reward_id = r.id
    GROUP BY rd.reward_id
    ORDER BY redemption_count DESC
    LIMIT 5"
);

?>

<div class="wrap">
    <h1>📊 Dashboard Carta Fedeltà</h1>
    
    <!-- Statistiche Principali -->
    <div class="loyalty-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
        
        <div class="loyalty-stat-card" style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 10px 0; color: #6b7280; font-size: 14px;">👥 Utenti Attivi</h3>
            <p style="margin: 0; font-size: 36px; font-weight: 700; color: #4f46e5;"><?php echo number_format($total_users); ?></p>
        </div>
        
        <div class="loyalty-stat-card" style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 10px 0; color: #6b7280; font-size: 14px;">⭐ Punti Totali Distribuiti</h3>
            <p style="margin: 0; font-size: 36px; font-weight: 700; color: #10b981;"><?php echo number_format($total_points_earned ?: 0); ?></p>
        </div>
        
        <div class="loyalty-stat-card" style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 10px 0; color: #6b7280; font-size: 14px;">🎁 Punti Riscattati</h3>
            <p style="margin: 0; font-size: 36px; font-weight: 700; color: #f59e0b;"><?php echo number_format($total_points_redeemed ?: 0); ?></p>
        </div>
        
        <div class="loyalty-stat-card" style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 10px 0; color: #6b7280; font-size: 14px;">📈 Transazioni Oggi</h3>
            <p style="margin: 0; font-size: 36px; font-weight: 700; color: #7c3aed;"><?php echo number_format($transactions_today); ?></p>
            <small style="color: #6b7280;">+<?php echo number_format($points_today ?: 0); ?> punti</small>
        </div>
        
    </div>
    
    <!-- Configurazione Rapida -->
    <div class="loyalty-quick-config" style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin: 20px 0;">
        <h2>⚙️ Configurazione</h2>
        
        <form method="post" action="options.php">
            <?php settings_fields('loyalty_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="loyalty_points_per_euro">Punti per Euro</label></th>
                    <td>
                        <input type="number" id="loyalty_points_per_euro" name="loyalty_points_per_euro" 
                               value="<?php echo esc_attr(get_option('loyalty_points_per_euro', 1)); ?>" 
                               min="0" step="0.1" class="regular-text">
                        <p class="description">Quanti punti vengono assegnati per ogni euro speso</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="loyalty_api_key">API Key</label></th>
                    <td>
                        <input type="text" id="loyalty_api_key" name="loyalty_api_key" 
                               value="<?php echo esc_attr(get_option('loyalty_api_key', wp_generate_password(32, false))); ?>" 
                               class="regular-text">
                        <p class="description">Chiave API per l'app del negoziante</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="loyalty_webhook_url">Webhook URL (n8n)</label></th>
                    <td>
                        <input type="url" id="loyalty_webhook_url" name="loyalty_webhook_url" 
                               value="<?php echo esc_attr(get_option('loyalty_webhook_url', '')); ?>" 
                               class="regular-text" placeholder="https://tuo-n8n.com/webhook/loyalty">
                        <p class="description">URL del webhook n8n per automazioni</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Salva Configurazione'); ?>
        </form>
    </div>
    
    <!-- Ultime Transazioni -->
    <div class="loyalty-recent-transactions" style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin: 20px 0;">
        <h2>📝 Ultime Transazioni</h2>
        
        <?php if (empty($recent_transactions)) : ?>
            <p>Nessuna transazione ancora.</p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Data/Ora</th>
                        <th>Utente</th>
                        <th>Punti</th>
                        <th>Importo</th>
                        <th>Nota</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_transactions as $transaction) : ?>
                        <tr>
                            <td><?php echo date_i18n('d/m/Y H:i', strtotime($transaction->created_at)); ?></td>
                            <td>
                                <strong><?php echo esc_html($transaction->display_name); ?></strong><br>
                                <small><?php echo esc_html($transaction->user_email); ?></small>
                            </td>
                            <td><strong style="color: #10b981;">+<?php echo number_format($transaction->points); ?></strong></td>
                            <td><?php echo $transaction->amount ? '€' . number_format($transaction->amount, 2) : '-'; ?></td>
                            <td><?php echo esc_html($transaction->note ?: '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <p style="margin-top: 15px;">
            <a href="<?php echo admin_url('admin.php?page=loyalty-transactions'); ?>" class="button">
                Vedi Tutte le Transazioni
            </a>
        </p>
    </div>
    
    <!-- Top Utenti -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
        
        <div style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h2>🏆 Top Utenti</h2>
            
            <?php if (empty($top_users)) : ?>
                <p>Nessun dato disponibile.</p>
            <?php else : ?>
                <ol style="padding-left: 20px;">
                    <?php foreach ($top_users as $index => $user) : ?>
                        <li style="margin-bottom: 10px;">
                            <strong><?php echo esc_html($user->display_name); ?></strong>
                            <br>
                            <small><?php echo esc_html($user->user_email); ?></small>
                            <br>
                            <span style="color: #10b981; font-weight: 600;"><?php echo number_format($user->total_points); ?> punti</span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>
        
        <div style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h2>🎁 Premi Più Popolari</h2>
            
            <?php if (empty($popular_rewards)) : ?>
                <p>Nessun premio riscattato ancora.</p>
            <?php else : ?>
                <ol style="padding-left: 20px;">
                    <?php foreach ($popular_rewards as $reward) : ?>
                        <li style="margin-bottom: 10px;">
                            <strong><?php echo esc_html($reward->name); ?></strong>
                            <br>
                            <span style="color: #7c3aed;">Riscattato <?php echo $reward->redemption_count; ?> volte</span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
            
            <p style="margin-top: 15px;">
                <a href="<?php echo admin_url('admin.php?page=loyalty-rewards'); ?>" class="button">
                    Gestisci Premi
                </a>
            </p>
        </div>
        
    </div>
    
    <!-- Info Shortcode -->
    <div style="background: #f0fdf4; border-left: 4px solid #10b981; padding: 20px; margin: 20px 0; border-radius: 5px;">
        <h3 style="margin-top: 0;">💡 Come Usare</h3>
        <p><strong>Per mostrare la carta fedeltà agli utenti:</strong></p>
        <p>Inserisci questo shortcode in una pagina o post:</p>
        <code style="background: white; padding: 10px; display: inline-block; border-radius: 5px;">[loyalty_card]</code>
        
        <p style="margin-top: 15px;"><strong>API Endpoint disponibili:</strong></p>
        <ul>
            <li><code><?php echo rest_url('loyalty/v1/add-points'); ?></code> - Aggiungi punti</li>
            <li><code><?php echo rest_url('loyalty/v1/balance/{user_id}'); ?></code> - Ottieni saldo</li>
            <li><code><?php echo rest_url('loyalty/v1/rewards'); ?></code> - Lista premi</li>
            <li><code><?php echo rest_url('loyalty/v1/redeem'); ?></code> - Riscatta premio</li>
        </ul>
    </div>
    
</div>
