<?php
/**
 * Admin Page - Gestione Premi
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$table_rewards = $wpdb->prefix . 'loyalty_rewards';
$table_redemptions = $wpdb->prefix . 'loyalty_redemptions';

// Gestione azioni
$message = '';
$message_type = '';

// Aggiungi premio
if (isset($_POST['add_reward']) && check_admin_referer('loyalty_add_reward')) {
    $name = sanitize_text_field($_POST['reward_name']);
    $description = sanitize_textarea_field($_POST['reward_description']);
    $points_required = intval($_POST['points_required']);
    $reward_type = sanitize_text_field($_POST['reward_type']);
    $reward_value = sanitize_text_field($_POST['reward_value']);
    
    if ($name && $points_required > 0) {
        $result = $wpdb->insert($table_rewards, array(
            'name' => $name,
            'description' => $description,
            'points_required' => $points_required,
            'reward_type' => $reward_type,
            'reward_value' => $reward_value,
            'is_active' => 1,
        ));
        
        if ($result) {
            $message = 'Premio aggiunto con successo!';
            $message_type = 'success';
        } else {
            $message = 'Errore nell\'aggiungere il premio.';
            $message_type = 'error';
        }
    }
}

// Modifica premio
if (isset($_POST['edit_reward']) && check_admin_referer('loyalty_edit_reward')) {
    $reward_id = intval($_POST['reward_id']);
    $name = sanitize_text_field($_POST['reward_name']);
    $description = sanitize_textarea_field($_POST['reward_description']);
    $points_required = intval($_POST['points_required']);
    $reward_type = sanitize_text_field($_POST['reward_type']);
    $reward_value = sanitize_text_field($_POST['reward_value']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $result = $wpdb->update($table_rewards, 
        array(
            'name' => $name,
            'description' => $description,
            'points_required' => $points_required,
            'reward_type' => $reward_type,
            'reward_value' => $reward_value,
            'is_active' => $is_active,
        ),
        array('id' => $reward_id)
    );
    
    if ($result !== false) {
        $message = 'Premio aggiornato con successo!';
        $message_type = 'success';
    }
}

// Elimina premio
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['reward_id'])) {
    $reward_id = intval($_GET['reward_id']);
    
    // Verifica se ci sono riscatti attivi
    $active_redemptions = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_redemptions WHERE reward_id = %d AND status = 'active'",
        $reward_id
    ));
    
    if ($active_redemptions > 0) {
        $message = 'Impossibile eliminare: ci sono ' . $active_redemptions . ' riscatti attivi per questo premio.';
        $message_type = 'error';
    } else {
        $wpdb->delete($table_rewards, array('id' => $reward_id));
        $message = 'Premio eliminato con successo!';
        $message_type = 'success';
    }
}

// Toggle attivo/disattivo
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['reward_id'])) {
    $reward_id = intval($_GET['reward_id']);
    $current = $wpdb->get_var($wpdb->prepare("SELECT is_active FROM $table_rewards WHERE id = %d", $reward_id));
    $new_status = $current ? 0 : 1;
    
    $wpdb->update($table_rewards, array('is_active' => $new_status), array('id' => $reward_id));
    $message = 'Stato premio aggiornato!';
    $message_type = 'success';
}

// Ottieni premi
$rewards = $wpdb->get_results("SELECT * FROM $table_rewards ORDER BY points_required ASC");

// Ottieni statistiche per ogni premio
$reward_stats = array();
foreach ($rewards as $reward) {
    $total_redemptions = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_redemptions WHERE reward_id = %d",
        $reward->id
    ));
    
    $active_redemptions = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_redemptions WHERE reward_id = %d AND status = 'active'",
        $reward->id
    ));
    
    $reward_stats[$reward->id] = array(
        'total' => $total_redemptions,
        'active' => $active_redemptions,
    );
}

// Modalità edit
$edit_reward = null;
if (isset($_GET['edit']) && isset($_GET['reward_id'])) {
    $edit_reward = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_rewards WHERE id = %d", intval($_GET['reward_id'])));
}

?>

<div class="wrap">
    <h1 class="wp-heading-inline">🎁 Gestione Premi</h1>
    <a href="#" class="page-title-action" onclick="document.getElementById('addRewardForm').scrollIntoView({behavior: 'smooth'}); return false;">
        ➕ Aggiungi Nuovo Premio
    </a>
    <hr class="wp-header-end">
    
    <!-- Messaggio -->
    <?php if ($message) : ?>
        <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Statistiche Generali -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
        <div style="background: #fff; padding: 15px; border-left: 4px solid #4f46e5; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 5px 0; font-size: 14px; color: #6b7280;">Premi Totali</h3>
            <p style="margin: 0; font-size: 24px; font-weight: 700; color: #4f46e5;"><?php echo count($rewards); ?></p>
        </div>
        <div style="background: #fff; padding: 15px; border-left: 4px solid #10b981; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 5px 0; font-size: 14px; color: #6b7280;">Premi Attivi</h3>
            <p style="margin: 0; font-size: 24px; font-weight: 700; color: #10b981;">
                <?php echo count(array_filter($rewards, function($r) { return $r->is_active; })); ?>
            </p>
        </div>
        <div style="background: #fff; padding: 15px; border-left: 4px solid #f59e0b; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 5px 0; font-size: 14px; color: #6b7280;">Riscatti Totali</h3>
            <p style="margin: 0; font-size: 24px; font-weight: 700; color: #f59e0b;">
                <?php echo number_format(array_sum(array_column($reward_stats, 'total'))); ?>
            </p>
        </div>
        <div style="background: #fff; padding: 15px; border-left: 4px solid #7c3aed; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 5px 0; font-size: 14px; color: #6b7280;">Riscatti Attivi</h3>
            <p style="margin: 0; font-size: 24px; font-weight: 700; color: #7c3aed;">
                <?php echo number_format(array_sum(array_column($reward_stats, 'active'))); ?>
            </p>
        </div>
    </div>
    
    <!-- Lista Premi -->
    <h2>📋 Premi Disponibili</h2>
    
    <?php if (empty($rewards)) : ?>
        <div style="background: #fff; padding: 40px; text-align: center; border: 1px solid #ddd;">
            <p style="font-size: 18px; color: #6b7280;">🎁 Nessun premio ancora. Creane uno!</p>
        </div>
    <?php else : ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <?php foreach ($rewards as $reward) : 
                $stats = $reward_stats[$reward->id];
            ?>
                <div style="background: #fff; border: 2px solid <?php echo $reward->is_active ? '#10b981' : '#d1d5db'; ?>; 
                            border-radius: 10px; padding: 20px; position: relative; 
                            <?php echo !$reward->is_active ? 'opacity: 0.7;' : ''; ?>">
                    
                    <!-- Badge stato -->
                    <div style="position: absolute; top: 15px; right: 15px;">
                        <?php if ($reward->is_active) : ?>
                            <span style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                ✅ ATTIVO
                            </span>
                        <?php else : ?>
                            <span style="background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                ⏸️ DISATTIVO
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Contenuto -->
                    <div style="margin-top: 30px;">
                        <h3 style="margin: 0 0 10px 0; font-size: 20px; color: #1f2937;">
                            <?php echo esc_html($reward->name); ?>
                        </h3>
                        
                        <p style="color: #6b7280; font-size: 14px; margin: 0 0 15px 0;">
                            <?php echo esc_html($reward->description ?: 'Nessuna descrizione'); ?>
                        </p>
                        
                        <div style="background: #f9fafb; padding: 12px; border-radius: 8px; margin-bottom: 15px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px;">
                                <div>
                                    <span style="color: #6b7280;">Punti richiesti:</span><br>
                                    <strong style="color: #4f46e5; font-size: 18px;"><?php echo number_format($reward->points_required); ?></strong>
                                </div>
                                <div>
                                    <span style="color: #6b7280;">Tipo:</span><br>
                                    <strong style="color: #1f2937;"><?php echo esc_html($reward->reward_type); ?></strong>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Statistiche -->
                        <div style="border-top: 1px solid #e5e7eb; padding-top: 12px; margin-bottom: 15px;">
                            <div style="display: flex; justify-content: space-around; font-size: 13px;">
                                <div style="text-align: center;">
                                    <span style="color: #6b7280; display: block;">Riscatti</span>
                                    <strong style="color: #1f2937; font-size: 16px;"><?php echo $stats['total']; ?></strong>
                                </div>
                                <div style="text-align: center;">
                                    <span style="color: #6b7280; display: block;">Attivi</span>
                                    <strong style="color: #10b981; font-size: 16px;"><?php echo $stats['active']; ?></strong>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Azioni -->
                        <div style="display: flex; gap: 8px;">
                            <a href="<?php echo admin_url('admin.php?page=loyalty-rewards&edit=1&reward_id=' . $reward->id); ?>" 
                               class="button button-primary" style="flex: 1; text-align: center;">
                                ✏️ Modifica
                            </a>
                            
                            <a href="<?php echo admin_url('admin.php?page=loyalty-rewards&action=toggle&reward_id=' . $reward->id); ?>" 
                               class="button" style="flex: 1; text-align: center;"
                               onclick="return confirm('Confermi il cambio di stato?');">
                                <?php echo $reward->is_active ? '⏸️ Disattiva' : '▶️ Attiva'; ?>
                            </a>
                            
                            <?php if ($stats['active'] == 0) : ?>
                                <a href="<?php echo admin_url('admin.php?page=loyalty-rewards&action=delete&reward_id=' . $reward->id); ?>" 
                                   class="button" style="color: #dc2626;"
                                   onclick="return confirm('Sei sicuro di voler eliminare questo premio?');">
                                    🗑️
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Form Aggiungi/Modifica Premio -->
    <div id="addRewardForm" style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 800px;">
        <h2><?php echo $edit_reward ? '✏️ Modifica Premio' : '➕ Aggiungi Nuovo Premio'; ?></h2>
        
        <?php if ($edit_reward) : ?>
            <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; margin-bottom: 20px;">
                <strong>⚠️ Attenzione:</strong> Le modifiche influenzeranno solo i futuri riscatti. I riscatti già effettuati restano invariati.
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <?php 
            if ($edit_reward) {
                wp_nonce_field('loyalty_edit_reward');
                echo '<input type="hidden" name="reward_id" value="' . $edit_reward->id . '">';
            } else {
                wp_nonce_field('loyalty_add_reward');
            }
            ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="reward_name">Nome Premio *</label></th>
                    <td>
                        <input type="text" id="reward_name" name="reward_name" 
                               value="<?php echo $edit_reward ? esc_attr($edit_reward->name) : ''; ?>" 
                               class="regular-text" required
                               placeholder="es: Sconto 10€">
                    </td>
                </tr>
                
                <tr>
                    <th><label for="reward_description">Descrizione</label></th>
                    <td>
                        <textarea id="reward_description" name="reward_description" 
                                  rows="3" class="large-text"
                                  placeholder="Descrizione dettagliata del premio..."><?php echo $edit_reward ? esc_textarea($edit_reward->description) : ''; ?></textarea>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="points_required">Punti Richiesti *</label></th>
                    <td>
                        <input type="number" id="points_required" name="points_required" 
                               value="<?php echo $edit_reward ? $edit_reward->points_required : ''; ?>" 
                               min="1" required
                               placeholder="100">
                        <p class="description">Quanti punti servono per riscattare questo premio</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="reward_type">Tipo Premio</label></th>
                    <td>
                        <select id="reward_type" name="reward_type">
                            <option value="discount" <?php echo ($edit_reward && $edit_reward->reward_type === 'discount') ? 'selected' : ''; ?>>
                                Sconto
                            </option>
                            <option value="free_product" <?php echo ($edit_reward && $edit_reward->reward_type === 'free_product') ? 'selected' : ''; ?>>
                                Prodotto Gratuito
                            </option>
                            <option value="voucher" <?php echo ($edit_reward && $edit_reward->reward_type === 'voucher') ? 'selected' : ''; ?>>
                                Voucher
                            </option>
                            <option value="service" <?php echo ($edit_reward && $edit_reward->reward_type === 'service') ? 'selected' : ''; ?>>
                                Servizio
                            </option>
                            <option value="other" <?php echo ($edit_reward && $edit_reward->reward_type === 'other') ? 'selected' : ''; ?>>
                                Altro
                            </option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="reward_value">Valore Premio</label></th>
                    <td>
                        <input type="text" id="reward_value" name="reward_value" 
                               value="<?php echo $edit_reward ? esc_attr($edit_reward->reward_value) : ''; ?>" 
                               class="regular-text"
                               placeholder="es: 10 (per sconto di 10€)">
                        <p class="description">Valore numerico del premio (es: importo sconto, quantità, ecc.)</p>
                    </td>
                </tr>
                
                <?php if ($edit_reward) : ?>
                <tr>
                    <th><label for="is_active">Stato</label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="is_active" name="is_active" 
                                   <?php checked($edit_reward->is_active, 1); ?>>
                            Premio attivo e visibile agli utenti
                        </label>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
            
            <?php if ($edit_reward) : ?>
                <p class="submit">
                    <button type="submit" name="edit_reward" class="button button-primary button-large">
                        💾 Salva Modifiche
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=loyalty-rewards'); ?>" class="button button-large">
                        Annulla
                    </a>
                </p>
            <?php else : ?>
                <p class="submit">
                    <button type="submit" name="add_reward" class="button button-primary button-large">
                        ➕ Aggiungi Premio
                    </button>
                </p>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Info Utili -->
    <div style="background: #f0fdf4; border-left: 4px solid #10b981; padding: 15px; margin-top: 30px;">
        <h3 style="margin-top: 0;">💡 Consigli per Creare Premi Efficaci</h3>
        <ul style="margin: 0;">
            <li><strong>Scala progressiva</strong>: Crea premi con diversi livelli di punti (es: 50, 100, 200, 500)</li>
            <li><strong>Varietà</strong>: Offri sia sconti che prodotti/servizi gratuiti</li>
            <li><strong>Chiarezza</strong>: Descrivi bene cosa include il premio</li>
            <li><strong>Valore percepito</strong>: Assicurati che i punti richiesti siano proporzionati al valore</li>
            <li><strong>Premi stagionali</strong>: Disattiva temporaneamente premi fuori stagione invece di eliminarli</li>
        </ul>
    </div>
</div>

<style>
.button-primary {
    background: #4f46e5 !important;
    border-color: #4338ca !important;
}
.button-primary:hover {
    background: #4338ca !important;
}
</style>
