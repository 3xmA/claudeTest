<?php
/**
 * Tab: Template Email
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variabili disponibili
$variables = array(
    '{user_name}' => 'Nome utente',
    '{user_email}' => 'Email utente',
    '{points}' => 'Punti guadagnati/usati',
    '{new_balance}' => 'Saldo aggiornato',
    '{amount}' => 'Importo acquisto',
    '{reward_name}' => 'Nome premio',
    '{redemption_code}' => 'Codice riscatto',
    '{note}' => 'Nota transazione',
    '{site_name}' => 'Nome sito',
    '{site_url}' => 'URL sito',
);
?>

<form method="post" action="">
    <?php wp_nonce_field('loyalty_notifications'); ?>
    <input type="hidden" name="tab" value="email">
    
    <div style="background: #fff4cc; padding: 15px; margin: 20px 0; border-left: 4px solid #f59e0b; border-radius: 5px;">
        <h3 style="margin-top: 0;">📝 Variabili Disponibili</h3>
        <p style="margin-bottom: 10px;">Usa queste variabili nei tuoi template (oggetto e corpo):</p>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; font-family: monospace; font-size: 13px;">
            <?php foreach ($variables as $var => $desc) : ?>
                <div style="background: white; padding: 8px; border-radius: 4px;">
                    <code style="color: #d97706; font-weight: bold;"><?php echo $var; ?></code><br>
                    <small style="color: #6b7280;"><?php echo $desc; ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Template: Punti Aggiunti -->
    <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2>📧 Evento: Punti Aggiunti</h2>
        
        <table class="form-table">
            <tr>
                <th style="width: 200px;"><label>Attivo</label></th>
                <td>
                    <label>
                        <input type="checkbox" name="loyalty_email_points_added_enabled" value="1" <?php checked(get_option('loyalty_email_points_added_enabled'), true); ?>>
                        Invia email quando un cliente guadagna punti
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="email_points_added_subject">Oggetto Email</label></th>
                <td>
                    <input type="text" name="loyalty_email_points_added_subject" id="email_points_added_subject" value="<?php echo esc_attr(get_option('loyalty_email_points_added_subject', '🎉 Hai guadagnato {points} punti!')); ?>" class="large-text" style="font-size: 14px; padding: 8px;">
                </td>
            </tr>
            <tr>
                <th><label for="email_points_added_body">Corpo Email (HTML)</label></th>
                <td>
                    <?php
                    wp_editor(
                        get_option('loyalty_email_points_added_body', ''),
                        'loyalty_email_points_added_body',
                        array(
                            'textarea_name' => 'loyalty_email_points_added_body',
                            'textarea_rows' => 15,
                            'media_buttons' => false,
                            'teeny' => false,
                            'tinymce' => array(
                                'toolbar1' => 'formatselect,bold,italic,underline,forecolor,backcolor,alignleft,aligncenter,alignright,bullist,numlist,link,removeformat,code',
                            ),
                        )
                    );
                    ?>
                    <p class="description">Usa l'editor per creare email HTML belle e professionali</p>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Template: Premio Riscattato -->
    <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2>🎁 Evento: Premio Riscattato</h2>
        
        <table class="form-table">
            <tr>
                <th style="width: 200px;"><label>Attivo</label></th>
                <td>
                    <label>
                        <input type="checkbox" name="loyalty_email_reward_redeemed_enabled" value="1" <?php checked(get_option('loyalty_email_reward_redeemed_enabled'), true); ?>>
                        Invia email quando un cliente riscatta un premio
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="email_reward_redeemed_subject">Oggetto Email</label></th>
                <td>
                    <input type="text" name="loyalty_email_reward_redeemed_subject" id="email_reward_redeemed_subject" value="<?php echo esc_attr(get_option('loyalty_email_reward_redeemed_subject', '🎁 Premio Riscattato con Successo!')); ?>" class="large-text" style="font-size: 14px; padding: 8px;">
                </td>
            </tr>
            <tr>
                <th><label for="email_reward_redeemed_body">Corpo Email (HTML)</label></th>
                <td>
                    <?php
                    wp_editor(
                        get_option('loyalty_email_reward_redeemed_body', ''),
                        'loyalty_email_reward_redeemed_body',
                        array(
                            'textarea_name' => 'loyalty_email_reward_redeemed_body',
                            'textarea_rows' => 15,
                            'media_buttons' => false,
                            'teeny' => false,
                            'tinymce' => array(
                                'toolbar1' => 'formatselect,bold,italic,underline,forecolor,backcolor,alignleft,aligncenter,alignright,bullist,numlist,link,removeformat,code',
                            ),
                        )
                    );
                    ?>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Template: Soglia Raggiunta -->
    <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2>🔥 Evento: Soglia Punti Raggiunta</h2>
        
        <table class="form-table">
            <tr>
                <th style="width: 200px;"><label>Attivo</label></th>
                <td>
                    <label>
                        <input type="checkbox" name="loyalty_email_threshold_reached_enabled" value="1" <?php checked(get_option('loyalty_email_threshold_reached_enabled'), true); ?>>
                        Invia email quando un cliente raggiunge 100+ punti
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="email_threshold_reached_subject">Oggetto Email</label></th>
                <td>
                    <input type="text" name="loyalty_email_threshold_reached_subject" id="email_threshold_reached_subject" value="<?php echo esc_attr(get_option('loyalty_email_threshold_reached_subject', '🔥 Puoi riscattare un premio!')); ?>" class="large-text" style="font-size: 14px; padding: 8px;">
                </td>
            </tr>
            <tr>
                <th><label for="email_threshold_reached_body">Corpo Email (HTML)</label></th>
                <td>
                    <?php
                    wp_editor(
                        get_option('loyalty_email_threshold_reached_body', ''),
                        'loyalty_email_threshold_reached_body',
                        array(
                            'textarea_name' => 'loyalty_email_threshold_reached_body',
                            'textarea_rows' => 15,
                            'media_buttons' => false,
                            'teeny' => false,
                            'tinymce' => array(
                                'toolbar1' => 'formatselect,bold,italic,underline,forecolor,backcolor,alignleft,aligncenter,alignright,bullist,numlist,link,removeformat,code',
                            ),
                        )
                    );
                    ?>
                </td>
            </tr>
        </table>
    </div>
    
    <?php submit_button('💾 Salva Template Email', 'primary', 'loyalty_save_notifications'); ?>
</form>
