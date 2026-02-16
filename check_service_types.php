<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\ProductType;
use Illuminate\Support\Facades\Auth;

// Find the user
$user = User::where('email', 'mkbcentralcode@gmail.com')->first();

if (!$user) {
    echo "❌ User not found\n";
    exit;
}

echo "✅ User found: {$user->name}\n\n";

// Get user's organization
$org = $user->organizations()->first();
if (!$org) {
    echo "❌ No organization found\n";
    exit;
}

echo "✅ Organization: {$org->name} (Type: {$org->business_activity->value})\n";
echo "=".str_repeat("=", 70)."\n\n";

// Simulate user context with Auth
Auth::setUser($user);
if ($user->currentStore) {
    $user->default_organization_id = $org->id;
} else {
    $user->default_organization_id = $org->id;
}

// Get service types using the updated scope
$serviceTypes = ProductType::forCurrentOrganization()
    ->where('is_service', true)
    ->ordered()
    ->get();

echo "Service Types Available for Product Creation:\n";
echo "-".str_repeat("-", 70)."\n";

$expectedServices = ['coiffure', 'esthetique', 'photographie', 'consultation', 'reparation'];
$found = [];

foreach ($serviceTypes as $type) {
    $origin = $type->organization_id ? "Org-specific" : "Global";
    echo "  ✓ {$type->icon} {$type->name} (slug: {$type->slug}) [{$origin}]\n";
    $found[] = $type->slug;
}

echo "\n";
echo "Check Results:\n";
echo "-".str_repeat("-", 70)."\n";

foreach ($expectedServices as $slug) {
    $exists = in_array($slug, $found);
    $icon = $exists ? "✅" : "❌";
    echo "$icon {$slug}\n";
}

// Detailed check for specific types
echo "\n";
echo "Detailed Type Check:\n";
echo "-".str_repeat("-", 70)."\n";

$photo = ProductType::forCurrentOrganization()->where('slug', 'photographie')->first();
$estet = ProductType::forCurrentOrganization()->where('slug', 'esthetique')->first();

echo "Photographie: " . ($photo ? "✅ Found - {$photo->name}" : "❌ NOT FOUND") . "\n";
echo "Esthétique: " . ($estet ? "✅ Found - {$estet->name}" : "❌ NOT FOUND") . "\n";
