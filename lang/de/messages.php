<?php

return [
    'redirects'           => 'Weiterleitungen',
    'redirect_created'    => 'Weiterleitung erstellt',
    'redirect_saved'      => 'Weiterleitung gespeichert',
    'redirect_deleted'    => 'Weiterleitung gelöscht',
    'delete_confirmation' => 'Möchtest du diese Weiterleitung wirklich löschen?',
    'redirects_reordered' => 'Sortierung gespeichert',
    'order_save_failed'   => 'Sortierung konnte nicht gespeichert werden',
    'save_failed'         => 'Speichern fehlgeschlagen',
    'validation_failed'   => 'Validierung fehlgeschlagen',

    'instructions' => [
        'source'      => 'Der URL-Pfad, von dem weitergeleitet werden soll. Verwende * als Wildcard (z.B. /blog/*).',
        'destination' => 'Die Ziel-URL. Verwende $1, $2 usw. für erfasste Wildcards.',
        'regex'       => 'Unterstützung reguläre Ausdrücke aktivieren (fortgeschritten).',
        'enabled'     => 'Ob diese Weiterleitung aktiv ist.',
        'sites'       => 'Weiterleitung auf bestimmte Seiten beschränken.',
    ],

    'validation' => [
        'blocked_protocol'        => 'Die Ziel-URL enthält ein blockiertes Protokoll.',
        'invalid_regex'           => 'Das reguläre Ausdruck ist ungültig.',
        'dangerous_regex_pattern' => 'Das reguläre Ausdruck ist potenziell gefährlich.',
    ],
];
