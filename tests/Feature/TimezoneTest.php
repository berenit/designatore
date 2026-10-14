<?php

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

test('the app runs on italian time', function () {
    expect(config('app.timezone'))->toBe('Europe/Rome')
        ->and(now()->getTimezone()->getName())->toBe('Europe/Rome');
});

test('guests see the designations from thursday at midnight italian time', function () {
    createDesignation(User::factory()->create(), ['status' => 'confirmed'])
        ->match->update(['date_time' => Carbon::parse('2026-10-18 15:00:00')]);

    $this->travelTo(Carbon::parse('2026-10-14 23:59:00', 'Europe/Rome'));
    $this->get(route('home'))->assertOk()->assertDontSee('Mario Rossi');

    $this->travelTo(Carbon::parse('2026-10-15 00:00:00', 'Europe/Rome'));
    $this->get(route('home'))->assertOk()->assertSee('Mario Rossi');
});

test('the timezone migration converts recorded timestamps from utc to italian time and back', function () {
    $designation = createDesignation(User::factory()->create());
    $match = $designation->match;

    // Ora legale (+2) e ora solare (+1); la data della partita è già in ora italiana e non va toccata
    DB::table('designations')->where('id', $designation->id)->update([
        'notified_at' => '2026-10-13 22:30:00',
        'created_at' => '2026-12-01 10:00:00',
        'escalated_at' => null,
    ]);
    DB::table('matches')->where('id', $match->id)->update(['date_time' => '2026-10-18 15:00:00']);

    $migration = require database_path('migrations/2026_10_15_000000_convert_timestamps_to_europe_rome.php');
    $migration->up();

    $row = DB::table('designations')->find($designation->id);
    expect($row->notified_at)->toBe('2026-10-14 00:30:00')
        ->and($row->created_at)->toBe('2026-12-01 11:00:00')
        ->and($row->escalated_at)->toBeNull()
        ->and(DB::table('matches')->find($match->id)->date_time)->toBe('2026-10-18 15:00:00');

    $migration->down();

    $row = DB::table('designations')->find($designation->id);
    expect($row->notified_at)->toBe('2026-10-13 22:30:00')
        ->and($row->created_at)->toBe('2026-12-01 10:00:00');
});
