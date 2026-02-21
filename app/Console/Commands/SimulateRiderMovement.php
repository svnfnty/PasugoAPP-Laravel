<?php

namespace App\Console\Commands;

use App\Models\Rider;
use Illuminate\Console\Command;

class SimulateRiderMovement extends Command
{
    protected $signature = 'riders:simulate';
    protected $description = 'Simulate realtime movement of riders on the map';

    public function handle()
    {
        while (true) {
            $riders = Rider::where('status', '!=', 'offline')->get();

            foreach ($riders as $rider) {
                // Moving riders randomly
                $latChange = (rand(-15, 15) / 100000);
                $lngChange = (rand(-15, 15) / 100000);

                $rider->update([
                    'lat' => $rider->lat + $latChange,
                    'lng' => $rider->lng + $lngChange,
                ]);
            }

            sleep(3);
        }
    }
}
