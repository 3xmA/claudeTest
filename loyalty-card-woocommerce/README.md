# Loyalty Card System - WooCommerce Integration

Plugin addon per integrare il sistema Carta Fedeltà con WooCommerce.

## 📋 Requisiti

- WordPress 5.0+
- **Loyalty Card System** (plugin principale)
- **WooCommerce** 3.0+
- PHP 7.0+

## 🚀 Installazione

1. Assicurati che **Loyalty Card System** e **WooCommerce** siano installati e attivi
2. Carica la cartella `loyalty-card-woocommerce` in `/wp-content/plugins/`
3. Attiva il plugin dal pannello WordPress

## ⚙️ Configurazione

### 1. Impostazioni Generali

Vai in **Carta Fedeltà > WooCommerce** e configura:

**Punti Automatici:**
- ✅ Abilita/disabilita punti automatici per ordini
- Punti per euro (es: 1 punto per ogni euro speso)
- Importo minimo ordine
- Esclusioni: spedizione, tasse, ordini con coupon

**Coupon:**
- Scadenza coupon (default: 30 giorni)

**Notifiche:**
- Invia email/WhatsApp quando cliente guadagna punti

### 2. Configurare Prodotti come Premi

Per ogni prodotto WooCommerce che vuoi offrire come premio:

1. Vai su **Prodotti > Modifica prodotto**
2. Nella sidebar destra trovi il box **🎁 Carta Fedeltà - Premio**
3. Spunta **"Disponibile come premio fedeltà"**
4. Configura:
   - **Punti Richiesti**: quanti punti servono per riscattarlo
   - **Tipo Sconto**:
     - **Prodotto Gratuito**: cliente riceve prodotto gratis (100% sconto)
     - **Sconto Percentuale**: es. 20% di sconto
     - **Sconto Fisso**: es. 5€ di sconto
   - **Valore Sconto**: percentuale o importo fisso

5. Vedi anteprima in tempo reale
6. Salva prodotto

### 3. Visualizzare Premi nella Dashboard

I prodotti WooCommerce appaiono automaticamente nella dashboard loyalty esistente insieme ai premi tradizionali.

**Shortcode opzionale** per mostrare solo prodotti WooCommerce:
```
[loyalty_woo_rewards]
```

## 💡 Funzionalità

### 🛒 Punti Automatici per Ordini

Quando un cliente completa un ordine WooCommerce:

1. ✅ Sistema calcola punti automaticamente
2. ✅ Aggiunge punti al saldo cliente
3. ✅ Invia notifica email/WhatsApp
4. ✅ Aggiunge nota all'ordine

**Se ordine viene cancellato/rimborsato**: i punti vengono automaticamente rimossi.

### 🎁 Riscatto Prodotti come Premi

Il cliente può riscattare prodotti WooCommerce dalla dashboard loyalty:

1. Cliente vede prodotti disponibili in base ai suoi punti
2. Clicca "Riscatta"
3. Sistema:
   - Sottrae punti dal saldo
   - Genera coupon WooCommerce automatico
   - Invia coupon via email/WhatsApp
4. Cliente usa il coupon al checkout

### 🎫 Coupon Automatici

Quando un cliente riscatta un prodotto:

- Coupon **monouso** (può essere usato 1 sola volta)
- Limitato al **prodotto specifico** riscattato
- Scadenza configurabile (default 30 giorni)
- Solo per l'**email del cliente** che ha riscattato

**Tipi di sconto supportati:**
- Prodotto Gratuito (100%)
- Sconto Percentuale (es: 20%)
- Sconto Fisso (es: 5€)

## 📊 Statistiche

Nella pagina **Carta Fedeltà > WooCommerce** vedi:

- Prodotti disponibili come premi
- Coupon generati totali
- Punti assegnati da ordini (ultimi 30 giorni)

## 🔍 Esempio Flusso Completo

### Scenario: Negoziante offre "Caffè Premium" come premio

1. **Configurazione Prodotto** (Negoziante)
   - Modifica prodotto "Caffè Premium" (€10)
   - Abilita come premio fedeltà
   - Imposta 100 punti richiesti
   - Tipo: Sconto 50%
   - Salva

2. **Cliente Accumula Punti**
   - Cliente compra prodotti per €100
   - Sistema assegna 100 punti automaticamente
   - Cliente riceve email/WhatsApp: "Hai guadagnato 100 punti!"

3. **Cliente Riscatta**
   - Cliente va su dashboard loyalty
   - Vede "Caffè Premium - 100 punti - 50% sconto"
   - Clicca "Riscatta"
   - Sistema genera coupon `LOYALTY-ABC12345`

4. **Cliente Usa Coupon**
   - Cliente va su WooCommerce
   - Aggiunge "Caffè Premium" al carrello
   - Al checkout inserisce coupon `LOYALTY-ABC12345`
   - Prezzo: ~~€10~~ → **€5** ✅
   - Completa ordine

## 🔧 Configurazioni Avanzate

### Escludere Categorie/Prodotti

Usa i filtri WordPress per personalizzare il calcolo punti:

```php
// functions.php del tema
add_filter('loyalty_woo_calculate_order_points', function($points, $order, $total) {
    // Esempio: escludi categoria "Sale"
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if ($product && $product->is_on_sale()) {
            return 0; // Nessun punto per prodotti in saldo
        }
    }
    return $points;
}, 10, 3);
```

### Moltiplicatori per Categorie

```php
add_filter('loyalty_woo_calculate_order_points', function($points, $order, $total) {
    // Raddoppia punti per categoria "Premium"
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if ($product && has_term('premium', 'product_cat', $product->get_id())) {
            return $points * 2;
        }
    }
    return $points;
}, 10, 3);
```

## 🐛 Troubleshooting

### Prodotto non appare come premio

1. Verifica che il prodotto sia **Pubblicato**
2. Controlla che il checkbox **"Disponibile come premio"** sia spuntato
3. Verifica che i **punti richiesti** siano impostati

### Punti non assegnati automaticamente

1. Verifica in **Carta Fedeltà > WooCommerce** che punti automatici siano **abilitati**
2. Controlla che l'ordine sia nello stato **"Completato"**
3. Verifica che il cliente sia **registrato** (ordini guest non ricevono punti)
4. Controlla **importo minimo** configurato

### Coupon non funziona

1. Verifica che il coupon non sia **scaduto**
2. Controlla che il cliente stia comprando il **prodotto corretto**
3. Verifica che il coupon non sia già stato **utilizzato**

## 📝 Note Tecniche

### Compatibilità

- Sistema ibrido: premi tradizionali + prodotti WooCommerce coesistono
- Nessuna modifica al database del plugin principale
- Hook WooCommerce standard per massima compatibilità

### Performance

- Query ottimizzate con meta_query
- Caching automatico WooCommerce
- Nessun impatto su checkout/carrello

### Sicurezza

- Verifica permessi su tutte le azioni admin
- Coupon limitati per email utente
- Nonce per tutti i form

## 🆘 Supporto

Per problemi o domande, verifica:
1. Plugin principali attivi (Loyalty Card System + WooCommerce)
2. Log WordPress (`wp-content/debug.log` se `WP_DEBUG_LOG` abilitato)
3. Versioni minime richieste

## 📄 Licenza

GPL v2 or later

## 🎉 Credits

Creato come addon per **Loyalty Card System**
