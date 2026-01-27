<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Pos\ReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Commande pour générer les rapports quotidiens du POS
 * À exécuter via le scheduler ou manuellement
 */
class GenerateDailyPosReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:daily-report 
                            {--date= : Date du rapport (format: Y-m-d, défaut: hier)}
                            {--user= : ID de l\'utilisateur (optionnel)}
                            {--output : Afficher le rapport dans la console}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Génère un rapport quotidien des ventes du POS';

    public function __construct(
        private ReportService $reportService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Génération du rapport quotidien POS...');

        // Récupérer la date (hier par défaut)
        $dateOption = $this->option('date');
        $date = $dateOption ? Carbon::parse($dateOption) : now()->subDay();
        
        $userId = $this->option('user') ? (int) $this->option('user') : null;

        $this->info("📅 Date: {$date->format('d/m/Y')}");
        if ($userId) {
            $this->info("👤 Utilisateur: {$userId}");
        }

        try {
            // Générer les statistiques
            $stats = $this->reportService->getDailyStats($date, $userId);

            // Enregistrer dans les logs
            Log::channel('daily')->info('Rapport quotidien POS généré', [
                'date' => $date->format('Y-m-d'),
                'user_id' => $userId,
                'stats' => $stats,
            ]);

            // Afficher le rapport si demandé
            if ($this->option('output')) {
                $this->displayReport($stats, $date);
            }

            $this->info('✅ Rapport généré avec succès!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la génération du rapport: ' . $e->getMessage());
            Log::error('Erreur génération rapport POS', [
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage(),
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Affiche le rapport dans la console
     *
     * @param array $stats
     * @param Carbon $date
     * @return void
     */
    private function displayReport(array $stats, Carbon $date): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════');
        $this->info("📊 RAPPORT POS - {$date->format('d/m/Y')}");
        $this->info('═══════════════════════════════════════');
        $this->newLine();

        // Statistiques globales
        $this->line("💰 Ventes totales: {$this->formatMoney($stats['total_sales'])}");
        $this->line("🧾 Nombre de transactions: {$stats['transaction_count']}");
        $this->line("📈 Ticket moyen: {$this->formatMoney($stats['average_ticket'])}");
        $this->newLine();

        // Méthodes de paiement
        if (!empty($stats['payment_methods'])) {
            $this->info('💳 Méthodes de paiement:');
            foreach ($stats['payment_methods'] as $method => $data) {
                $this->line("  - {$this->formatPaymentMethod($method)}: {$data['count']} transactions, {$this->formatMoney($data['total'])}");
            }
            $this->newLine();
        }

        // Top produits
        if (!empty($stats['top_products'])) {
            $this->info('🏆 Top 5 des produits:');
            foreach (array_slice($stats['top_products'], 0, 5) as $index => $product) {
                $this->line(sprintf(
                    "  %d. %s - %d unités - %s",
                    $index + 1,
                    $product->name,
                    $product->quantity_sold,
                    $this->formatMoney($product->revenue)
                ));
            }
            $this->newLine();
        }

        // Distribution horaire (heures avec ventes)
        $this->info('⏰ Distribution horaire:');
        $hourlyData = array_filter($stats['hourly_distribution'], fn($h) => $h['count'] > 0);
        if (!empty($hourlyData)) {
            foreach ($hourlyData as $hour) {
                $bar = str_repeat('█', min(50, (int)($hour['count'] * 5)));
                $this->line(sprintf(
                    "  %s: %s %d ventes (%s)",
                    $hour['hour'],
                    $bar,
                    $hour['count'],
                    $this->formatMoney($hour['total'])
                ));
            }
        } else {
            $this->line('  Aucune vente enregistrée');
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════');
    }

    /**
     * Formate un montant monétaire
     *
     * @param float|string|int $amount
     * @return string
     */
    private function formatMoney(float|string|int $amount): string
    {
        return number_format((float) $amount, 0, ',', ' ') . ' ' . current_currency();
    }

    /**
     * Formate le nom de la méthode de paiement
     *
     * @param string $method
     * @return string
     */
    private function formatPaymentMethod(string $method): string
    {
        return match($method) {
            'cash' => 'Espèces',
            'card' => 'Carte bancaire',
            'mobile_money' => 'Mobile Money',
            'bank_transfer' => 'Virement',
            default => ucfirst($method),
        };
    }
}
