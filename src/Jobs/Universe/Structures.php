<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015, 2016, 2017, 2018  Leon Jacobs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace Seat\Eveapi\Jobs\Universe;

use Seat\Eseye\Exceptions\RequestFailedException;
use Seat\Eveapi\Jobs\EsiBase;
use Seat\Eveapi\Models\Assets\CharacterAsset;
use Seat\Eveapi\Models\Sde\StaStation;
use Seat\Eveapi\Models\Universe\UniverseStation;
use Seat\Eveapi\Models\Universe\UniverseStructure;

/**
 * Class Structures
 * @package Seat\Eveapi\Jobs\Universe
 */
class Structures extends EsiBase
{

    /**
     * @var string
     */
    protected $method = 'get';

    /**
     * @var string
     */
    protected $endpoint = '/universe/structures/{structure_id}/';

    /**
     * @var string
     */
    protected $version = 'v1';

    /**
     * @var array
     */
    protected $tags = ['character', 'universe', 'structures'];

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Throwable
     */
    public function handle()
    {

        $character_assets = CharacterAsset::where('character_id', $this->getCharacterId())
            ->where('location_flag', 'Hangar')
            ->whereNotIn('location_id', UniverseStation::all()
                ->pluck('station_id')->flatten())
            ->whereNotIn('location_id', StaStation::all()
                ->pluck('stationID')->flatten())
            ->select('location_id')
            ->distinct()
            ->get();

        foreach ($character_assets as $character_asset) {

            try {

                $structure = $this->retrieve([
                    'structure_id' => $character_asset->location_id,
                ]);

                UniverseStructure::firstOrNew([
                    'structure_id'    => $character_asset->location_id,
                    'name'            => $structure->name,
                    'solar_system_id' => $structure->solar_system_id,
                    'x'               => $structure->position->x,
                    'y'               => $structure->position->y,
                    'z'               => $structure->position->z,
                ])->save();

            } catch (RequestFailedException $e) {

                // Failure to grab the structure should result in us creating an
                // empty entry in the database for this structure.

                UniverseStructure::firstOrNew([
                    'structure_id' => $character_asset->location_id,
                ])->fill([
                    'name'            => 'Unknown structure',
                    'solar_system_id' => 0,
                    'x'               => 0.0,
                    'y'               => 0.0,
                    'z'               => 0.0,
                ])->save();
            }
        }
    }
}
