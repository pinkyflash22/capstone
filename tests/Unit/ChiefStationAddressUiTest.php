<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChiefStationAddressUiTest extends TestCase
{
    #[Test]
    public function chief_can_edit_and_persist_the_assigned_station_address_hierarchy(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Chief/ManagementController.php'));
        $stationModel = file_get_contents(app_path('Models/Station.php'));
        $view = file_get_contents(resource_path('views/chief/management.blade.php'));
        $script = file_get_contents(resource_path('js/chief/management.js'));

        $this->assertStringContainsString("'address.barangay.city.province'", $controller);
        foreach (['province_id', 'city_id', 'barangay_id', 'street'] as $field) {
            $this->assertStringContainsString("'{$field}'", $controller);
        }
        $this->assertStringContainsString("Rule::exists('cities', 'city_id')", $controller);
        $this->assertStringContainsString("Rule::exists('barangays', 'barangay_id')", $controller);
        $this->assertStringContainsString('FullAddress::firstOrCreate', $controller);
        $this->assertStringContainsString("'address_id' => \$address->address_id", $controller);
        $this->assertStringContainsString('DB::transaction', $controller);
        $this->assertStringContainsString('forgetStationManagementCaches', $controller);
        $this->assertStringContainsString('catch (\\Throwable $exception)', $controller);
        $this->assertStringContainsString('public $timestamps = false;', $stationModel);

        foreach (['editStationProvince', 'editStationCity', 'editStationBarangay', 'editStationStreet'] as $id) {
            $this->assertStringContainsString("id=\"{$id}\"", $view);
            $this->assertStringContainsString($id, $script);
        }
        $this->assertStringContainsString('/api/cities/', $script);
        $this->assertStringContainsString('/api/barangays/', $script);
        $this->assertStringContainsString('Station address not configured', $view);
        $this->assertStringContainsString("->with('city.province')", $controller);
        $this->assertStringContainsString('$coverageCities->count() === 1', $view);
        $this->assertStringContainsString('Suggested from active coverage', $view);
        $this->assertStringContainsString('data-address-suggested', $view);
        $this->assertStringContainsString('citySelect?.value && barangaySelect?.disabled', $script);
    }
}
