<?php
/**
 * Template: Carta Fedeltà Utente
 * Shortcode: [loyalty_card]
 */

if (!defined('ABSPATH')) exit;

$user = wp_get_current_user();
$stats = Loyalty_Points::get_user_stats($user_id);
$qr_url = Loyalty_QRCode::generate_user_qr($user_id, 300);
$recent_transactions = Loyalty_Points::get_user_transactions($user_id, 10);
$recent_redemptions = Loyalty_Points::get_user_redemptions($user_id);

// Ottieni premi disponibili
global $wpdb;
$table_rewards = $wpdb->prefix . 'loyalty_rewards';
$available_rewards = $wpdb->get_results("SELECT * FROM $table_rewards WHERE is_active = 1 ORDER BY points_required ASC");
?>

<div class="loyalty-card-container">
    
    <!-- Carta Fedeltà Principale -->
    <div class="loyalty-card">
        <div class="loyalty-card-header">
            <h2>🎫 La Tua Carta Fedeltà</h2>
            <p class="user-name"><?php echo esc_html($user->display_name); ?></p>
        </div>
        
        <div class="loyalty-card-body">
            <div class="points-section">
                <div class="points-balance">
                    <span class="points-label">Punti Disponibili</span>
                    <span class="points-value"><?php echo number_format($stats['balance']); ?></span>
                </div>
                
                <div class="points-stats">
                    <div class="stat-item">
                        <span class="stat-label">Totale Guadagnati</span>
                        <span class="stat-value"><?php echo number_format($stats['total_earned']); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Acquisti</span>
                        <span class="stat-value"><?php echo $stats['transaction_count']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Premi Ottenuti</span>
                        <span class="stat-value"><?php echo $stats['rewards_count']; ?></span>
                    </div>
                </div>
            </div>
            
            <div class="qr-section">
                <p class="qr-instructions">Mostra questo QR al negoziante</p>
                <div class="qr-code-wrapper">
                    <img src="<?php echo esc_url($qr_url); ?>" alt="QR Code Carta Fedeltà" class="qr-code-image">
                </div>
                <p class="user-id-text">ID: #<?php echo $user_id; ?></p>
            </div>
        </div>
    </div>
    
    <!-- Premi Disponibili -->
    <div class="rewards-section">
        <h3>🎁 Premi Disponibili</h3>
        
        <?php if (empty($available_rewards)) : ?>
            <p>Nessun premio disponibile al momento.</p>
        <?php else : ?>
            <div class="rewards-grid">
                <?php foreach ($available_rewards as $reward) : 
                    $can_redeem = $stats['balance'] >= $reward->points_required;
                    $progress = min(100, ($stats['balance'] / $reward->points_required) * 100);
                ?>
                    <div class="reward-card <?php echo $can_redeem ? 'can-redeem' : 'locked'; ?>">
                        <div class="reward-header">
                            <h4><?php echo esc_html($reward->name); ?></h4>
                            <span class="reward-points"><?php echo number_format($reward->points_required); ?> punti</span>
                        </div>
                        
                        <div class="reward-body">
                            <p><?php echo esc_html($reward->description); ?></p>
                            
                            <div class="reward-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                                <span class="progress-text">
                                    <?php if ($can_redeem) : ?>
                                        ✅ Disponibile!
                                    <?php else : ?>
                                        Ti mancano <?php echo number_format($reward->points_required - $stats['balance']); ?> punti
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <?php if ($can_redeem) : ?>
                                <button class="btn-redeem" data-reward-id="<?php echo $reward->id; ?>">
                                    Riscatta Premio
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Storico Transazioni -->
    <div class="transactions-section">
        <h3>📊 Ultime Transazioni</h3>
        
        <?php if (empty($recent_transactions)) : ?>
            <p>Nessuna transazione ancora. Inizia a guadagnare punti!</p>
        <?php else : ?>
            <div class="transactions-list">
                <?php foreach ($recent_transactions as $transaction) : ?>
                    <div class="transaction-item <?php echo $transaction->transaction_type; ?>">
                        <div class="transaction-info">
                            <span class="transaction-date">
                                <?php echo date_i18n('d/m/Y H:i', strtotime($transaction->created_at)); ?>
                            </span>
                            <span class="transaction-note">
                                <?php echo $transaction->note ? esc_html($transaction->note) : 'Acquisto in negozio'; ?>
                            </span>
                            <?php if ($transaction->amount) : ?>
                                <span class="transaction-amount">€<?php echo number_format($transaction->amount, 2); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="transaction-points <?php echo $transaction->points > 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $transaction->points > 0 ? '+' : ''; ?><?php echo number_format($transaction->points); ?> punti
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Premi Riscattati -->
    <?php if (!empty($recent_redemptions)) : ?>
    <div class="redemptions-section">
        <h3>🎫 I Tuoi Premi</h3>
        
        <div class="redemptions-list">
            <?php foreach ($recent_redemptions as $redemption) : ?>
                <div class="redemption-item status-<?php echo $redemption->status; ?>">
                    <div class="redemption-info">
                        <h4><?php echo esc_html($redemption->reward_name); ?></h4>
                        <p><?php echo esc_html($redemption->reward_description); ?></p>
                        <span class="redemption-date">
                            Riscattato il <?php echo date_i18n('d/m/Y H:i', strtotime($redemption->redeemed_at)); ?>
                        </span>
                    </div>
                    
                    <?php if ($redemption->status === 'active') : ?>
                        <div class="redemption-qr">
                            <p class="qr-instructions-small">Mostra questo QR al negoziante</p>
                            <?php 
                            $reward_qr = Loyalty_QRCode::generate_reward_qr($redemption->redemption_code, 200);
                            ?>
                            <img src="<?php echo esc_url($reward_qr); ?>" alt="QR Premio" class="qr-code-small">
                            <p class="redemption-code">Codice: <strong><?php echo $redemption->redemption_code; ?></strong></p>
                        </div>
                    <?php else : ?>
                        <div class="redemption-used">
                            <span class="used-badge">✅ Utilizzato</span>
                            <?php if ($redemption->used_at) : ?>
                                <span class="used-date">il <?php echo date_i18n('d/m/Y', strtotime($redemption->used_at)); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal per conferma riscatto -->
<div id="redeemModal" class="loyalty-modal" style="display: none;">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <h3>Conferma Riscatto</h3>
        <div id="modalBody"></div>
    </div>
</div>
