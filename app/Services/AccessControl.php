<?php

namespace App\Services;

class AccessControl
{
    // Central list of features used across UI and controllers
    private const FEATURES = [
        'dashboard',
        'customers',
        'orders',
        'production',
        'supplier_tracking',
        'workspace',
        'users',
        'settings',
        'profile',
    ];

    // Default landing order preference
    private const LANDING_ORDER = [
        'dashboard',
        'workspace',
        'supplier_tracking',
        'production',
        'orders',
        'customers',
        'users',
        'settings',
        'profile',
    ];

    public static function allFeatures(): array
    {
        return self::FEATURES;
    }

    public static function allowedFeaturesForRole(string $role): array
    {
        // Administrator is superuser
        if ($role === 'administrator') {
            return self::FEATURES;
        }

        // Fetch from settings; fall back to sensible defaults
        $key = "access.roles.{$role}.allowed_features";
        $allowed = SettingsService::get($key);

        if (!is_array($allowed)) {
            // Role-specific defaults
            if ($role === 'production_team') {
                $allowed = ['workspace', 'supplier_tracking', 'profile'];
            } else {
                // Generic default
                $allowed = ['profile'];
            }
        }

        // Ensure profile remains accessible
        if (!in_array('profile', $allowed, true)) {
            $allowed[] = 'profile';
        }

        // Filter to known features only
        return array_values(array_intersect(self::FEATURES, array_unique($allowed)));
    }

    public static function canAccess(string $role, string $feature): bool
    {
        if ($role === 'administrator') {
            return true;
        }
        $allowed = self::allowedFeaturesForRole($role);
        return in_array($feature, $allowed, true);
    }

    public static function defaultLandingRoute(string $role): string
    {
        $allowed = self::allowedFeaturesForRole($role);
        foreach (self::LANDING_ORDER as $feature) {
            if (in_array($feature, $allowed, true)) {
                return self::featureToRoute($feature);
            }
        }
        // Fallback
        return '/profile';
    }

    public static function featureToRoute(string $feature): string
    {
        switch ($feature) {
            case 'dashboard':
                return '/dashboard';
            case 'customers':
                return '/customers';
            case 'orders':
                return '/orders';
            case 'production':
                return '/production';
            case 'supplier_tracking':
                return '/production/supplier-tracking';
            case 'workspace':
                return '/workspace';
            case 'users':
                return '/users';
            case 'settings':
                return '/settings';
            case 'profile':
            default:
                return '/profile';
        }
    }
}
