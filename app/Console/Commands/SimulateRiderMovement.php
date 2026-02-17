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
        $this->info('Starting rider movement simulation with Landmark Standby...');

        $landmarks = [
            ['name' => 'Jollibee', 'lat' => 14.5995, 'lng' => 120.9842],
            ['name' => 'SM Manila', 'lat' => 14.5917, 'lng' => 120.9814],
            ['name' => 'Luneta', 'lat' => 14.5826, 'lng' => 120.9787],
            ['name' => 'Quiapo', 'lat' => 14.5989, 'lng' => 120.9833],
        ];

        while (true) {
            $riders = Rider::where('status', '!=', 'offline')->get();

            foreach ($riders as $index => $rider) {
                // If it's an even index, make them standby at a landmark
                if ($index % 2 === 0) {
                    $targetLandmark = $landmarks[$index % count($landmarks)];

                    // Slightly jitter around the landmark to show life
                    $lat = $targetLandmark['lat'] + (rand(-5, 5) / 100000);
                    $lng = $targetLandmark['lng'] + (rand(-5, 5) / 100000);

                    $rider->update([
                        'lat' => $lat,
                        'lng' => $lng,
                    ]);
                }
                else {
                    // Moving riders
                    $latChange = (rand(-15, 15) / 100000);
                    $lngChange = (rand(-15, 15) / 100000);

                    $rider->update([
                        'lat' => $rider->lat + $latChange,
                        'lng' => $rider->lng + $lngChange,
                    ]);
                }
            }

            sleep(3);
        }
    }
}
