<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015, 2016, 2017  Leon Jacobs
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

namespace Seat\Eveapi\Jobs\Assets\Corporation;

use Seat\Eveapi\Jobs\EsiBase;
use Seat\Eveapi\Models\Assets\CorporationAsset;
use Seat\Eveapi\Models\RefreshToken;

/**
 * Class Assets
 * @package Seat\Eveapi\Jobs\Assets\Corporation
 */
class Assets extends EsiBase
{
    /**
     * @var string
     */
    protected $method = 'get';

    /**
     * @var string
     */
    protected $endpoint = '/corporations/{corporation_id}/assets/';

    /**
     * @var string
     */
    protected $version = 'v2';

    /**
     * @var string
     */
    protected $scope = 'esi-assets.read_corporation_assets.v1';

    /**
     * @var array
     */
    protected $tags = ['corporation', 'assets'];

    /**
     * @var int
     */
    protected $page = 1;

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $known_assets;

    /**
     * Assets constructor.
     *
     * @param \Seat\Eveapi\Models\RefreshToken|null $token
     */
    public function __construct(RefreshToken $token = null)
    {

        $this->known_assets = collect();

        parent::__construct($token);
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Throwable
     */
    public function handle(): void
    {

        if (! $this->authenticated()) return;

        while (true) {

            $assets = $this->retrieve([
                'corporation_id' => $this->getCorporationId(),
            ]);

            collect($assets)->each(function ($asset) {

                CorporationAsset::firstOrNew([
                    'item_id'        => $asset->item_id,
                    'corporation_id' => $this->getCorporationId(),
                ])->fill([
                    'type_id'       => $asset->type_id,
                    'quantity'      => $asset->quantity,
                    'location_id'   => $asset->location_id,
                    'location_type' => $asset->location_type,
                    'location_flag' => $asset->location_flag,
                    'is_singleton'  => $asset->is_singleton,
                ])->save();
            });

            // Update the list of known item_id's which should be
            // excluded from the databse cleanup later.
            $this->known_assets->push(collect($assets)
                ->pluck('item_id')->flatten()->all());

            if (! $this->nextPage($assets->pages))
                break;
        }

        // Cleanup old assets
        CorporationAsset::where('corporation_id', $this->getCorporationId())
            ->whereNotIn('item_id', $this->known_assets->flatten()->all())
            ->delete();
    }
}
