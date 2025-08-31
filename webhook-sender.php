<?php
// Załaduje środowisko WordPressa
require_once( './wp-load.php' );

echo '<h1>Symulator Webhooka imoje</h1>';

// KONFIGURACJA
$target_url = get_site_url() . '/wp-json/msp/v1/webhook';
$secret_key = 'tajny_klucz_do_podpisu_webhooka_123';

// Przykładowy payload, wzorowany na dokumentacji imoje
$payload_data = [
    'orderId'       => 75,
    'transactionId' => 'TRN-' . time(),
    'status'        => 'settled',
    'amount'        => 5000, // Kwota w groszach (50.00 PLN)
    'currency'      => 'PLN',
];

// Konwertuje tablicę PHP na string JSON
$payload_body = json_encode( $payload_data );

// GENEROWANIE PODPISU
$signature = hash_hmac( 'sha256', $payload_body, $secret_key );

// PRZYGOTOWANIE ŻĄDANIA
$args = [
    'body'    => $payload_body,
    'headers' => [
        'Content-Type'      => 'application/json',
        'X-Imoje-Signature' => $signature
    ],
    'timeout' => 15,
];

// WYŚWIETLA DANE DO DEBUGOWANIA
echo '<h2>Dane wysyłane:</h2>';
echo '<p><strong>URL docelowy:</strong> ' . esc_url( $target_url ) . '</p>';
echo '<p><strong>Nagłówek z podpisem:</strong> X-Imoje-Signature</p>';
echo '<p><strong>Wygenerowany podpis:</strong><br><textarea rows="2" cols="80" readonly>' . esc_textarea( $signature ) . '</textarea></p>';
echo '<p><strong>Payload (body):</strong><br><textarea rows="5" cols="80" readonly>' . esc_textarea( $payload_body ) . '</textarea></p>';

// WYSYŁA ŻĄDANIE
$response = wp_remote_post( $target_url, $args );

// ANALIZA ODPOWIEDZI
echo '<h2>Odpowiedź z serwera:</h2>';
if ( is_wp_error( $response ) ) {
    $error_message = $response->get_error_message();
    echo '<p style="color:red;"><strong>Błąd:</strong> ' . esc_html( $error_message ) . '</p>';
} else {
    $response_code = wp_remote_retrieve_response_code( $response );
    $response_body = wp_remote_retrieve_body( $response );
    $color = ( $response_code >= 200 && $response_code < 300 ) ? 'green' : 'red';

    echo '<p><strong>Kod statusu HTTP:</strong> <span style="color:' . $color . ';">' . esc_html( $response_code ) . '</span></p>';
    echo '<p><strong>Treść odpowiedzi:</strong><br><textarea rows="4" cols="80" readonly>' . esc_textarea( $response_body ) . '</textarea></p>';
}