# Wtyczka Portfolio: Integracja Webhook z WooCommerce

**Moje Super Płatności** to prosta, ale w pełni funkcjonalna wtyczka do WordPress i WooCommerce, stworzona jako projekt-portfolio. Jej głównym celem jest demonstracja praktycznej umiejętności integracji z zewnętrznym API poprzez bezpieczną obsługę webhooków, co jest kluczowym zadaniem w świecie e-commerce.

---

## Cel Projektu

Projekt został zrealizowany w celu nauki i demonstracji następujących kluczowych kompetencji:
*   Zrozumienie mechanizmów rozszerzania funkcjonalności WordPress i WooCommerce za pomocą hooków (akcji i filtrów).
*   Umiejętność tworzenia i zabezpieczania niestandardowych endpointów REST API.
*   Praktyczne zastosowanie PHP do interakcji z danymi WooCommerce (zamówienia, metadane).
*   Zrozumienie cyklu życia żądania-odpowiedzi HTTP i pracy z formatem JSON.

---

## Główne Funkcjonalności

Wtyczka składa się z dwóch głównych modułów:

1.  **Modyfikacja Procesu Zamówienia:**
    *   Dodaje niestandardowe pole tekstowe ("Numer referencyjny") do formularza zamówienia w WooCommerce.
    *   Zapisuje wartość tego pola jako metadane zamówienia (`order meta`).
    *   Wyświetla zapisaną wartość w panelu administracyjnym na stronie szczegółów zamówienia.

2.  **Symulacja Odbioru Płatności przez API:**
    *   Tworzy niestandardowy endpoint API (`/wp-json/msp/v1/webhook`) do nasłuchiwania na powiadomienia o płatnościach.
    *   Implementuje mechanizm weryfikacji podpisu **HMAC SHA256** w nagłówku HTTP, aby zapewnić autentyczność i integralność przychodzących danych.
    *   Po pomyślnej weryfikacji, wtyczka odnajduje odpowiednie zamówienie w WooCommerce.
    *   Automatycznie zmienia status zamówienia na **"W trakcie realizacji"** i dodaje prywatną notatkę z ID transakcji.

---

## Demonstrowane Umiejętności i Technologie

*   **Backend:** `PHP`
*   **Platforma:** `WordPress`, `WooCommerce`
    *   Filtry i Akcje (Hooks)
    *   REST API (rejestracja niestandardowych tras)
    *   Metadane (Post Meta / Order Meta)
    *   Praca z obiektem `WC_Order`
*   **Frontend:** `HTML`, `CSS` (podstawy, w kontekście formularzy WP)
*   **API & Protokoły:** `HTTP (POST)`, `JSON`, `Webhooki`, `HMAC SHA256`
*   **Narzędzia:** `Git`, `Postman` (do testowania API), `WP-CLI` (opcjonalnie do zarządzania WP), `WP_DEBUG`

---

## Jak Testować

### 1. Testowanie niestandardowego pola
1.  Aktywuj wtyczkę w panelu WordPress.
2.  Dodaj dowolny produkt do koszyka i przejdź do strony zamówienia.
3.  W formularzu, pod polami adresowymi, powinno pojawić się pole "Numer referencyjny (MSP)".
4.  Wpisz dowolną wartość i złóż zamówienie.
5.  W panelu admina, w szczegółach tego zamówienia, wpisana wartość powinna być widoczna pod adresem bilingowym.

### 2. Testowanie Webhooka (za pomocą Postman)
1.  Stwórz w WooCommerce testowe zamówienie i ustaw jego status na "W oczekiwaniu na płatność". Zanotuj jego ID (np. `123`).
2.  Skonfiguruj żądanie w Postman:
    *   Metoda: `POST`
    *   URL: `https://twojadomena.pl/wp-json/msp/v1/webhook`
3.  W zakładce **Body** wybierz `raw` i `JSON`, a następnie wklej:
    ```json
    {
        "event_type": "payment_completed",
        "order_id": 123,
        "transaction_id": "txn_abcdef123456",
        "amount": "150.75"
    }
    ```
    *(Pamiętaj, aby podmienić `123` na ID swojego zamówienia)*
4.  Wygeneruj podpis HMAC SHA256 (np. używając narzędzia online) dla powyższych danych i sekretnego klucza zdefiniowanego w pliku wtyczki (w stałej MSP_WEBHOOK_SECRET).
5.  W zakładce **Headers** dodaj nowy nagłówek:
    *   KEY: `X-Msp-Signature`
    *   VALUE: `tutaj-wklej-wygenerowany-podpis`
6.  Wyślij żądanie. Oczekiwana odpowiedź to `Status: 200 OK`.
7.  Odśwież stronę zamówienia w panelu WooCommerce – jego status powinien zmienić się na "W trakcie realizacji".

---

## Autor

*   **Damian Kmiecik** - https://github.com/damiankmiecik1
