<?php
/**
 * Admin Page - Gestione Utenti e Punti
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$table_transactions = $wpdb->prefix . 'loyalty_transactions';

// Gestione azioni
$message = '';
$message_type = '';

// Aggiungi/Sottrai punti manualmente
if (isset($_POST['adjust_points']) && check_admin_referer('loyalty_adjust_points')) {
    $user_id = intval($_POST['user_id']);
    $points = intval($_POST['points']);
    $note = sanitize_textarea_field($_POST['note']);
    $action_type = $_POST['action_type']; // 'add' o 'subtract'
    
    if ($user_id && $points != 0) {
        $final_points = $action_type === 'subtract' ? -abs($points) : abs($points);
        
        $result = $wpdb->insert($table_transactions, array(
            'user_id' => $user_id,
            'points' => $final_points,
            'transaction_type' => $action_type === 'add' ? 'earn' : 'redeem',
            'note' => $note ?: 'Aggiustamento manuale da admin',
        ));
        
        if ($result) {
            $message = 'Punti aggiustati con successo!';
            $message_type = 'success';
        } else {
            $message = 'Errore nell\'aggiustare i punti.';
            $message_type = 'error';
        }
    }
}

// Paginazione e filtri
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($paged - 1) * $per_page;
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$order_by = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'points';
$order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

// Query utenti con punti
$users_query = "
    SELECT 
        u.ID,
        u.display_name,
        u.user_email,
        u.user_registered,
        COALESCE(SUM(t.points), 0) as total_points,
        COUNT(CASE WHEN t.transaction_type = 'earn' THEN 1 END) as transaction_count,
        MAX(t.created_at) as last_transaction
    FROM {$wpdb->users} u
    LEFT JOIN $table_transactions t ON u.ID = t.user_id
";

$where = array();
$where_values = array();

if ($search) {
    $where[] = "(u.display_name LIKE %s OR u.user_email LIKE %s)";
    $search_term = '%' . $wpdb->esc_like($search) . '%';
    $where_values[] = $search_term;
    $where_values[] = $search_term;
}

if (!empty($where)) {
    $users_query .= " WHERE " . implode(' AND ', $where);
}

$users_query .= " GROUP BY u.ID";

// Ordinamento
$valid_orderby = array('points' => 'total_points', 'name' => 'u.display_name', 'email' => 'u.user_email', 'transactions' => 'transaction_count', 'registered' => 'u.user_registered');
$order_column = isset($valid_orderby[$order_by]) ? $valid_orderby[$order_by] : 'total_points';

$users_query .= " ORDER BY $order_column $order";

// Conta totale per paginazione
$count_query = "SELECT COUNT(DISTINCT u.ID) FROM {$wpdb->users} u LEFT JOIN $table_transactions t ON u.ID = t.user_id";
if (!empty($where)) {
    $count_query .= " WHERE " . implode(' AND ', $where);
}

if (!empty($where_values)) {
    $total_users = $wpdb->get_var($wpdb->prepare($count_query, $where_values));
} else {
    $total_users = $wpdb->get_var($count_query);
}

$total_pages = ceil($total_users / $per_page);

// Aggiungi limit
$users_query .= " LIMIT %d OFFSET %d";
$where_values[] = $per_page;
$where_values[] = $offset;

// Esegui query
if (!empty($where_values)) {
    $users = $wpdb->get_results($wpdb->prepare($users_query, $where_values));
} else {
    $users = $wpdb->get_results($wpdb->prepare($users_query, $per_page, $offset));
}

// Statistiche generali
$total_active_users = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $table_transactions");
$total_points_system = $wpdb->get_var("SELECT SUM(points) FROM $table_transactions");
$avg_points = $total_active_users > 0 ? round($total_points_system / $total_active_users) : 0;

?>

<div class="wrap">
    <h1 class="wp-heading-inline">👥 Gestione Utenti</h1>
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
            <h3 style="margin: 0 0 5px 0; font-size: 14px; color: #6b7280;">Utenti Attivi</h3>
            <p style="margin: 0; font-size: 24px; font-weight: 700; color: #4f46e5;"><?php echo number_format($total_active_users); ?></p>
        </div>
        <div style="background: #fff; padding: 15px; border-left: 4px solid #10b981; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 5px 0; font-size: 14px; color: #6b7280;">Punti Totali Sistema</h3>
            <p style="margin: 0; font-size: 24px; font-weight: 700; color: #10b981;"><?php echo number_format($total_points_system); ?></p>
        </div>
        <div style="background: #fff; padding: 15px; border-left: 4px solid #f59e0b; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 5px 0; font-size: 14px; color: #6b7280;">Media Punti per Utente</h3>
            <p style="margin: 0; font-size: 24px; font-weight: 700; color: #f59e0b;"><?php echo number_format($avg_points); ?></p>
        </div>
        <div style="background: #fff; padding: 15px; border-left: 4px solid #7c3aed; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 5px 0; font-size: 14px; color: #6b7280;">Utenti Totali WP</h3>
            <p style="margin: 0; font-size: 24px; font-weight: 700; color: #7c3aed;"><?php echo number_format(count_users()['total_users']); ?></p>
        </div>
    </div>
    
    <!-- Filtri e Ricerca -->
    <div class="tablenav top">
        <form method="get" action="" style="display: flex; gap: 10px; align-items: center;">
            <input type="hidden" name="page" value="loyalty-users">
            
            <input type="text" name="s" placeholder="🔍 Cerca utente..." 
                   value="<?php echo esc_attr($search); ?>" 
                   style="width: 250px; padding: 5px 10px;">
            
            <button type="submit" class="button">Cerca</button>
            
            <?php if ($search) : ?>
                <a href="<?php echo admin_url('admin.php?page=loyalty-users'); ?>" class="button">Reset</a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Tabella Utenti -->
    <?php if (empty($users)) : ?>
        <div style="background: #fff; padding: 40px; text-align: center; border: 1px solid #ddd; margin-top: 20px;">
            <p style="font-size: 18px; color: #6b7280;">👥 Nessun utente trovato</p>
        </div>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>
                        <a href="<?php echo add_query_arg(array('orderby' => 'name', 'order' => $order === 'DESC' ? 'asc' : 'desc')); ?>">
                            Nome <?php echo $order_by === 'name' ? ($order === 'DESC' ? '▼' : '▲') : ''; ?>
                        </a>
                    </th>
                    <th>
                        <a href="<?php echo add_query_arg(array('orderby' => 'email', 'order' => $order === 'DESC' ? 'asc' : 'desc')); ?>">
                            Email <?php echo $order_by === 'email' ? ($order === 'DESC' ? '▼' : '▲') : ''; ?>
                        </a>
                    </th>
                    <th style="width: 120px; text-align: right;">
                        <a href="<?php echo add_query_arg(array('orderby' => 'points', 'order' => $order === 'DESC' ? 'asc' : 'desc')); ?>">
                            Punti <?php echo $order_by === 'points' ? ($order === 'DESC' ? '▼' : '▲') : ''; ?>
                        </a>
                    </th>
                    <th style="width: 100px; text-align: center;">
                        <a href="<?php echo add_query_arg(array('orderby' => 'transactions', 'order' => $order === 'DESC' ? 'asc' : 'desc')); ?>">
                            Acquisti <?php echo $order_by === 'transactions' ? ($order === 'DESC' ? '▼' : '▲') : ''; ?>
                        </a>
                    </th>
                    <th style="width: 150px;">Ultima Attività</th>
                    <th style="width: 200px;">Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user) : ?>
                    <tr>
                        <td><strong>#<?php echo $user->ID; ?></strong></td>
                        <td>
                            <strong><?php echo esc_html($user->display_name); ?></strong>
                        </td>
                        <td>
                            <?php echo esc_html($user->user_email); ?>
                        </td>
                        <td style="text-align: right;">
                            <strong style="color: #10b981; font-size: 18px;">
                                <?php echo number_format($user->total_points); ?>
                            </strong>
                        </td>
                        <td style="text-align: center;">
                            <span style="background: #f3f4f6; padding: 4px 12px; border-radius: 12px; font-weight: 600;">
                                <?php echo $user->transaction_count; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user->last_transaction) : ?>
                                <small><?php echo human_time_diff(strtotime($user->last_transaction), current_time('timestamp')) . ' fa'; ?></small>
                            <?php else : ?>
                                <small style="color: #9ca3af;">Mai</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=loyalty-transactions&filter_user=' . $user->ID); ?>" 
                               class="button button-small">
                                📊 Transazioni
                            </a>
                            <a href="#" class="button button-small" 
                               onclick="openAdjustModal(<?php echo $user->ID; ?>, '<?php echo esc_js($user->display_name); ?>', <?php echo $user->total_points; ?>); return false;">
                                ⚙️ Aggiusta
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Paginazione -->
        <?php if ($total_pages > 1) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php echo number_format($total_users); ?> utenti</span>
                    <span class="pagination-links">
                        <?php
                        $base_url = admin_url('admin.php?page=loyalty-users');
                        if ($search) $base_url .= '&s=' . urlencode($search);
                        if ($order_by) $base_url .= '&orderby=' . $order_by . '&order=' . $order;
                        
                        if ($paged > 1) {
                            echo '<a class="button" href="' . $base_url . '&paged=1">«</a> ';
                            echo '<a class="button" href="' . $base_url . '&paged=' . ($paged - 1) . '">‹</a> ';
                        }
                        
                        echo '<span class="paging-input">
                                <span class="tablenav-paging-text">
                                    ' . $paged . ' di <span class="total-pages">' . $total_pages . '</span>
                                </span>
                              </span>';
                        
                        if ($paged < $total_pages) {
                            echo ' <a class="button" href="' . $base_url . '&paged=' . ($paged + 1) . '">›</a>';
                            echo ' <a class="button" href="' . $base_url . '&paged=' . $total_pages . '">»</a>';
                        }
                        ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <!-- Info Utili -->
    <div style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 15px; margin-top: 20px;">
        <h3 style="margin-top: 0;">💡 Suggerimenti</h3>
        <ul style="margin: 0;">
            <li><strong>Aggiusta Punti</strong>: Usa questa funzione per correzioni o bonus speciali</li>
            <li><strong>Transazioni</strong>: Clicca per vedere lo storico completo di ogni utente</li>
            <li><strong>Ordinamento</strong>: Clicca sulle intestazioni per ordinare la lista</li>
            <li>Gli utenti che non hanno mai fatto transazioni mostrano 0 punti</li>
        </ul>
    </div>
</div>

<!-- Modal per Aggiustamento Punti -->
<div id="adjustPointsModal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
    <div style="background: #fff; margin: 50px auto; padding: 30px; border-radius: 10px; max-width: 500px; box-shadow: 0 20px 50px rgba(0,0,0,0.3);">
        <h2 style="margin-top: 0;">⚙️ Aggiusta Punti</h2>
        
        <form method="post" action="">
            <?php wp_nonce_field('loyalty_adjust_points'); ?>
            <input type="hidden" name="user_id" id="adjust_user_id">
            
            <div style="background: #f9fafb; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <p style="margin: 0;"><strong>Utente:</strong> <span id="adjust_user_name"></span></p>
                <p style="margin: 5px 0 0 0;"><strong>Saldo Attuale:</strong> <span id="adjust_user_balance"></span> punti</p>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Azione</label>
                <div style="display: flex; gap: 15px;">
                    <label style="flex: 1;">
                        <input type="radio" name="action_type" value="add" checked>
                        <span style="color: #10b981; font-weight: 600;">➕ Aggiungi Punti</span>
                    </label>
                    <label style="flex: 1;">
                        <input type="radio" name="action_type" value="subtract">
                        <span style="color: #ef4444; font-weight: 600;">➖ Sottrai Punti</span>
                    </label>
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label for="adjust_points" style="display: block; margin-bottom: 8px; font-weight: 600;">Quantità Punti</label>
                <input type="number" id="adjust_points" name="points" min="1" required 
                       style="width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 16px;">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label for="adjust_note" style="display: block; margin-bottom: 8px; font-weight: 600;">Motivo (opzionale)</label>
                <textarea id="adjust_note" name="note" rows="3" 
                          style="width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px;"
                          placeholder="es: Bonus fedeltà, Correzione errore, ecc."></textarea>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" name="adjust_points" class="button button-primary" style="flex: 1;">
                    💾 Conferma
                </button>
                <button type="button" class="button" onclick="closeAdjustModal()" style="flex: 1;">
                    Annulla
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAdjustModal(userId, userName, currentBalance) {
    document.getElementById('adjust_user_id').value = userId;
    document.getElementById('adjust_user_name').textContent = userName;
    document.getElementById('adjust_user_balance').textContent = currentBalance.toLocaleString();
    document.getElementById('adjustPointsModal').style.display = 'block';
    document.getElementById('adjust_points').focus();
}

function closeAdjustModal() {
    document.getElementById('adjustPointsModal').style.display = 'none';
    document.getElementById('adjust_points').value = '';
    document.getElementById('adjust_note').value = '';
}

// Chiudi modal cliccando fuori
document.getElementById('adjustPointsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAdjustModal();
    }
});

// ESC per chiudere
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAdjustModal();
    }
});
</script>

<style>
.button-primary {
    background: #4f46e5 !important;
    border-color: #4338ca !important;
}
.button-primary:hover {
    background: #4338ca !important;
}
</style>
