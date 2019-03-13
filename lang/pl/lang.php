<?php return [
    'plugin' => [
        'name' => 'PowerComponents',
        'description' => 'Plugin z zestawem traitów i behaviorów dla komponentów, które przenosi pracę z komponentami na zupełnie inny poziom'
    ],
    'list_component_properties' => [
        'record_url' => 'Strona rekordu',
        'record_url_desc' => 'Wybierz stronę, do której przekieruje kliknięcie w rekord',
        'create_page' => 'Strona tworzenia rekordu',
        'create_page_desc' => 'Wybierz stronę, na której jest komponent tworzenia rekordu',
    ],
    'list_toolbar' => [
        'add_item' => 'Dodaj',
        'filter_placeholder' => 'Filtruj'
    ],
    'list_actions' => [
        'title' => 'Akcje',
        'delete_confirmation' => 'Czy na pewno chcesz usunąć ten wpis?',
    ],
    'component_properties' => [
        'pages_group' => 'Strony',
        'list_page' => 'Strona listy rekordów',
        'list_page_desc' => 'Użytkownik zostanie przekierowany do tej strony po kliknięciu przycisku zapisz i zamknij lub anuluj',
        'update_page' => 'Strona aktualizacji rekordu',
        'update_page_desc' => 'Użytkownik zostanie przekierowany do tej strony po kliknięciu przycisku zapisz',
        'create_page' => 'Strona tworzenia rekordu',
        'create_page_desc' => 'Strona tworzenia rekordu',
        'preview_page' => 'Strona podglądu rekordu',
        'preview_page_desc' => 'Strona podglądu rekordu',
    ],
    'form_component_properties' => [
        'context' => 'Kontekst',
        'context_desc' => 'Kontekst komponentu: tworzenie, aktualizacja lub podgląd',
        'context_create' => 'Tworzenie',
        'context_update' => 'Aktualizacja',
        'context_preview' => 'Podgląd',
        'record_key' => 'ID rekordu',
        'record_key_desc' => 'ID pobrane prawdopodobnie z URLa do wstrzyknięcia na stronę',
        'record_key_name' => 'Kolumna z kluczem głównym',
        'record_key_name_desc' => 'Nazwa kolumny z kluczem głównym',
    ],
    'form_actions' => [
        'create' => 'Zapisz',
        'create_and_next' => 'Zapisz i twórz następny',
        'create_and_close' => 'Zapisz i zamknij',
        'cancel' => 'Anuluj',
        'saving' => 'Zapisywanie',
        'or' => 'lub',
        'update' => 'Aktualizuj',
        'update_and_close' => 'Aktualizuj i zamknij',
        'back_to_list' => 'Powróć do listy',
        'update_record' => 'Aktualizuj wpis',
        'delete' => 'Usuń',
        'delete_confirm' => 'Czy na pewno chcesz usunąć ten element?',
        'delete_success' => 'Usunięto pomyślnie',
    ],
    'form_save' => [
        'success' => 'Pomyślnie zapisano'
    ],
    'permissions' => [
        'access_forbidden' => 'Zablokowany dostęp'
    ],
    'misc' => [
        'loader_text' => 'Ładowanie...'
    ]
];
