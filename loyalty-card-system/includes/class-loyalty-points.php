<?php
/**
 * Classe per gestione punti
 */
class Loyalty_Points {
    
    /**
     * Calcola punti basati sull'importo
     */
    public static function calculate_points($amount) {
        // Configurazione: 1€ = 1 punto (puoi personalizzare)
        $points_per_euro = get_option('loyalty_points_per_euro', 1);
        return floor($amount * $points_per_euro);
    }
    
    /**
     * Ottieni storico transazioni utente
     */
    public static function get_user_transactions($user_id, $limit = 50) {
        global $wpdb;
        $table = $wpdb->prefix . 'loyalty_transactions';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
            $user_id,
            $limit
        ));
    }
    
    /**
     * Ottieni premi riscattati dall'utente
     */
    public static function get_user_redemptions($user_id, $status = 'all') {
        global $wpdb;
        $table_redemptions = $wpdb->prefix . 'loyalty_redemptions';
        $table_rewards = $wpdb->prefix . 'loyalty_rewards';
        
        $sql = "SELECT r.*, rw.name as reward_name, rw.description as reward_description 
                FROM $table_redemptions r 
                LEFT JOIN $table_rewards rw ON r.reward_id = rw.id 
                WHERE r.user_id = %d";
        
        if ($status !== 'all') {
            $sql .= $wpdb->prepare(" AND r.status = %s", $status);
        }
        
        $sql .= " ORDER BY r.redeemed_at DESC";
        
        return $wpdb->get_results($wpdb->prepare($sql, $user_id));
    }
    
    /**
     * Controlla se utente può riscattare un premio
     */
    public static function can_redeem($user_id, $reward_id) {
        global $wpdb;
        
        // Ottieni info premio
        $table_rewards = $wpdb->prefix . 'loyalty_rewards';
        $reward = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_rewards WHERE id = %d AND is_active = 1",
            $reward_id
        ));
        
        if (!$reward) {
            return false;
        }
        
        // Ottieni saldo
        $table_transactions = $wpdb->prefix . 'loyalty_transactions';
        $balance = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(points) FROM $table_transactions WHERE user_id = %d",
            $user_id
        ));
        
        return $balance >= $reward->points_required;
    }
    
    /**
     * Ottieni statistiche utente
     */
    public static function get_user_stats($user_id) {
        global $wpdb;
        $table_transactions = $wpdb->prefix . 'loyalty_transactions';
        $table_redemptions = $wpdb->prefix . 'loyalty_redemptions';
        
        // Punti totali guadagnati
        $total_earned = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(points) FROM $table_transactions WHERE user_id = %d AND transaction_type = 'earn'",
            $user_id
        ));
        
        // Punti totali spesi
        $total_spent = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(points_used) FROM $table_redemptions WHERE user_id = %d",
            $user_id
        ));
        
        // Numero transazioni
        $transaction_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_transactions WHERE user_id = %d AND transaction_type = 'earn'",
            $user_id
        ));
        
        // Numero premi riscattati
        $rewards_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_redemptions WHERE user_id = %d",
            $user_id
        ));
        
        // Saldo corrente
        $balance = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(points) FROM $table_transactions WHERE user_id = %d",
            $user_id
        ));
        
        return array(
            'balance' => intval($balance),
            'total_earned' => intval($total_earned),
            'total_spent' => intval($total_spent),
            'transaction_count' => intval($transaction_count),
            'rewards_count' => intval($rewards_count),
        );
    }
}
