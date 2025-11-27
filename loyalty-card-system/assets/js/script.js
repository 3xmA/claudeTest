/**
 * Loyalty Card System - JavaScript
 */

(function($) {
    'use strict';
    
    const LoyaltyCard = {
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            // Click su bottone riscatta premio
            $(document).on('click', '.btn-redeem', this.handleRedeemClick);
            
            // Chiudi modal
            $(document).on('click', '.modal-close, .loyalty-modal', function(e) {
                if (e.target === this) {
                    $('.loyalty-modal').fadeOut();
                }
            });
            
            // Conferma riscatto
            $(document).on('click', '#confirmRedeem', this.confirmRedeem);
        },
        
        handleRedeemClick: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const rewardId = $btn.data('reward-id');
            
            // Ottieni info premio
            const $rewardCard = $btn.closest('.reward-card');
            const rewardName = $rewardCard.find('h4').text();
            const rewardPoints = $rewardCard.find('.reward-points').text();
            
            // Mostra modal di conferma
            const modalHtml = `
                <div class="modal-reward-info">
                    <div class="reward-icon">🎁</div>
                    <h4>Vuoi riscattare questo premio?</h4>
                    <div class="reward-details">
                        <p class="reward-name">${rewardName}</p>
                        <p class="reward-cost">Costo: <strong>${rewardPoints}</strong></p>
                    </div>
                    <p class="warning-text">⚠️ I punti verranno sottratti dal tuo saldo</p>
                    <div class="modal-actions">
                        <button id="confirmRedeem" class="btn-confirm" data-reward-id="${rewardId}">
                            Conferma Riscatto
                        </button>
                        <button class="btn-cancel modal-close">Annulla</button>
                    </div>
                </div>
            `;
            
            $('#modalBody').html(modalHtml);
            $('#redeemModal').fadeIn();
        },
        
        confirmRedeem: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const rewardId = $btn.data('reward-id');
            
            // Disabilita bottone
            $btn.prop('disabled', true).text('Riscatto in corso...');
            
            // Chiamata API
            $.ajax({
                url: loyaltyAjax.rest_url + 'redeem',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', loyaltyAjax.nonce);
                },
                data: JSON.stringify({
                    reward_id: rewardId
                }),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success) {
                        LoyaltyCard.showSuccessModal(response);
                    } else {
                        LoyaltyCard.showErrorModal(response.message || 'Errore nel riscatto');
                    }
                },
                error: function(xhr) {
                    let errorMsg = 'Errore nel riscatto del premio';
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    
                    LoyaltyCard.showErrorModal(errorMsg);
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Conferma Riscatto');
                }
            });
        },
        
        showSuccessModal: function(response) {
            const qrUrl = `https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=LOYALTY-REWARD-${response.redemption_code}`;
            
            const modalHtml = `
                <div class="modal-success">
                    <div class="success-icon">✅</div>
                    <h4>Premio Riscattato!</h4>
                    <div class="reward-qr-display">
                        <p>Mostra questo QR al negoziante:</p>
                        <img src="${qrUrl}" alt="QR Premio" class="modal-qr">
                        <p class="redemption-code">
                            Codice: <strong>${response.redemption_code}</strong>
                        </p>
                    </div>
                    <div class="new-balance">
                        <p>Nuovo saldo: <strong>${response.new_balance} punti</strong></p>
                    </div>
                    <button class="btn-close-success modal-close">Chiudi</button>
                    <p class="info-text">💡 Troverai il premio nella sezione "I Tuoi Premi"</p>
                </div>
            `;
            
            $('#modalBody').html(modalHtml);
            
            // Ricarica pagina dopo 3 secondi (opzionale)
            setTimeout(function() {
                location.reload();
            }, 3000);
        },
        
        showErrorModal: function(message) {
            const modalHtml = `
                <div class="modal-error">
                    <div class="error-icon">❌</div>
                    <h4>Errore</h4>
                    <p>${message}</p>
                    <button class="btn-close-error modal-close">Chiudi</button>
                </div>
            `;
            
            $('#modalBody').html(modalHtml);
        }
    };
    
    // Inizializza quando il DOM è pronto
    $(document).ready(function() {
        LoyaltyCard.init();
    });
    
})(jQuery);

// Stili inline per modal (da aggiungere al CSS principale se necessario)
const modalStyles = `
<style>
.modal-reward-info,
.modal-success,
.modal-error {
    text-align: center;
}

.reward-icon,
.success-icon,
.error-icon {
    font-size: 64px;
    margin-bottom: 20px;
}

.modal-reward-info h4,
.modal-success h4,
.modal-error h4 {
    margin: 0 0 20px 0;
    font-size: 24px;
    color: #1f2937;
}

.reward-details {
    background: #f9fafb;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 15px;
}

.reward-name {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 10px;
}

.reward-cost {
    font-size: 16px;
    color: #6b7280;
}

.warning-text {
    font-size: 13px;
    color: #f59e0b;
    margin-bottom: 20px;
}

.modal-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
}

.btn-confirm,
.btn-cancel,
.btn-close-success,
.btn-close-error {
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-confirm {
    background: #10b981;
    color: white;
}

.btn-confirm:hover {
    background: #059669;
}

.btn-confirm:disabled {
    background: #9ca3af;
    cursor: not-allowed;
}

.btn-cancel {
    background: #e5e7eb;
    color: #1f2937;
}

.btn-cancel:hover {
    background: #d1d5db;
}

.reward-qr-display {
    margin: 20px 0;
}

.modal-qr {
    max-width: 200px;
    margin: 15px auto;
    display: block;
}

.redemption-code {
    font-size: 14px;
    color: #6b7280;
    margin-top: 10px;
}

.redemption-code strong {
    color: #1f2937;
    font-size: 16px;
}

.new-balance {
    background: #f0fdf4;
    padding: 15px;
    border-radius: 10px;
    margin: 20px 0;
}

.new-balance p {
    margin: 0;
    font-size: 16px;
}

.btn-close-success,
.btn-close-error {
    width: 100%;
    margin-top: 10px;
}

.btn-close-success {
    background: #10b981;
    color: white;
}

.btn-close-error {
    background: #ef4444;
    color: white;
}

.info-text {
    font-size: 12px;
    color: #6b7280;
    margin-top: 15px;
}
</style>
`;
