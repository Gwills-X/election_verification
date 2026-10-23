<?php

namespace Database\Seeders;

use App\Models\LocalGovernment;
use App\Models\State;
use Illuminate\Database\Seeder;

class GeographySeeder extends Seeder
{
    private const STATES = [
        ['Abia', 'AB'], ['Adamawa', 'AD'], ['Akwa Ibom', 'AK'], ['Anambra', 'AN'],
        ['Bauchi', 'BA'], ['Bayelsa', 'BY'], ['Benue', 'BE'], ['Borno', 'BO'],
        ['Cross River', 'CR'], ['Delta', 'DE'], ['Ebonyi', 'EB'], ['Edo', 'ED'],
        ['Ekiti', 'EK'], ['Enugu', 'EN'], ['Gombe', 'GO'], ['Imo', 'IM'],
        ['Jigawa', 'JI'], ['Kaduna', 'KD'], ['Kano', 'KN'], ['Katsina', 'KT'],
        ['Kebbi', 'KB'], ['Kogi', 'KG'], ['Kwara', 'KW'], ['Lagos', 'LA'],
        ['Nasarawa', 'NA'], ['Niger', 'NI'], ['Ogun', 'OG'], ['Ondo', 'ON'],
        ['Osun', 'OS'], ['Oyo', 'OY'], ['Plateau', 'PL'], ['Rivers', 'RI'],
        ['Sokoto', 'SO'], ['Taraba', 'TA'], ['Yobe', 'YO'], ['Zamfara', 'ZA'],
        ['Federal Capital Territory', 'FC'],
    ];

    private const BAUCHI_LOCAL_GOVERNMENTS = [
        'Alkaleri', 'Bauchi', 'Bogoro', 'Dambam', 'Darazo', 'Dass', 'Gamawa',
        'Ganjuwa', 'Giade', 'Itas/Gadau', "Jama'are", 'Katagum', 'Kirfi',
        'Misau', 'Ningi', 'Shira', 'Tafawa Balewa', 'Toro', 'Warji', 'Zaki',
    ];

    public function run(): void
    {
        foreach (self::STATES as [$name, $code]) {
            $state = State::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();

            if (!$state) {
                $state = new State();
            }

            $state->fill([
                'name' => $name,
                'code' => $code,
                'country_code' => 'NGA',
            ])->save();
        }

        $bauchi = State::where('code', 'BA')->firstOrFail();
        foreach (self::BAUCHI_LOCAL_GOVERNMENTS as $name) {
            $localGovernment = LocalGovernment::query()
                ->where('state_id', $bauchi->id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();

            if (!$localGovernment) {
                $localGovernment = new LocalGovernment([
                    'state_id' => $bauchi->id,
                ]);
            }

            $localGovernment->name = $name;
            $localGovernment->save();
        }
    }
}
