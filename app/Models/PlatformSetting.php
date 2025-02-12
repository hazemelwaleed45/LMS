<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    use HasFactory;

    protected $table = 'platform_settings';

    protected $fillable = [
        'admin_commission',
        'admin_paypal_email',
        // 'admin_stripe_account_id',
    ];

    /**
     * Get the platform's admin commission.
     * Defaults to 20% if not set.
     */
    public static function getAdminCommission()
    {
        return self::first()->admin_commission ?? 20.00;
    }

    /**
     * Get the admin PayPal email.
     */
    public static function getAdminPayPalEmail()
    {
        return self::first()->admin_paypal_email ?? null;
    }

    /**
     * Get the admin Stripe Account ID.
     */
    public static function getAdminStripeAccountId()
    {
        return self::first()->admin_stripe_account_id ?? null;
    }
}
