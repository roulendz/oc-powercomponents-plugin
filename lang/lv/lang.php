<?php return [
    'plugin' => [
        'name' => 'PowerComponents',
        'description' => 'Spraudnis, kas palīdzēs backend formas un sarakstus izmantot forntend'
    ],
    'list_component_properties' => [
        'record_page' => 'Kur pāradresēt pēc nospiešanas uz ieraksta',
        'record_page_desc' => 'Izvēlieties lapu, uz kuru pāradresēt pēc nospiešanas uz ieraksta',
        'create_page' => 'Lapa, kur tiks izveidots jauns ieraksts',
        'create_page_desc' => 'Lūdzu, izvēlieties lapu, kurā atrodas komponents: Izveidot Ierakstu',
    ],
    'list_toolbar' => [
        'add_item' => 'Izveidot jaunu',
        'filter_placeholder' => 'Filtra veids'
    ],
    'list_actions' => [
        'title' => 'Darbības',
        'delete_confirmation' => 'Vai jūs tiešām vēlaties dzēst šo ierakstu?',
    ],
    'component_properties' => [
        'pages_group' => 'Lapas',
        'list_page' => 'Lapu saraksts',
        'list_page_desc' => 'Lapa ar ierakstiem - saraksts',
        'update_page' => 'Labot lapa',
        'update_page_desc' => 'Lapa, kurā var labot ierakstus - kurā atrodas komponents: Labot Ierakstu',
        'create_page' => 'Izveidot jaunu ierakstu - lapa',
        'create_page_desc' => 'Lapa, kurā var izveidot jaunu ierakstu - kurā atrodas komponents: Izveidot jaunu Ierakstu',
        'preview_page' => 'Ieraksta apskates lapa',
        'preview_page_desc' => 'Lapa, kurā var apskatīt ierakstu - kurā atrodas komponents: Apskatīt Ierakstu',
    ],
    'form_component_properties' => [
        'context' => 'Konteksts',
        'context_desc' => 'Konteksts, kurā rādīt komponentu: izveidot, labot vai apskatīt',
        'context_create' => 'Izveidot',
        'context_update' => 'Labot',
        'context_preview' => 'Apskatīt',
        'record_key' => 'Record ID',
        'record_key_desc' => 'ID - parasti tiek paņemts no URL, lai sasaistītu ar attiecīgo ierakstu, var izmantot arī citus datubāzes laukus',
        'record_key_name' => 'Primārās atslēgas kolonna, parasti ID, bet var arī izamntot SLUG vai ko citu, kas ir unikāls ierakts,',
        'record_key_name_desc' => 'Kollonas nosaukums, kas tiks imantots kā virsraksts ar Primāro atslēgu',
    ],
    'form_actions' => [
        'create' => 'Izveidot',
        'create_and_next' => 'Izveidot',
        'create_and_close' => 'izveidot un aizvērt',
        'cancel' => 'Atcelt',
        'saving' => 'Lūdzu, uzgaidiet! Saglabājam.',
        'or' => 'vai',
        'update' => 'Saglabāt',
        'update_and_close' => 'Saglabāt un aizvērt',
        'back_to_list' => 'Atpaka uz sarakstu',
        'update_record' => 'Labot ierakstu',
        'delete' => 'Dzēst',
        'delete_confirm' => 'Vai esi pārliecināts?',
        'delete_success' => 'Dzēšana pabeigta',
    ],
    'form_save' => [
        'success' => 'Saglabāšana notikusi veiksmīgi'
    ],
    'permissions' => [
        'access_forbidden' => 'Pieeja datiem liegta, Jums te nav ko darīt!'
    ],
    'exception' => [
        'not_defined' => '":name" lauks nav definēts vai nepareiza klase'
    ],
    'misc' => [
        'loader_text' => 'Ielādējam...'
    ],
    'alert' => [
        'cancel_button_text' => 'Aizvērt',
        'confirm_button_text' => 'Apstiprināt',
    ],
    ];
