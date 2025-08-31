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
    *   Praca z obiektem `WC_Order`, WordPress HTTP API ( wp_remote_post )
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

### 2. Testowanie Webhooka (za pomocą skryptu symulującego)
W repozytorium znajduje się plik webhook-sender.php, który jest prostym skryptem symulującym wysyłanie powiadomienia o transakcji przez zewnętrzną bramkę płatniczą, wzorowaną na dokumentacji technicznej imoje.

Sposób użycia:
1. Umieść plik webhook-sender.php w głównym folderze swojej instalacji WordPressa.
2. Upewnij się, że masz wtyczkę "Moje Super Płatności" aktywną.
3. W plikach moje-super-platnosci.php i webhook-sender.php upewnij się, że używasz tego samego, unikalnego sekretnego klucza.
4. W pliku webhook-sender.php zmień wartość orderId na ID istniejącego zamówienia w Twoim sklepie (najlepiej ze statusem "Oczekujące na płatność").
5. Uruchom skrypt, wchodząc na adres http://twoja-strona.test/webhook-sender.php w przeglądarce.

Skrypt używa WordPress HTTP API (wp_remote_post) do wysłania żądania POST do endpointu wtyczki, a następnie wyświetla pełną odpowiedź z serwera. Po pomyślnym wykonaniu (kod 200 OK), status zamówienia w WooCommerce powinien zmienić się na "W trakcie realizacji".

---

## Refaktoryzacja

W najnowszej wersji wtyczki przeprowadzono refaktoryzację kodu w celu poprawy jego czytelności i struktury. Logika odpowiedzialna za weryfikację podpisu HMAC została wydzielona z głównej funkcji obsługującej webhook do dedykowanej, prywatnej funkcji pomocniczej (_msp_is_signature_valid). Ułatwia to utrzymanie oraz testowanie kodu.

## Autor

*   **Damian Kmiecik** - https://github.com/damiankmiecik1
