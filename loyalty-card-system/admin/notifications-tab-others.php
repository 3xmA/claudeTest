<?php
/**
 * Tab: Template WhatsApp, Test, Statistiche
 */

if (!defined('ABSPATH')) {
    exit;
}

// TAB: TEMPLATE WHATSAPP
if ($active_tab === 'whatsapp') :
?>

<form method="post" action="">
    <?php wp_nonce_field('loyalty_notifications'); ?>
    <input type="hidden" name="tab" value="whatsapp">
    
    <div style="background: #dcfce7; padding: 15px; margin: 20px 0; border-left: 4px solid #10b981; border-radius: 5px;">
        <h3 style="margin-top: 0;">📱 WhatsApp Markdown</h3>
        <p>Puoi usare la formattazione WhatsApp:</p>
        <ul style="margin: 10px 0; padding-left: 25px;">
            <li><code>*grassetto*</code> → <strong>grassetto</strong></li>
            <li><code>_corsivo_</code> → <em>corsivo</em></li>
            <li><code>~barrato~</code> → <del>barrato</del></li>
        </ul>
    </div>
    
    <!-- Template: Punti Aggiunti -->
    <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2>📱 Messaggio: Punti Aggiunti</h2>
        
        <table class="form-table">
            <tr>
                <th style="width: 200px;"><label>Attivo</label></th>
                <td>
                    <label>
                        <input type="checkbox" name="loyalty_whatsapp_points_added_enabled" value="1" <?php checked(get_option('loyalty_whatsapp_points_added_enabled'), true); ?>>
                        Invia WhatsApp quando un cliente guadagna punti
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="whatsapp_points_added_text">Messaggio</label></th>
                <td>
                    <textarea name="loyalty_whatsapp_points_added_text" id="whatsapp_points_added_text" rows="10" class="large-text" style="font-family: monospace; font-size: 13px;"><?php echo esc_textarea(get_option('loyalty_whatsapp_points_added_text', '')); ?></textarea>
                    <p class="description">Massimo 1024 caratteri</p>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Template: Premio Riscattato -->
    <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2>🎁 Messaggio: Premio Riscattato</h2>
        
        <table class="form-table">
            <tr>
                <th style="width: 200px;"><label>Attivo</label></th>
                <td>
                    <label>
                        <input type="checkbox" name="loyalty_whatsapp_reward_redeemed_enabled" value="1" <?php checked(get_option('loyalty_whatsapp_reward_redeemed_enabled'), true); ?>>
                        Invia WhatsApp quando un cliente riscatta un premio
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="whatsapp_reward_redeemed_text">Messaggio</label></th>
                <td>
                    <textarea name="loyalty_whatsapp_reward_redeemed_text" id="whatsapp_reward_redeemed_text" rows="10" class="large-text" style="font-family: monospace; font-size: 13px;"><?php echo esc_textarea(get_option('loyalty_whatsapp_reward_redeemed_text', '')); ?></textarea>
                    <p class="description">Il sistema invierà anche automaticamente l'immagine QR del premio</p>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Template: Soglia Raggiunta -->
    <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2>🔥 Messaggio: Soglia Raggiunta</h2>
        
        <table class="form-table">
            <tr>
                <th style="width: 200px;"><label>Attivo</label></th>
                <td>
                    <label>
                        <input type="checkbox" name="loyalty_whatsapp_threshold_reached_enabled" value="1" <?php checked(get_option('loyalty_whatsapp_threshold_reached_enabled'), true); ?>>
                        Invia WhatsApp quando un cliente raggiunge 100+ punti
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="whatsapp_threshold_reached_text">Messaggio</label></th>
                <td>
                    <textarea name="loyalty_whatsapp_threshold_reached_text" id="whatsapp_threshold_reached_text" rows="10" class="large-text" style="font-family: monospace; font-size: 13px;"><?php echo esc_textarea(get_option('loyalty_whatsapp_threshold_reached_text', '')); ?></textarea>
                </td>
            </tr>
        </table>
    </div>
    
    <?php submit_button('💾 Salva Template WhatsApp', 'primary', 'loyalty_save_notifications'); ?>
</form>

<?php endif; ?>

<?php
// TAB: TEST
if ($active_tab === 'test') :
?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
    
    <!-- Test Email -->
    <div style="background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2>📧 Test Email</h2>
        
        <form method="post" action="">
            <input type="hidden" name="_wpnonce_test_email" value="<?php echo wp_create_nonce('loyalty_test_email'); ?>">
            
            <table class="form-table">
                <tr>
                    <th><label for="test_email_to">Invia a:</label></th>
                    <td>
                        <input type="email" name="test_email_to" id="test_email_to" value="<?php echo esc_attr(get_option('admin_email')); ?>" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="test_email_template">Template:</label></th>
                    <td>
                        <select name="test_email_template" id="test_email_template" class="regular-text">
                            <option value="points_added">Punti Aggiunti</option>
                            <option value="reward_redeemed">Premio Riscattato</option>
                            <option value="threshold_reached">Soglia Raggiunta</option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p style="background: #f9fafb; padding: 15px; border-radius: 5px; margin: 15px 0;">
                <strong>📝 Dati di test:</strong><br>
                <small style="font-family: monospace;">Nome: Mario Rossi | Punti: 50 | Saldo: 150 | Importo: €50</small>
            </p>
            
            <button type="submit" name="test_email_send" class="button button-primary button-large" style="width: 100%;">
                📧 Invia Email di Test
            </button>
        </form>
    </div>
    
    <!-- Test WhatsApp -->
    <div style="background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2>📱 Test WhatsApp</h2>
        
        <form method="post" action="">
            <input type="hidden" name="_wpnonce_test_whatsapp" value="<?php echo wp_create_nonce('loyalty_test_whatsapp'); ?>">
            
            <table class="form-table">
                <tr>
                    <th><label for="test_whatsapp_phone">Numero:</label></th>
                    <td>
                        <input type="text" name="test_whatsapp_phone" id="test_whatsapp_phone" value="393331234567" class="regular-text" placeholder="393331234567" required>
                        <p class="description">Formato: 393331234567 (SENZA +)</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="test_whatsapp_template">Template:</label></th>
                    <td>
                        <select name="test_whatsapp_template" id="test_whatsapp_template" class="regular-text">
                            <option value="points_added">Punti Aggiunti</option>
                            <option value="reward_redeemed">Premio Riscattato</option>
                            <option value="threshold_reached">Soglia Raggiunta</option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p style="background: #f9fafb; padding: 15px; border-radius: 5px; margin: 15px 0;">
                <strong>📝 Dati di test:</strong><br>
                <small style="font-family: monospace;">Nome: Mario Rossi | Punti: 50 | Saldo: 150 | Importo: €50</small>
            </p>
            
            <button type="submit" name="test_whatsapp_send" class="button button-primary button-large" style="width: 100%;">
                📱 Invia WhatsApp di Test
            </button>
        </form>
    </div>
    
</div>

<div style="background: #fff4cc; padding: 20px; margin: 20px 0; border-left: 4px solid #f59e0b; border-radius: 5px;">
    <h3 style="margin-top: 0;">⚠️ Note sui Test</h3>
    <ul style="margin: 10px 0; padding-left: 25px;">
        <li>I messaggi di test usano dati fittizi (nome: Mario Rossi, ecc.)</li>
        <li>Le variabili verranno sostituite automaticamente</li>
        <li>I test NON vengono registrati nelle statistiche</li>
        <li>Assicurati di aver salvato i template prima di testare</li>
    </ul>
</div>

<?php endif; ?>

<?php
// TAB: STATISTICHE
if ($active_tab === 'stats') :

global $wpdb;
$table = $wpdb->prefix . 'loyalty_notification_logs';

// Statistiche ultimi 30 giorni
$email_stats = Loyalty_Email_Sender::get_stats(30);
$whatsapp_stats = Loyalty_WhatsApp_Sender::get_stats(30);

// Ultimi invii
$recent_logs = $wpdb->get_results("
    SELECT * FROM $table 
    ORDER BY sent_at DESC 
    LIMIT 50
");
?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
    
    <!-- Stats Email -->
    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #4f46e5;">
        <h3 style="margin: 0 0 15px 0; color: #4f46e5;">📧 Email (30 giorni)</h3>
        <p style="font-size: 36px; font-weight: bold; margin: 10px 0;"><?php echo $email_stats['total']; ?></p>
        <p style="margin: 5px 0;"><strong><?php echo $email_stats['sent']; ?></strong> inviate</p>
        <p style="margin: 5px 0; color: #ef4444;"><strong><?php echo $email_stats['failed']; ?></strong> fallite</p>
        <p style="margin: 5px 0; color: #f59e0b;"><strong><?php echo $email_stats['blocked']; ?></strong> bloccate</p>
        <p style="margin: 15px 0 0 0; padding-top: 15px; border-top: 1px solid #e5e7eb;">
            <strong>Tasso Successo:</strong> <?php echo $email_stats['success_rate']; ?>%
        </p>
    </div>
    
    <!-- Stats WhatsApp -->
    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #10b981;">
        <h3 style="margin: 0 0 15px 0; color: #10b981;">📱 WhatsApp (30 giorni)</h3>
        <p style="font-size: 36px; font-weight: bold; margin: 10px 0;"><?php echo $whatsapp_stats['total']; ?></p>
        <p style="margin: 5px 0;"><strong><?php echo $whatsapp_stats['sent']; ?></strong> inviati</p>
        <p style="margin: 5px 0; color: #ef4444;"><strong><?php echo $whatsapp_stats['failed']; ?></strong> falliti</p>
        <p style="margin: 5px 0; color: #f59e0b;"><strong><?php echo $whatsapp_stats['blocked']; ?></strong> bloccati</p>
        <p style="margin: 15px 0 0 0; padding-top: 15px; border-top: 1px solid #e5e7eb;">
            <strong>Tasso Successo:</strong> <?php echo $whatsapp_stats['success_rate']; ?>%
        </p>
    </div>
    
    <!-- Stats Totali -->
    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #f59e0b;">
        <h3 style="margin: 0 0 15px 0; color: #f59e0b;">📊 Totale</h3>
        <p style="font-size: 36px; font-weight: bold; margin: 10px 0;"><?php echo $email_stats['total'] + $whatsapp_stats['total']; ?></p>
        <p style="margin: 5px 0;"><strong><?php echo $email_stats['sent'] + $whatsapp_stats['sent']; ?></strong> inviate</p>
        <p style="margin: 5px 0; color: #ef4444;"><strong><?php echo $email_stats['failed'] + $whatsapp_stats['failed']; ?></strong> fallite</p>
        <p style="margin: 5px 0; color: #f59e0b;"><strong><?php echo $email_stats['blocked'] + $whatsapp_stats['blocked']; ?></strong> bloccate</p>
        <?php 
        $total_all = $email_stats['total'] + $whatsapp_stats['total'];
        $sent_all = $email_stats['sent'] + $whatsapp_stats['sent'];
        $success_rate_all = $total_all > 0 ? round(($sent_all / $total_all) * 100, 1) : 0;
        ?>
        <p style="margin: 15px 0 0 0; padding-top: 15px; border-top: 1px solid #e5e7eb;">
            <strong>Tasso Successo:</strong> <?php echo $success_rate_all; ?>%
        </p>
    </div>
</div>

<!-- Log Ultimi Invii -->
<div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
    <h2>📋 Ultimi Invii</h2>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 140px;">Data/Ora</th>
                <th style="width: 80px;">Tipo</th>
                <th>Destinatario</th>
                <th style="width: 120px;">Evento</th>
                <th style="width: 100px;">Stato</th>
                <th>Errore</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recent_logs)) : ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px;">
                        Nessun invio registrato
                    </td>
                </tr>
            <?php else : ?>
                <?php foreach ($recent_logs as $log) : ?>
                    <tr>
                        <td><?php echo date_i18n('d/m/Y H:i', strtotime($log->sent_at)); ?></td>
                        <td>
                            <?php if ($log->type === 'email') : ?>
                                <span style="background: #dbeafe; color: #1e40af; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">📧 EMAIL</span>
                            <?php else : ?>
                                <span style="background: #dcfce7; color: #166534; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">📱 WA</span>
                            <?php endif; ?>
                        </td>
                        <td><code style="font-size: 12px;"><?php echo esc_html($log->recipient); ?></code></td>
                        <td><?php echo esc_html($log->event); ?></td>
                        <td>
                            <?php if ($log->status === 'sent') : ?>
                                <span style="color: #10b981; font-weight: bold;">✅ Inviato</span>
                            <?php elseif ($log->status === 'failed') : ?>
                                <span style="color: #ef4444; font-weight: bold;">❌ Fallito</span>
                            <?php else : ?>
                                <span style="color: #f59e0b; font-weight: bold;">🚫 Bloccato</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($log->error_message)) : ?>
                                <small style="color: #6b7280;"><?php echo esc_html(substr($log->error_message, 0, 50)); ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>
