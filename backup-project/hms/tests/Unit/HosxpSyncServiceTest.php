<?php

namespace Tests\Unit;

use App\Models\Patient;
use App\Services\HosxpSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class HosxpSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_skips_out_of_range_bmi_values_from_his(): void
    {
        $service = app(HosxpSyncService::class);
        $method = new ReflectionMethod($service, 'upsertPatientFromHisRow');
        $method->setAccessible(true);

        $method->invoke($service, [
            'hn' => '00012345',
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'weight_kg' => '45',
            'height_cm' => '12222.222',
            'bmi' => '12222.222',
        ]);

        $patient = Patient::query()->where('hn', '00012345')->firstOrFail();

        $this->assertSame(45.0, (float) $patient->weight_kg);
        $this->assertNull($patient->height_cm);
        $this->assertNull($patient->bmi);
    }
}
