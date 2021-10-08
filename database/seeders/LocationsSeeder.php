<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use PragmaRX\Countries\Package\Countries;
use App\Models\Country;
use App\Models\State;
use App\Models\Continent;

class LocationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //seed countries
        $countries = new Countries();
        $all = $countries->all();

        foreach($all as $country) {
            $countryModel = Country::create([
                "name" => $country->name->common,
                "iso_code" => $country->cca2,
            ]);
            //seed states
           /* $states = $country->hydrateStates()->states->pluck('name', 'postal')->toArray();
            foreach($states as $code => $name) {
                $state = State::create([
                    "name" => is_null($name) ? $code : $name,
                    "country_id" => $countryModel->id,
                ]);
            }*/
        }

        //seed continents
        Continent::create([
            "name" => "Africa",
            "iso_code" => "AF",
        ]);

        Continent::create([
            "name" => "North America",
            "iso_code" => "NA",
        ]);

        Continent::create([
            "name" => "Oceania",
            "iso_code" => "OC",
        ]);

        Continent::create([
            "name" => "Antarctica",
            "iso_code" => "AN",
        ]);

        Continent::create([
            "name" => "Asia",
            "iso_code" => "AS",
        ]);

        Continent::create([
            "name" => "Europe",
            "iso_code" => "EU",
        ]);

        Continent::create([
            "name" => "South America",
            "iso_code" => "SA",
        ]);
    }
}
