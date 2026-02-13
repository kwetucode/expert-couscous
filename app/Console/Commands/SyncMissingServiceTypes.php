<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Organization;
use App\Models\ProductType;
use App\Enums\BusinessActivityType;

class SyncMissingServiceTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:missing-service-types {--org-id= : Sync only for a specific organization}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize missing service types to organizations that should have them';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $orgId = $this->option('org-id');

        if ($orgId) {
            $organizations = Organization::where('id', $orgId)->get();
            if ($organizations->isEmpty()) {
                $this->error("Organization with ID {$orgId} not found.");
                return self::FAILURE;
            }
        } else {
            // Get all organizations that are services or mixed
            $organizations = Organization::whereIn('business_activity', ['services', 'mixed'])
                ->get();
        }

        if ($organizations->isEmpty()) {
            $this->info('No organizations to sync.');
            return self::SUCCESS;
        }

        $this->info("Syncing missing service types for {$organizations->count()} organization(s)...\n");

        $totalAdded = 0;

        foreach ($organizations as $organization) {
            $businessActivity = $organization->business_activity?->value ?? 'mixed';
            $isServiceOrg = ($businessActivity === 'services');
            $isMixed = ($businessActivity === 'mixed');

            // Get all global service types that should be available
            $query = ProductType::whereNull('organization_id')
                ->where('is_active', true)
                ->with(['attributes', 'categories']);

            if (!$isMixed) {
                $query->where('is_service', $isServiceOrg);
            }

            $globalServiceTypes = $query->get();

            $organizationBar = $this->output->createProgressBar($globalServiceTypes->count());
            $organizationBar->setFormat("%current%/%max% [%bar%] -- %message%");

            foreach ($globalServiceTypes as $globalType) {
                // Check if this type already exists for the organization
                $existingType = ProductType::where('organization_id', $organization->id)
                    ->where('slug', $globalType->slug)
                    ->first();

                if (!$existingType) {
                    // Copy the type to the organization
                    try {
                        $newType = $globalType->replicate();
                        $newType->organization_id = $organization->id;
                        $newType->save();

                        // Copy attributes
                        foreach ($globalType->attributes as $attribute) {
                            $newAttribute = $attribute->replicate();
                            $newAttribute->product_type_id = $newType->id;
                            $newAttribute->save();
                        }

                        // Copy categories
                        foreach ($globalType->categories as $category) {
                            $existingCategory = \App\Models\Category::where('organization_id', $organization->id)
                                ->where('slug', $category->slug)
                                ->first();

                            if (!$existingCategory) {
                                $newCategory = $category->replicate();
                                $newCategory->organization_id = $organization->id;
                                $newCategory->product_type_id = $newType->id;
                                $newCategory->save();
                            }
                        }

                        $organizationBar->setMessage("Added: {$globalType->name}");
                        $totalAdded++;
                    } catch (\Exception $e) {
                        $organizationBar->setMessage("Error: {$globalType->name} - {$e->getMessage()}");
                    }
                } else {
                    $organizationBar->setMessage("Exists: {$globalType->name}");
                }

                $organizationBar->advance();
            }

            $organizationBar->finish();
            $this->line("\n<info>Organization {$organization->name} ({$organization->id}):</info> Synchronized\n");
        }

        $this->info("\n<fg=green>Total service types added: {$totalAdded}</>");

        return self::SUCCESS;
    }
}
