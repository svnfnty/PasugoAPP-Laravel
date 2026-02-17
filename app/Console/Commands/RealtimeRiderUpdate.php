<?php

namespace App\Console\Commands;

use App\Models\Rider;
use App\Events\RiderLocationUpdated;
use Illuminate\Console\Command;

class RealtimeRiderUpdate extends Command
{
    protected $signature = 'riders:realtime';
    protected $description = 'Simulate REAL-TIME event-driven movement of riders (Production-Ready Loop)';

    public function handle()
    {
        $this->info('Starting production-ready real-time update engine (Event-Driven)...');

        while (true) {
            $riders = Rider::where('status', '!=', 'offline')->get();

            foreach ($riders as $rider) {
                // Jitter movement
                $latChange = (rand(-20, 20) / 100000);
                $lngChange = (rand(-20, 20) / 100000);

                $newLat = $rider->lat + $latChange;
                $newLng = $rider->lng + $lngChange;

                // Update DB (Persistent state)
                $rider->update(['lat' => $newLat, 'lng' => $newLng]);

                // BROADCAST EVENT (The "Real-time" part for Echo)
                broadcast(new RiderLocationUpdated($rider->id, $newLat, $newLng, $rider->status));

                $this->line("Rider {$rider->name} broadcasted new position.");
            }

            sleep(3);
        }
    }
}
