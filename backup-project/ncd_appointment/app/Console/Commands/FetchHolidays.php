<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FetchHolidays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-holidays {year?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Thai public holidays from API and save to database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $year = $this->argument('year') ?? date('Y');
        $this->info("Fetching public holidays for Thailand in {$year}...");

        $icsUrl = 'https://calendar.google.com/calendar/ical/th.th%23holiday%40group.v.calendar.google.com/public/basic.ics';
        
        try {
            $response = \Illuminate\Support\Facades\Http::get($icsUrl);
            
            if ($response->successful()) {
                $lines = explode("\n", $response->body());
                $count = 0;
                $currentEvent = null;

                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === 'BEGIN:VEVENT') {
                        $currentEvent = [];
                    } elseif ($line === 'END:VEVENT') {
                        if (isset($currentEvent['DTSTART']) && isset($currentEvent['SUMMARY'])) {
                            if (substr($currentEvent['DTSTART'], 0, 4) == $year) {
                                $date = substr($currentEvent['DTSTART'], 0, 4) . '-' . substr($currentEvent['DTSTART'], 4, 2) . '-' . substr($currentEvent['DTSTART'], 6, 2);
                                \App\Models\Holiday::updateOrCreate(
                                    ['date' => $date],
                                    ['name' => $currentEvent['SUMMARY']]
                                );
                                $count++;
                            }
                        }
                        $currentEvent = null;
                    } elseif ($currentEvent !== null) {
                        if (strpos($line, 'DTSTART;VALUE=DATE:') === 0) {
                            $currentEvent['DTSTART'] = substr($line, 19);
                        } elseif (strpos($line, 'DTSTART:') === 0) {
                            $currentEvent['DTSTART'] = substr($line, 8, 8); 
                        } elseif (strpos($line, 'SUMMARY:') === 0) {
                            $currentEvent['SUMMARY'] = substr($line, 8);
                        }
                    }
                }

                $this->info("Successfully fetched and seeded {$count} holidays for {$year}.");
            } else {
                $this->error("Failed to fetch holidays calendar. Status code: " . $response->status());
            }
        } catch (\Exception $e) {
            $this->error("An error occurred: " . $e->getMessage());
        }
    }
}
