<?php

declare(strict_types=1);

return [
    // Page
    'page_title' => 'Transactions',
    'page_description' => 'Gérer toutes les transactions',
    
    // Actions
    'add_transaction' => 'Ajouter une Transaction',
    'edit_transaction' => 'Modifier la Transaction',
    'view_transaction' => 'Voir la Transaction',
    'delete_transaction' => 'Supprimer la Transaction',
    'cancel_transaction' => 'Annuler la Transaction',
    'complete_transaction' => 'Compléter la Transaction',
    'statistics' => 'Statistiques',
    'close' => 'Fermer',
    'cancel' => 'Annuler',
    'save' => 'Enregistrer',
    'update' => 'Mettre à jour',
    'create' => 'Créer',
    'refresh' => 'Actualiser',
    
    // Statuts
    'pending' => 'En attente',
    'available' => 'Disponible',
    'completed' => 'Complétée',
    'cancelled' => 'Annulée',
    'failed' => 'Échouée',
    'expired' => 'Expirée',
    
    // Champs
    'transaction_type' => 'Type de Transaction',
    'branch' => 'Agence',
    'destination_branch' => 'Agence de Destination',
    'customer' => 'Client',
    'wallet' => 'Portefeuille',
    'customer_phone' => 'Téléphone Client',
    'gross_amount' => 'Montant Brut',
    'fee_amount' => 'Montant des Frais',
    'net_amount' => 'Montant Net',
    'status' => 'Statut',
    'reference' => 'Référence',
    'currency' => 'Devise',
    'period' => 'Période',
    'start_date' => 'Date début',
    'end_date' => 'Date fin',
    'date_range' => 'Plage de dates',
    'created_by' => 'Créé par',
    'created_at' => 'Créé le',
    'updated_at' => 'Modifié le',
    
    // Périodes
    'today' => 'Aujourd\'hui',
    'yesterday' => 'Hier',
    'this_week' => 'Cette semaine',
    'this_month' => 'Ce mois',
    'last_month' => 'Mois passé',
    'this_year' => 'Cette année',
    'custom' => 'Personnalisée',
    
    // Statistiques
    'by_status' => 'Par Statut',
    'overview' => 'Vue d\'ensemble',
    'total_transactions' => 'Total Transactions',
    'total_amount' => 'Montant Total',
    'total_fees' => 'Frais Totaux',
    'total_net' => 'Net Total',
    'no_statistics' => 'Aucune statistique disponible',
    'loading_stats' => 'Chargement des statistiques...',
    'stats_error' => 'Erreur lors du chargement des statistiques',
    
    // Détails
    'transaction_details' => 'Détails de la Transaction',
    'status_timeline' => 'État de la transaction',
    'additional_info' => 'Informations complémentaires',
    'loading_details' => 'Chargement des détails...',
    'fetch_error' => 'Erreur lors du chargement des détails',
    
    // Messages de confirmation
    'confirm_delete_title' => 'Êtes-vous sûr?',
    'confirm_delete_text' => 'Cette action ne peut pas être annulée!',
    'confirm_delete_button' => 'Oui, supprimer!',
    'confirm_cancel_title' => 'Annuler la transaction?',
    'confirm_cancel_text' => 'Cette action ne peut pas être annulée!',
    'confirm_cancel_button' => 'Oui, annuler!',
    'confirm_complete_title' => 'Compléter la Transaction?',
    'confirm_complete_text' => 'Cette action marquera la transaction comme complétée.',
    'confirm_complete_button' => 'Oui, compléter!',
    
    // Messages de succès
    'success' => 'Succès!',
    'create_success' => 'Transaction créée avec succès.',
    'update_success' => 'Transaction mise à jour avec succès.',
    'delete_success' => 'Transaction supprimée avec succès.',
    'cancel_success' => 'Transaction annulée avec succès.',
    'complete_success' => 'Transaction complétée avec succès.',
    
    // Messages d'erreur
    'error' => 'Erreur!',
    'create_error' => 'Échec de la création de la transaction.',
    'update_error' => 'Échec de la mise à jour de la transaction.',
    'delete_error' => 'Échec de la suppression de la transaction.',
    'cancel_error' => 'Échec de l\'annulation de la transaction.',
    'complete_error' => 'Échec de la complétion de la transaction.',
];
