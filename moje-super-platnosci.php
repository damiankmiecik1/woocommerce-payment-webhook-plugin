<?php
/**
 * Plugin Name: Moje Super Płatności
 * Description: Wtyczka-portfolio demonstrująca integrację API (webhook) z WooCommerce.
 * Version: 1.0
 * Author: Damian Kmiecik
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Zabezpieczenie: zakończ, jeśli plik jest wywoływany bezpośrednio.
}

/**
 * Sekretny klucz używany do weryfikacji podpisów HMAC webhooków.
 * WAŻNE: W prawdziwym projekcie ten klucz powinien być unikalny i skomplikowany.
 */
define( 'MSP_WEBHOOK_SECRET', 'tajny_klucz_do_podpisu_webhooka_123' );

/**
 * Przestrzeń nazw (namespace) dla tego niestandardowego API.
 */
define( 'MSP_API_NAMESPACE', 'msp/v1' );

/**
 * Ścieżka (route) dla endpointu webhooka.
 * Pełny URL to: /wp-json/msp/v1/webhook
 */
define( 'MSP_API_ROUTE', '/webhook' );

/**
 * Dodaje niestandardowe pole do formularza zamówienia.
 * Podpięte do: woocommerce_checkout_fields
 */
function msp_dodaj_pole_referencyjne( $fields ) {
    $fields['billing']['billing_msp_reference'] = [
        'type'        => 'text',
        'label'       => 'Numer referencyjny (MSP)',
        'placeholder' => 'Wpisz numer referencyjny',
        'required'    => false,
        'class'       => ['form-row-wide'],
        'priority'    => 120,
    ];
    return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'msp_dodaj_pole_referencyjne' );

/**
 * Zapisuje wartość z niestandardowego pola do metadanych zamówienia.
 * Podpięte do: woocommerce_checkout_create_order
 */
function msp_zapisz_wartosc_pola_referencyjnego( $order, $data ) {
    if ( isset( $_POST['billing_msp_reference'] ) && ! empty( $_POST['billing_msp_reference'] ) ) {
        $reference_value = sanitize_text_field( $_POST['billing_msp_reference'] );
        $order->update_meta_data( 'msp_reference_number', $reference_value );
        $order->save();
    }
}
add_action( 'woocommerce_checkout_create_order', 'msp_zapisz_wartosc_pola_referencyjnego', 10, 2 );

/**
 * Wyświetla zapisaną wartość na stronie edycji zamówienia w panelu admina.
 * Podpięte do: woocommerce_admin_order_data_after_billing_address
 */
function msp_wyswietl_wartosc_pola_w_adminie( $order ) {
    $reference_value = $order->get_meta( 'msp_reference_number' );
    if ( $reference_value ) {
        echo '<p><strong>Numer Referencyjny (MSP):</strong> ' . esc_html( $reference_value ) . '</p>';
    }
}
add_action( 'woocommerce_admin_order_data_after_billing_address', 'msp_wyswietl_wartosc_pola_w_adminie' );

/**
 * Rejestruje niestandardowy endpoint REST API.
 * Podpięte do: rest_api_init
 */
function msp_register_webhook_endpoint() {
    register_rest_route(
        MSP_API_NAMESPACE, // Używa stałej zdefiniowanej na górze
        MSP_API_ROUTE,     // Używa stałej zdefiniowanej na górze
        [
            'methods'             => 'POST',
            'callback'            => 'msp_handle_webhook_request',
            'permission_callback' => '__return_true',
        ]
    );
}
add_action( 'rest_api_init', 'msp_register_webhook_endpoint' );

/**
 * Główna funkcja obsługująca przychodzące żądanie webhooka.
 * Wykonuje weryfikację podpisu, a następnie aktualizuje zamówienie.
 */
function msp_handle_webhook_request( WP_REST_Request $request ) {

    $received_signature = $request->get_header( 'x-msp-signature' );
    $payload = $request->get_body();

    if ( ! $received_signature ) {
        error_log( 'Błąd webhooka: Brak nagłówka X-Msp-Signature.' );
        return new WP_REST_Response( [ 'status' => 'error', 'message' => 'Missing signature.' ], 401 );
    }

    // Generuje oczekiwany podpis używając sekretnego klucza zdefiniowanego jako stała.
    $expected_signature = hash_hmac( 'sha256', $payload, MSP_WEBHOOK_SECRET );

    if ( ! hash_equals( $expected_signature, $received_signature ) ) {
        error_log( 'Błąd webhooka: Nieprawidłowy podpis.' );
        return new WP_REST_Response( [ 'status' => 'error', 'message' => 'Invalid signature.' ], 403 );
    }

    $data = json_decode( $payload, true );

    if ( json_last_error() !== JSON_ERROR_NONE || empty( $data['order_id'] ) ) {
        error_log( 'Błąd webhooka: Nieprawidłowy JSON lub brak order_id.' );
        return new WP_REST_Response( [ 'status' => 'error', 'message' => 'Invalid JSON or missing order_id.' ], 400 );
    }

    $order_id = absint( $data['order_id'] );
    $order = wc_get_order( $order_id );

    if ( ! $order ) {
        error_log( 'Błąd webhooka: Zamówienie o ID ' . $order_id . ' nie zostało znalezione.' );
        return new WP_REST_Response( [ 'status' => 'error', 'message' => 'Order not found.' ], 404 );
    }

    $order->update_status( 'processing', 'Płatność została pomyślnie zweryfikowana przez webhook.' );
    
    $transaction_id = isset( $data['transaction_id'] ) ? sanitize_text_field( $data['transaction_id'] ) : 'N/A';
    $order->add_order_note( 'Otrzymano potwierdzenie płatności przez API. ID transakcji zewnętrznej: ' . $transaction_id );

    $order->save();

    error_log( 'Sukces: Zamówienie ' . $order_id . ' zostało zaktualizowane do statusu "processing".' );

    $response_data = [
        'status'  => 'success',
        'message' => 'Order ' . $order_id . ' updated successfully.',
    ];
    return new WP_REST_Response( $response_data, 200 );
}
