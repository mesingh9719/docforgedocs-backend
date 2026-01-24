<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Currency;
use App\Models\Industry;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Tax;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Industries
        $industries = [
            ['name' => 'Technology', 'slug' => 'technology'],
            ['name' => 'Healthcare', 'slug' => 'healthcare'],
            ['name' => 'Finance', 'slug' => 'finance'],
            ['name' => 'Education', 'slug' => 'education'],
            ['name' => 'Real Estate', 'slug' => 'real-estate'],
            ['name' => 'Manufacturing', 'slug' => 'manufacturing'],
            ['name' => 'Retail', 'slug' => 'retail'],
            ['name' => 'Logistics', 'slug' => 'logistics'],
            ['name' => 'Energy', 'slug' => 'energy'],
            ['name' => 'Legal', 'slug' => 'legal'],
        ];

        foreach ($industries as $ind) {
            Industry::firstOrCreate(['slug' => $ind['slug']], $ind);
        }

        // 2. Currencies
        // 2. Currencies
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£'],
            ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹'],
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'C$'],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'A$'],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥'],
            ['code' => 'BRL', 'name' => 'Brazilian Real', 'symbol' => 'R$'],
            ['code' => 'ZAR', 'name' => 'South African Rand', 'symbol' => 'R'],
            ['code' => 'CHF', 'name' => 'Swiss Franc', 'symbol' => 'Fr'],
        ];

        foreach ($currencies as $curr) {
            Currency::firstOrCreate(['code' => $curr['code']], $curr);
        }

        // 3. Taxes
        $taxes = [
            ['name' => 'US Sales Tax', 'rate' => 7.00, 'type' => 'percentage'],
            ['name' => 'VAT (Standard)', 'rate' => 20.00, 'type' => 'percentage'],
            ['name' => 'GST (India)', 'rate' => 18.00, 'type' => 'percentage'],
            ['name' => 'HST (Canada)', 'rate' => 13.00, 'type' => 'percentage'],
            ['name' => 'Consumption Tax (Japan)', 'rate' => 10.00, 'type' => 'percentage'],
        ];

        foreach ($taxes as $tax) {
            Tax::firstOrCreate(['name' => $tax['name']], $tax);
        }

        // 4. Countries, States, Cities
        $countries = [
            [
                'name' => 'United States',
                'iso_code' => 'US',
                'phone_code' => '+1',
                'currency_code' => 'USD',
                'states' => [
                    ['name' => 'California', 'iso_code' => 'CA', 'cities' => ['Los Angeles', 'San Francisco', 'San Diego']],
                    ['name' => 'New York', 'iso_code' => 'NY', 'cities' => ['New York City', 'Buffalo', 'Albany']],
                    ['name' => 'Texas', 'iso_code' => 'TX', 'cities' => ['Houston', 'Austin', 'Dallas']],
                ]
            ],
            [
                'name' => 'United Kingdom',
                'iso_code' => 'GB',
                'phone_code' => '+44',
                'currency_code' => 'GBP',
                'states' => [
                    ['name' => 'England', 'iso_code' => 'ENG', 'cities' => ['London', 'Manchester', 'Birmingham']],
                    ['name' => 'Scotland', 'iso_code' => 'SCT', 'cities' => ['Edinburgh', 'Glasgow']],
                ]
            ],
            [
                'name' => 'India',
                'iso_code' => 'IN',
                'phone_code' => '+91',
                'currency_code' => 'INR',
                'states' => [
                    ['name' => 'Maharashtra', 'iso_code' => 'MH', 'cities' => ['Mumbai', 'Pune', 'Nagpur']],
                    ['name' => 'Delhi', 'iso_code' => 'DL', 'cities' => ['New Delhi']],
                    ['name' => 'Karnataka', 'iso_code' => 'KA', 'cities' => ['Bangalore', 'Mysore']],
                ]
            ],
            [
                'name' => 'Canada',
                'iso_code' => 'CA',
                'phone_code' => '+1',
                'currency_code' => 'CAD',
                'states' => [
                    ['name' => 'Ontario', 'iso_code' => 'ON', 'cities' => ['Toronto', 'Ottawa']],
                    ['name' => 'British Columbia', 'iso_code' => 'BC', 'cities' => ['Vancouver', 'Victoria']],
                ]
            ],
            [
                'name' => 'Australia',
                'iso_code' => 'AU',
                'phone_code' => '+61',
                'currency_code' => 'AUD',
                'states' => [
                    ['name' => 'New South Wales', 'iso_code' => 'NSW', 'cities' => ['Sydney', 'Newcastle']],
                    ['name' => 'Victoria', 'iso_code' => 'VIC', 'cities' => ['Melbourne', 'Geelong']],
                ]
            ],
            [
                'name' => 'France',
                'iso_code' => 'FR',
                'phone_code' => '+33',
                'currency_code' => 'EUR',
                'states' => [
                    ['name' => 'Île-de-France', 'iso_code' => 'IDF', 'cities' => ['Paris', 'Versailles']],
                    ['name' => 'Provence-Alpes-Côte d\'Azur', 'iso_code' => 'PAC', 'cities' => ['Marseille', 'Nice']],
                ]
            ],
            [
                'name' => 'Germany',
                'iso_code' => 'DE',
                'phone_code' => '+49',
                'currency_code' => 'EUR',
                'states' => [
                    ['name' => 'Bavaria', 'iso_code' => 'BY', 'cities' => ['Munich', 'Nuremberg']],
                    ['name' => 'Berlin', 'iso_code' => 'BE', 'cities' => ['Berlin']],
                ]
            ],
            [
                'name' => 'Japan',
                'iso_code' => 'JP',
                'phone_code' => '+81',
                'currency_code' => 'JPY',
                'states' => [
                    ['name' => 'Tokyo', 'iso_code' => '13', 'cities' => ['Shinjuku', 'Shibuya']],
                    ['name' => 'Osaka', 'iso_code' => '27', 'cities' => ['Osaka City']],
                ]
            ],
            [
                'name' => 'Brazil',
                'iso_code' => 'BR',
                'phone_code' => '+55',
                'currency_code' => 'BRL',
                'states' => [
                    ['name' => 'São Paulo', 'iso_code' => 'SP', 'cities' => ['São Paulo', 'Campinas']],
                    ['name' => 'Rio de Janeiro', 'iso_code' => 'RJ', 'cities' => ['Rio de Janeiro']],
                ]
            ],
            [
                'name' => 'South Africa',
                'iso_code' => 'ZA',
                'phone_code' => '+27',
                'currency_code' => 'ZAR',
                'states' => [
                    ['name' => 'Gauteng', 'iso_code' => 'GT', 'cities' => ['Johannesburg', 'Pretoria']],
                    ['name' => 'Western Cape', 'iso_code' => 'WC', 'cities' => ['Cape Town']],
                ]
            ],
        ];

        foreach ($countries as $cData) {
            $states = $cData['states'] ?? [];
            unset($cData['states']);

            $country = Country::firstOrCreate(['iso_code' => $cData['iso_code']], $cData);

            foreach ($states as $sData) {
                $cities = $sData['cities'] ?? [];
                unset($sData['cities']);

                $state = $country->states()->firstOrCreate(['name' => $sData['name']], $sData);

                foreach ($cities as $cityName) {
                    $state->cities()->firstOrCreate(['name' => $cityName]);
                }
            }
        }
    }
}
