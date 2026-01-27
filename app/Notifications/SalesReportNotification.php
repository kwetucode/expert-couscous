<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Store;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SalesReportNotification extends Notification
{
    public function __construct(
        public Store $store,
        public array $salesData,
        public string $reportType = 'daily' // daily, hourly, milestone, new_sale
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification for database.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sales_report',
            'report_type' => $this->reportType,
            'store_id' => $this->store->id,
            'store_name' => $this->store->name,
            'data' => $this->salesData,
            'message' => $this->getMessage(),
            'icon' => $this->getIcon(),
            'color' => $this->getColor(),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'sales_report',
            'report_type' => $this->reportType,
            'store_id' => $this->store->id,
            'store_name' => $this->store->name,
            'data' => $this->salesData,
            'message' => $this->getMessage(),
            'icon' => $this->getIcon(),
            'color' => $this->getColor(),
        ]);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $currency = $this->getCurrency();
        
        return (new MailMessage)
            ->subject("Rapport de ventes - {$this->store->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line($this->getMessage())
            ->line("Montant total: " . number_format($this->salesData['total_amount'] ?? 0, 0, ',', ' ') . " {$currency}")
            ->line("Nombre de ventes: " . ($this->salesData['total_sales'] ?? 0))
            ->action('Voir le tableau de bord', url('/dashboard'))
            ->salutation('L\'équipe STK');
    }

    /**
     * Get the currency from the store's organization
     */
    private function getCurrency(): string
    {
        return $this->store->organization?->currency ?? config('app.default_currency', 'FCFA');
    }

    /**
     * Format amount with the store's organization currency
     */
    private function formatAmount(float $amount): string
    {
        return number_format($amount, 0, ',', ' ') . ' ' . $this->getCurrency();
    }

    /**
     * Generate message based on report type
     */
    private function getMessage(): string
    {
        $storeName = $this->store->name;
        $totalAmount = $this->formatAmount((float) ($this->salesData['total_amount'] ?? 0));
        $totalSales = $this->salesData['total_sales'] ?? 0;

        return match ($this->reportType) {
            'hourly' => "📊 {$storeName}: {$totalSales} vente(s) cette heure - {$totalAmount}",
            'milestone' => "🎉 {$storeName} a atteint {$totalAmount} de ventes aujourd'hui!",
            'daily' => "📈 Résumé journalier {$storeName}: {$totalSales} ventes - {$totalAmount}",
            'new_sale' => "💰 Nouvelle vente à {$storeName}: {$totalAmount}",
            default => "Rapport de ventes pour {$storeName}",
        };
    }

    /**
     * Get icon based on report type
     */
    private function getIcon(): string
    {
        return match ($this->reportType) {
            'hourly' => 'chart-bar',
            'milestone' => 'trophy',
            'daily' => 'document-report',
            'new_sale' => 'cash',
            default => 'bell',
        };
    }

    /**
     * Get color based on report type
     */
    private function getColor(): string
    {
        return match ($this->reportType) {
            'hourly' => 'blue',
            'milestone' => 'yellow',
            'daily' => 'indigo',
            'new_sale' => 'green',
            default => 'gray',
        };
    }
}
