<?php

declare(strict_types=1);

return [
    // General
    'page_title' => 'Gestion des Devises',
    'page_description' => 'Gérer les devises supportées par le système',
    'add_currency' => 'Ajouter une Devise',
    'edit_currency' => 'Modifier la Devise',
    'delete' => 'Supprimer',
    'edit' => 'Modifier',
    'activate' => 'Activer',
    'deactivate' => 'Désactiver',
    'refresh' => 'Actualiser',
    'search_placeholder' => 'Rechercher une devise...',
    'no_results' => 'Aucune devise trouvée',
    'loading' => 'Chargement des devises...',
    'no_currencies' => 'Aucune devise configurée',
    
    // Messages
    'created_successfully' => 'Devise créée avec succès',
    'updated_successfully' => 'Devise mise à jour avec succès',
    'deleted_successfully' => 'Devise supprimée avec succès',
    'create_error' => 'Erreur lors de la création de la devise',
    'update_error' => 'Erreur lors de la mise à jour de la devise',
    'delete_error' => 'Erreur lors de la suppression de la devise',
    'not_found' => 'Devise non trouvée',
    'in_use' => 'Cette devise est utilisée dans des transactions et ne peut pas être supprimée',
    'used_by_wallets' => 'Cette devise est utilisée par des portefeuilles et ne peut pas être supprimée',
    'cannot_delete_default' => 'La devise par défaut ne peut pas être supprimée',
    'no_default_currency' => 'Aucune devise par défaut configurée',
    
    // Confirmation
    'delete_confirm_title' => 'Supprimer la Devise?',
    'delete_confirm_message' => 'Êtes-vous sûr de vouloir supprimer cette devise? Cette action ne peut pas être annulée.',
    'delete_confirm_button' => 'Oui, supprimer',
    'cancel_button' => 'Annuler',
    
    // Status
    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],
    
    // Table
    'table' => [
        'code' => 'Code',
        'name' => 'Nom',
        'symbol' => 'Symbole',
        'exchange_rate' => 'Taux de Change',
        'decimals' => 'Décimales',
        'status' => 'Statut',
        'default' => 'Par Défaut',
        'example' => 'Exemple',
        'actions' => 'Actions',
    ],
    
    // Form
    'form' => [
        'code' => 'Code ISO',
        'code_placeholder' => 'Ex: USD, EUR, CDF',
        'code_hint' => 'Code ISO à 3 lettres (Ex: USD, EUR, CDF)',
        'name' => 'Nom',
        'name_placeholder' => 'Ex: Dollar Américain',
        'symbol' => 'Symbole',
        'symbol_placeholder' => 'Ex: $, €, FC',
        'country' => 'Pays',
        'country_placeholder' => 'Sélectionnez un pays',
        'country_hint' => 'Pays d\'origine de la devise (optionnel)',
        'decimal_places' => 'Nombre de Décimales',
        'decimal_places_hint' => 'Nombre de chiffres après la virgule (0-4)',
        'exchange_rate' => 'Taux de Change',
        'exchange_rate_placeholder' => 'Ex: 1.0',
        'exchange_rate_hint' => 'Taux de change par rapport à la devise de base',
        'is_active' => 'Actif',
        'is_active_hint' => 'La devise est disponible pour les transactions',
        'is_default' => 'Par Défaut',
        'is_default_hint' => 'Définir comme devise par défaut du système',
        'cancel' => 'Annuler',
        'update' => 'Mettre à jour',
        'create' => 'Créer',
    ],
    
    // Validation
    'validation' => [
        'code_required' => 'Le code de la devise est requis',
        'code_size' => 'Le code de la devise doit contenir exactement 3 caractères',
        'code_uppercase' => 'Le code de la devise doit être en majuscules',
        'code_unique' => 'Ce code de devise existe déjà',
        'name_required' => 'Le nom de la devise est requis',
        'name_max' => 'Le nom de la devise ne peut pas dépasser 100 caractères',
        'symbol_required' => 'Le symbole de la devise est requis',
        'symbol_max' => 'Le symbole ne peut pas dépasser 10 caractères',
        'country_id_integer' => 'L\'identifiant du pays doit être un entier',
        'country_id_exists' => 'Le pays sélectionné n\'existe pas',
        'decimal_places_required' => 'Le nombre de décimales est requis',
        'decimal_places_integer' => 'Le nombre de décimales doit être un entier',
        'decimal_places_min' => 'Le nombre de décimales doit être au minimum 0',
        'decimal_places_max' => 'Le nombre de décimales ne peut pas dépasser 4',
        'exchange_rate_required' => 'Le taux de change est requis',
        'exchange_rate_numeric' => 'Le taux de change doit être un nombre',
        'exchange_rate_min' => 'Le taux de change doit être supérieur à 0',
        'exchange_rate_max' => 'Le taux de change est trop élevé',
        'is_active_boolean' => 'Le statut actif doit être vrai ou faux',
        'is_default_boolean' => 'Le statut par défaut doit être vrai ou faux',
    ],
];
