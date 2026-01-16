<?php

namespace App\Models;

use App\Traits\HasStoreScope;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory, HasStoreScope, BelongsToOrganization;

    /**
     * Invoice status constants.
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'store_id',
        'sale_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax',
        'total',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-assign store from sale if not provided
        static::creating(function ($invoice) {
            if (!$invoice->store_id && $invoice->sale_id) {
                $sale = Sale::find($invoice->sale_id);
                if ($sale) {
                    $invoice->store_id = $sale->store_id;
                }
            }

            // Generate invoice number if not provided
            if (!$invoice->invoice_number) {
                $invoice->invoice_number = self::generateInvoiceNumber($invoice->store_id);
            }
        });
    }

    /**
     * Generate a unique invoice number per store.
     * Format: FACT-S{store_id}-{année-mois}-{séquence}-{random}
     */
    public static function generateInvoiceNumber(?int $storeId = null): string
    {
        $storeId = $storeId ?? 0;
        $date = now()->format('Y-m');
        $prefix = 'FACT-S' . $storeId . '-' . $date . '-';
        $maxAttempts = 10;

        return \DB::transaction(function () use ($prefix, $maxAttempts) {
            for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                // Récupère le MAX du numéro de séquence pour ce store
                $maxNumber = self::where('invoice_number', 'like', $prefix . '%')
                                ->lockForUpdate()
                                ->selectRaw('MAX(CAST(SUBSTRING_INDEX(invoice_number, "-", -1) AS UNSIGNED)) as max_num')
                                ->value('max_num');

                $nextNumber = ($maxNumber ?? 0) + 1 + $attempt;
                
                // Format: FACT-S1-2026-01-0007-ABC (ajout d'un suffixe aléatoire pour unicité)
                $randomSuffix = strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 3));
                $invoiceNumber = $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT) . '-' . $randomSuffix;

                // Vérifie que ce numéro n'existe pas
                if (!self::where('invoice_number', $invoiceNumber)->exists()) {
                    return $invoiceNumber;
                }
            }

            // Fallback avec timestamp complet + microsecondes
            $timestamp = now()->format('His') . substr((string)microtime(true), -4);
            return $prefix . $timestamp . '-' . strtoupper(substr(uniqid(), -3));
        });
    }

    /**
     * Get the sale that owns the invoice.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the store that owns the invoice.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the client through the sale.
     */
    public function getClientAttribute()
    {
        return $this->sale->client;
    }

    /**
     * Scope a query to only include paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope a query to only include unpaid invoices.
     */
    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_SENT]);
    }

    /**
     * Scope a query to only include overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', self::STATUS_PAID)
                    ->where('due_date', '<', now());
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status !== self::STATUS_PAID
            && $this->due_date
            && $this->due_date->isPast();
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(): void
    {
        $this->status = self::STATUS_PAID;
        $this->save();

        // Update related sale payment status
        if ($this->sale) {
            $this->sale->payment_status = Sale::PAYMENT_PAID;
            $this->sale->save();
        }
    }
}
