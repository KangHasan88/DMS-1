<?php

namespace Database\Seeders;

use App\Models\DeliveryVendor;
use Illuminate\Database\Seeder;

class DeliveryVendorSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = [
            [
                'name' => 'JNE Express',
                'code' => 'JNE',
                'vendor_type' => DeliveryVendor::TYPE_EXPEDITION,
                'phone' => '021-29278888',
                'contact_person' => 'Customer Service JNE',
                'payment_term' => DeliveryVendor::PAYMENT_TERM_INVOICE,
                'notes' => 'Vendor ekspedisi nasional untuk paket reguler dan cepat.',
            ],
            [
                'name' => 'J&T Express',
                'code' => 'JNT',
                'vendor_type' => DeliveryVendor::TYPE_EXPEDITION,
                'phone' => '021-80661888',
                'contact_person' => 'Customer Service J&T',
                'payment_term' => DeliveryVendor::PAYMENT_TERM_INVOICE,
                'notes' => 'Vendor ekspedisi nasional untuk pengiriman retail dan marketplace.',
            ],
            [
                'name' => 'SiCepat Ekspres',
                'code' => 'SCP',
                'vendor_type' => DeliveryVendor::TYPE_EXPEDITION,
                'phone' => '021-50200050',
                'contact_person' => 'Customer Service SiCepat',
                'payment_term' => DeliveryVendor::PAYMENT_TERM_INVOICE,
                'notes' => 'Vendor ekspedisi nasional untuk layanan reguler dan same day tertentu.',
            ],
            [
                'name' => 'Anteraja',
                'code' => 'ATR',
                'vendor_type' => DeliveryVendor::TYPE_EXPEDITION,
                'phone' => '021-50663333',
                'contact_person' => 'Customer Service Anteraja',
                'payment_term' => DeliveryVendor::PAYMENT_TERM_INVOICE,
                'notes' => 'Vendor ekspedisi nasional untuk pengiriman paket dan dokumen.',
            ],
            [
                'name' => 'GoSend',
                'code' => 'GOS',
                'vendor_type' => DeliveryVendor::TYPE_INSTANT,
                'phone' => '021-50849000',
                'contact_person' => 'Customer Service GoSend',
                'payment_term' => DeliveryVendor::PAYMENT_TERM_CASH,
                'notes' => 'Vendor instant courier untuk area lokal/kota.',
            ],
        ];

        foreach ($vendors as $vendor) {
            DeliveryVendor::updateOrCreate(
                [
                    'code' => $vendor['code'],
                    'company_branch_id' => null,
                ],
                $vendor + [
                    'company_branch_id' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}
