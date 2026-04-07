<?php

declare(strict_types=1);

return [
    // Page
    'title'       => 'Tableau de bord',
    'description' => 'Vue globale du système de transfert',

    // KPI labels
    'total'              => 'Total',
    'total_transactions' => 'Transactions',
    'total_amount'       => 'Montant total',
    'total_fees'         => 'Total frais',
    'total_net'          => 'Montant net',
    'average_amount'     => 'Montant moyen',
    'success_rate'       => 'Taux de succès',
    'gross'              => 'Brut',
    'fees'               => 'Frais',
    'net'                => 'Net',
    'rate'               => 'Taux',
    'average'            => 'Moy',
    'amount'             => 'Montant',

    // Sections
    'by_status'           => 'Répartition par statut',
    'trend'               => 'Évolution des transactions',
    'by_type'             => 'Par type de transaction',
    'by_branch'           => 'Par agence',
    'recent_transactions' => 'Transactions récentes',
    'view_all'            => 'Voir tout',

    // Filters
    'filter' => [
        'all_branches'   => 'Toutes les agences',
        'all_currencies' => 'Toutes les devises',
        'all_types'      => 'Tous les types',
    ],

    // Periods
    'period' => [
        'today'      => "Aujourd'hui",
        'yesterday'  => 'Hier',
        'week'       => 'Cette semaine',
        'month'      => 'Ce mois',
        'last_month' => 'Mois passé',
        'year'       => 'Cette année',
        'all'        => 'Tout le temps',
        'custom'     => 'Plage personnalisée',
    ],

    // UI States
    'filters_active' => 'filtre(s) actif(s)',
    'reset_filters'  => 'Réinitialiser',
    'no_data'        => 'Aucune donnée',
    'no_trend_data'  => 'Aucune donnée de tendance',
    'no_recent'      => 'Aucune transaction récente',
    'no_data_title'  => 'Aucune donnée disponible',
    'no_data_desc'   => 'Modifiez les filtres ou vérifiez la connexion.',
    'retry'          => 'Réessayer',
];
