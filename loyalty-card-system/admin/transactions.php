<?php
/**
 * Admin Page - Gestione Transazioni
 */

if (!defined('ABSPATH')) exit;

global $wpdb;

// Gestione filtri e paginazione
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($paged - 1) * $per_page;

$filter_user = isset($_GET['filter_user']) ? intval($_GET['filter_user']) : 0;
$filter_type = isset($_GET['filter_type']) ? sanitize_text_field($_GET['filter_type']) : '';
$filter_date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$filter_date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Costruisci query
$table_transactions = $wpdb->prefix . 'loyalty_transactions';
$where = array('1=1');
$where_values = array();

if ($filter_user > 0) {
    $where[] = 't.user_id = %d';
    $where_values[] = $filter_user;
}

if ($filter_type) {
    $where[] = 't.transaction_type = %s';
    $where_values[] = $filter_type;
}

if ($filter_date_from) {
    $where[] = 'DATE(t.created_at) >= %s';
    $where_values[] = $filter_date_from;
}

if ($filter_date_to) {
    $where[] = 'DATE(t.created_at) <= %s';
    $where_values[] = $filter_date_to;
}

if ($search) {
    $where[] = '(u.display_name LIKE %s OR u.user_email LIKE %s OR t.note LIKE %s)';
    $search_term = '%' . $wpdb->esc_like($search) . '%';
    $where_values[] = $search_term;
    $where_values[] = $search_term;
    $where_values[] = $search_term;
}

$where_clause = implode(' AND ', $where);

// Conta totale
$count_query = "SELECT COUNT(*) FROM $table_transactions t 
                LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID 
                WHERE $where_clause";

if (!empty($where_values)) {
    $total_items = $wpdb->get_var($wpdb->prepare($count_query, $where_values));
} else {
    $total_items = $wpdb->get_var($count_query);
}

$total_pages = ceil($total_items / $per_page);

// Query transazioni
$query = "SELECT t.*, u.display_name, u.user_email 
          FROM $table_transactions t 
          LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID 
          WHERE $where_clause 
          ORDER BY t.created_at DESC 
          LIMIT %d OFFSET %d";

$where_values[] = $per_page;
$where_values[] = $offset;

$transactions = $wpdb->get_results($wpdb->prepare($query, $where_values));

// Statistiche filtrate
$stats_query = "SELECT 
                COUNT(*) as total_transactions,
                SUM(CASE WHEN transaction_type = 'earn' THEN points ELSE 0 END) as total_earned,
                SUM(CASE WHEN transaction_type = 'redeem' THEN ABS(points) ELSE 0 END) as total_redeemed,
                SUM(CASE WHEN transaction_type = 'earn' THEN amount ELSE 0 END) as total_amount
                FROM $table_transactions t 
                LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID 
                WHERE $where_clause";

$stats_values = array_slice($where_values, 0, -2); // Rimuovi limit e offset

if (!empty($stats_values)) {
    $stats = $wpdb->get_row($wpdb->prepare($stats_query, $stats_values));
} else {
    $stats = $wpdb->get_row($stats_query);
}

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=transazioni_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Header CSV
    fputcsv($output, array('ID', 'Data', 'Utente', 'Email', 'Tipo', 'Punti', 'Importo', 'Nota'));
    
    // Dati (prendi tutte le transazioni senza limit)
    $export_query = "SELECT t.*, u.display_name, u.user_email 
                     FROM $table_transactions t 
                     LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID 
                     WHERE $where_clause 
                     ORDER BY t.created_at DESC";
    
    $export_values = array_slice($where_values, 0, -2);
    
    if (!empty($export_values)) {
        $export_data = $wpdb->get_results($wpdb->prepare($export_query, $export_values));
    } else {
        $export_data = $wpdb->get_results($export_query);
    }
    
    foreach ($export_data as $row) {
        fputcsv($output, array(
            $row->id,
            $row->created_at,
            $row->display_name,
            $row->user_email,
            $row->transaction_type,
            $row->points,
            $row->amount,
            $row->note
        ));
    }
    
    fclose($output);
    exit;
}

?>

<div class="wrap">
    <h1 class="wp-heading-inline">📝 Transazioni</h1>
    <a href="<?php echo admin_url('admin.php?page=loyalty-transactions&export=csv' . 
        ($filter_user ? '&filter_user=' . $filter_user : '') . 
        ($filter_type ? '&filter_type=' . $filter_type : '') . 
        ($filter_date_from ? '&date_from=' . $filter_date_from : '') . 
        ($filter_date_to ? '&date_to=' . $filter_date_to : '') .
        ($search ? '&s=' . urlencode($search) : '')); ?>" 
       class="page-title-action">📥 Esporta CSV</a>
    <hr class="wp-header-end">
    
    <!-- Statistiche Filtrate -->
    <?php if ($stats) : ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
        <div style="background: #fff; padding: 15px; border-left: 4px solid #4f46e5; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 5px 0; font-size: 14px; color: #6b7280;">Transazioni</h3>
            <p style="margin: 0; font-size: 24px; font-weight: 700; color: #4f46e5;"><?php echo number_format($stats->total_transactions); ?></p>
        </div>
        <div style="background: #fff; padding: 15px; border-left: 4px solid #10b981; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 5px 0; font-size: 14px; color: #6b7280;">Punti Guadagnati</h3>
            <p style="margin: 0; font-size: 24px; font-weight: 700; color: #10b981;">+<?php echo number_format($stats->total_earned); ?></p>
        </div>
        <div style="background: #fff; padding: 15px; border-left: 4px solid #f59e0b; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 5px 0; font-size: 14px; color: #6b7280;">Punti Riscattati</h3>
            <p style="margin: 0; font-size: 24px; font-weight: 700; color: #f59e0b;">-<?php echo number_format($stats->total_redeemed); ?></p>
        </div>
        <div style="background: #fff; padding: 15px; border-left: 4px solid #7c3aed; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 5px 0; font-size: 14px; color: #6b7280;">Fatturato Totale</h3>
            <p style="margin: 0; font-size: 24px; font-weight: 700; color: #7c3aed;">€<?php echo number_format($stats->total_amount, 2); ?></p>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Filtri -->
    <div class="tablenav top">
        <form method="get" action="" style="display: inline-flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <input type="hidden" name="page" value="loyalty-transactions">
            
            <input type="text" name="s" placeholder="🔍 Cerca utente o nota..." 
                   value="<?php echo esc_attr($search); ?>" 
                   style="width: 250px; padding: 5px 10px;">
            
            <select name="filter_type" style="padding: 5px 10px;">
                <option value="">Tutti i tipi</option>
                <option value="earn" <?php selected($filter_type, 'earn'); ?>>Guadagno</option>
                <option value="redeem" <?php selected($filter_type, 'redeem'); ?>>Riscatto</option>
            </select>
            
            <input type="date" name="date_from" value="<?php echo esc_attr($filter_date_from); ?>" 
                   placeholder="Da data" style="padding: 5px 10px;">
            
            <input type="date" name="date_to" value="<?php echo esc_attr($filter_date_to); ?>" 
                   placeholder="A data" style="padding: 5px 10px;">
            
            <button type="submit" class="button">Filtra</button>
            
            <?php if ($search || $filter_type || $filter_date_from || $filter_date_to || $filter_user) : ?>
                <a href="<?php echo admin_url('admin.php?page=loyalty-transactions'); ?>" class="button">Reset Filtri</a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Tabella Transazioni -->
    <?php if (empty($transactions)) : ?>
        <div style="background: #fff; padding: 40px; text-align: center; border: 1px solid #ddd; margin-top: 20px;">
            <p style="font-size: 18px; color: #6b7280;">📭 Nessuna transazione trovata</p>
        </div>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th style="width: 150px;">Data/Ora</th>
                    <th>Utente</th>
                    <th style="width: 100px;">Tipo</th>
                    <th style="width: 100px; text-align: right;">Punti</th>
                    <th style="width: 100px; text-align: right;">Importo</th>
                    <th>Nota</th>
                    <th style="width: 80px;">Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction) : ?>
                    <tr>
                        <td><strong>#<?php echo $transaction->id; ?></strong></td>
                        <td><?php echo date_i18n('d/m/Y H:i', strtotime($transaction->created_at)); ?></td>
                        <td>
                            <strong><?php echo esc_html($transaction->display_name); ?></strong><br>
                            <small style="color: #6b7280;"><?php echo esc_html($transaction->user_email); ?></small><br>
                            <a href="<?php echo admin_url('admin.php?page=loyalty-transactions&filter_user=' . $transaction->user_id); ?>" 
                               style="font-size: 12px;">Vedi tutte transazioni →</a>
                        </td>
                        <td>
                            <?php if ($transaction->transaction_type === 'earn') : ?>
                                <span style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                    ⬆️ Guadagno
                                </span>
                            <?php else : ?>
                                <span style="background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                    ⬇️ Riscatto
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <strong style="color: <?php echo $transaction->points > 0 ? '#10b981' : '#ef4444'; ?>; font-size: 16px;">
                                <?php echo $transaction->points > 0 ? '+' : ''; ?><?php echo number_format($transaction->points); ?>
                            </strong>
                        </td>
                        <td style="text-align: right;">
                            <?php if ($transaction->amount) : ?>
                                <strong>€<?php echo number_format($transaction->amount, 2); ?></strong>
                            <?php else : ?>
                                <span style="color: #9ca3af;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $transaction->note ? esc_html($transaction->note) : '<span style="color: #9ca3af;">-</span>'; ?>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('user-edit.php?user_id=' . $transaction->user_id); ?>" 
                               class="button button-small" title="Vedi utente">
                                👤
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
                    <span class="displaying-num"><?php echo number_format($total_items); ?> elementi</span>
                    <span class="pagination-links">
                        <?php
                        $base_url = admin_url('admin.php?page=loyalty-transactions');
                        if ($search) $base_url .= '&s=' . urlencode($search);
                        if ($filter_type) $base_url .= '&filter_type=' . $filter_type;
                        if ($filter_date_from) $base_url .= '&date_from=' . $filter_date_from;
                        if ($filter_date_to) $base_url .= '&date_to=' . $filter_date_to;
                        if ($filter_user) $base_url .= '&filter_user=' . $filter_user;
                        
                        // Prima pagina
                        if ($paged > 1) {
                            echo '<a class="button" href="' . $base_url . '&paged=1">«</a> ';
                            echo '<a class="button" href="' . $base_url . '&paged=' . ($paged - 1) . '">‹</a> ';
                        }
                        
                        // Numero pagina
                        echo '<span class="paging-input">
                                <span class="tablenav-paging-text">
                                    ' . $paged . ' di <span class="total-pages">' . $total_pages . '</span>
                                </span>
                              </span>';
                        
                        // Ultima pagina
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
    <div style="background: #f0fdf4; border-left: 4px solid #10b981; padding: 15px; margin-top: 20px;">
        <h3 style="margin-top: 0;">💡 Info Utili</h3>
        <ul style="margin: 0;">
            <li><strong>Guadagno</strong>: punti aggiunti quando il cliente fa un acquisto</li>
            <li><strong>Riscatto</strong>: punti sottratti quando il cliente riscatta un premio</li>
            <li>Usa i filtri per analizzare periodi specifici o singoli utenti</li>
            <li>Esporta in CSV per analisi esterne (Excel, Google Sheets, ecc.)</li>
        </ul>
    </div>
</div>

<style>
.wp-list-table tbody tr:hover {
    background-color: #f9fafb;
}
</style>
