<?php

declare(strict_types=1);

use App\Actions\ToolboxTalks\BuildToolboxTalkDeck;
use App\Actions\ToolboxTalks\CopyToolboxTalkFromDate;
use App\Actions\ToolboxTalks\LoadStandardToolboxReminders;
use App\Livewire\Admin\ToolboxTalks;
use App\Livewire\Attendant\ToolboxTalkPresent;
use App\Models\CarPark;
use App\Models\ToolboxTalk;
use App\Models\User;
use Livewire\Livewire;

function makeCarPark(string $name = 'North'): CarPark
{
    return CarPark::query()->create([
        'name' => $name,
        'capacity' => 100,
        'location' => 'Test',
    ]);
}

test('scan page includes resource links for lessons feedback and toolbox talk', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('scan.access');

    $this->actingAs($user)
        ->get(route('attendant.scan'))
        ->assertOk()
        ->assertSee(route('management.lessons-learned'), false)
        ->assertSee(route('management.toolbox-feedback'), false)
        ->assertSee(route('attendant.toolbox-talk'), false);
});

test('admin toolbox talks page requires permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.toolbox-talks'))
        ->assertForbidden();

    $user->givePermissionTo('toolbox-talks.view');

    $this->actingAs($user)
        ->get(route('admin.toolbox-talks'))
        ->assertOk();
});

test('admin can save core slides and copy from yesterday', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('toolbox-talks.view');

    $yesterday = now()->subDay()->toDateString();
    $today = now()->toDateString();

    $source = ToolboxTalk::firstOrCreateCore($yesterday);
    $source->slides()->create([
        'sort_order' => 0,
        'title' => 'Hydration',
        'body' => 'Drink water',
    ]);

    Livewire::actingAs($admin)
        ->test(ToolboxTalks::class)
        ->set('talkDate', $today)
        ->set('activeTab', 'core')
        ->call('copyFromYesterday')
        ->assertHasNoErrors()
        ->assertSet('slides.0.title', 'Hydration');

    $todayCore = ToolboxTalk::findCoreForDate($today);
    expect($todayCore)->not->toBeNull()
        ->and($todayCore->slides)->toHaveCount(1)
        ->and($todayCore->slides->first()->title)->toBe('Hydration');
});

test('present deck concatenates core then park add-on in order', function () {
    $park = makeCarPark('Rosebine');
    $date = now()->toDateString();

    $core = ToolboxTalk::firstOrCreateCore($date);
    $core->slides()->create(['sort_order' => 0, 'title' => 'Core One', 'body' => 'A']);
    $core->slides()->create(['sort_order' => 1, 'title' => 'Core Two', 'body' => 'B']);

    $parkTalk = ToolboxTalk::firstOrCreatePark($date, $park->id);
    $parkTalk->slides()->create(['sort_order' => 0, 'title' => 'Park Gate', 'body' => 'C']);

    $deck = app(BuildToolboxTalkDeck::class)->handle($date, $park->id);

    expect($deck)->toHaveCount(4)
        ->and($deck[0]['title'])->toBe('Core One')
        ->and($deck[0]['section'])->toBe('core')
        ->and($deck[1]['title'])->toBe('Core Two')
        ->and($deck[2]['type'])->toBe('cover')
        ->and($deck[2]['title'])->toBe('Rosebine')
        ->and($deck[3]['title'])->toBe('Park Gate')
        ->and($deck[3]['section'])->toBe('park');
});

test('toolbox talk covers are assigned per car park from the image pool', function () {
    $resolver = app(\App\Actions\ToolboxTalks\ResolveToolboxTalkCover::class);
    $west = makeCarPark('West');
    $north = makeCarPark('North');
    $rose = makeCarPark('Rosebine Cover');

    $paths = [
        $resolver->relativePath($west->id),
        $resolver->relativePath($north->id),
        $resolver->relativePath($rose->id),
    ];

    expect($paths)->toHaveCount(3)
        ->and(count(array_unique($paths)))->toBeGreaterThanOrEqual(2)
        ->and(is_file($resolver->absolutePath($west->id)))->toBeTrue()
        ->and(is_file($resolver->absolutePath(null)))->toBeTrue();
});

test('load standard reminders seeds core slides from lang', function () {
    $date = now()->toDateString();

    $count = app(LoadStandardToolboxReminders::class)->handle($date);
    expect($count)->toBeGreaterThan(0);

    $core = ToolboxTalk::findCoreForDate($date);
    expect($core)->not->toBeNull()
        ->and($core->slides->count())->toBe($count)
        ->and($core->slides->first()->title)->not->toBe('');
});

test('present page is available to attendants with scan access', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('scan.access');
    $date = now()->toDateString();

    ToolboxTalk::firstOrCreateCore($date)->slides()->create([
        'sort_order' => 0,
        'title' => 'Welcome',
        'body' => 'Hello',
    ]);

    $this->actingAs($user)
        ->get(route('attendant.toolbox-talk.present', ['date' => $date]))
        ->assertOk()
        ->assertSee('Welcome');

    Livewire::actingAs($user)
        ->test(ToolboxTalkPresent::class, ['date' => $date])
        ->assertSet('deck.0.title', 'Welcome')
        ->call('next')
        ->assertSet('index', 0);
});

test('copy action replaces slides when confirmed via admin overwrite flow', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('toolbox-talks.view');

    $yesterday = now()->subDay()->toDateString();
    $today = now()->toDateString();

    ToolboxTalk::firstOrCreateCore($yesterday)->slides()->create([
        'sort_order' => 0,
        'title' => 'From Yesterday',
        'body' => 'Y',
    ]);

    ToolboxTalk::firstOrCreateCore($today)->slides()->create([
        'sort_order' => 0,
        'title' => 'Existing',
        'body' => 'X',
    ]);

    Livewire::actingAs($admin)
        ->test(ToolboxTalks::class)
        ->set('talkDate', $today)
        ->call('copyFromYesterday')
        ->assertSet('confirmOverwriteCopy', true)
        ->call('copyFromYesterday')
        ->assertSet('confirmOverwriteCopy', false)
        ->assertSet('slides.0.title', 'From Yesterday');
});

test('full download deck includes core and every car park with cover dividers', function () {
    $date = now()->toDateString();
    $west = makeCarPark('West Full');
    $north = makeCarPark('North Full');

    ToolboxTalk::firstOrCreateCore($date)->slides()->create([
        'sort_order' => 0,
        'title' => 'Core Safety',
        'body' => 'Shared',
    ]);
    ToolboxTalk::firstOrCreatePark($date, $west->id)->slides()->create([
        'sort_order' => 0,
        'title' => 'West Gate',
        'body' => 'West note',
    ]);
    ToolboxTalk::firstOrCreatePark($date, $north->id)->slides()->create([
        'sort_order' => 0,
        'title' => 'North Stack',
        'body' => 'North note',
    ]);

    $deck = app(BuildToolboxTalkDeck::class)->handleFull($date);
    $titles = array_column($deck, 'title');

    expect($titles)->toContain('Core Safety')
        ->and($titles)->toContain('West Full')
        ->and($titles)->toContain('West Gate')
        ->and($titles)->toContain('North Full')
        ->and($titles)->toContain('North Stack');

    $covers = array_values(array_filter($deck, fn (array $s): bool => ($s['type'] ?? '') === 'cover'));
    expect($covers)->toHaveCount(2);
});

test('admin can download toolbox talk powerpoint for a date', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('toolbox-talks.view');
    $date = now()->toDateString();
    $park = makeCarPark('Download Park');

    ToolboxTalk::firstOrCreateCore($date)->slides()->create([
        'sort_order' => 0,
        'title' => 'Safety First',
        'body' => "Every space counts.\n• Drink water\n• Stay shaded",
    ]);
    ToolboxTalk::firstOrCreatePark($date, $park->id)->slides()->create([
        'sort_order' => 0,
        'title' => 'Park Note',
        'body' => 'Local tip',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.toolbox-talks.download-pptx', ['date' => $date]));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('.pptx')
        ->and($response->headers->get('content-disposition'))->toContain('-full.pptx')
        ->and($response->headers->get('content-type'))->toContain('presentationml.presentation');

    $tmp = tempnam(sys_get_temp_dir(), 'pptx-');
    expect($tmp)->not->toBeFalse();
    $pptxPath = $tmp.'.pptx';
    @unlink($tmp);
    file_put_contents($pptxPath, $response->streamedContent());

    $zip = new ZipArchive;
    expect($zip->open($pptxPath))->toBeTrue();

    $allXml = '';
    $slideXml = '';
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (! is_string($name) || ! str_starts_with($name, 'ppt/slides/slide') || ! str_ends_with($name, '.xml')) {
            continue;
        }
        $xml = $zip->getFromIndex($i);
        if (! is_string($xml)) {
            continue;
        }
        $allXml .= $xml;
        if (str_contains($xml, 'Drink water')) {
            $slideXml = $xml;
        }
    }
    $zip->close();
    @unlink($pptxPath);

    expect($slideXml)->not->toBe('')
        ->and($slideXml)->toContain('<a:buChar')
        ->and($slideXml)->toContain('<a:normAutofit')
        ->and($slideXml)->toContain('marL="266700"')
        ->and($allXml)->toContain('Download Park')
        ->and($allXml)->toContain('Park Note');
});

test('admin can download toolbox talk pdf for a date', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('toolbox-talks.view');
    $date = now()->toDateString();
    $park = makeCarPark('Pdf Park');

    ToolboxTalk::firstOrCreateCore($date)->slides()->create([
        'sort_order' => 0,
        'title' => 'Hydration',
        'body' => "Stay cool.\n• Drink water",
    ]);
    ToolboxTalk::firstOrCreatePark($date, $park->id)->slides()->create([
        'sort_order' => 0,
        'title' => 'Pdf Gate',
        'body' => 'Gate note',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.toolbox-talks.download-pdf', ['date' => $date]));

    $response->assertOk();
    $content = $response->streamedContent();
    $expectedPages = count(app(BuildToolboxTalkDeck::class)->handleFull($date)) + 1;
    $pdfPages = preg_match_all('/\/Type\s*\/Page[^s]/', $content);

    expect($response->headers->get('content-disposition'))->toContain('.pdf')
        ->and($response->headers->get('content-type'))->toContain('application/pdf')
        ->and(str_starts_with($content, '%PDF'))->toBeTrue()
        ->and($pdfPages)->toBe($expectedPages);
});

test('powerpoint export shrinks dense slides so text stays in bounds', function () {
    $date = now()->toDateString();
    ToolboxTalk::firstOrCreateCore($date)->slides()->create([
        'sort_order' => 0,
        'title' => 'Weather — Staying Safe On Duty',
        'body' => "August sun and sudden rain — be ready for both.\n"
            ."• Heat: wear a hat, keep fluids with you, and watch for dehydration and hot tarmac underfoot.\n"
            ."• Unwell: if you feel dizzy, sick, or overheating, tell your Key Man immediately and step out of the sun.\n"
            ."• Rain: keep umbrellas ready for guests, and make sure your hi-vis stays visible in poor light.\n"
            .'• Whatever the weather, your hi-vis stays on — it is how drivers see you before they move.',
    ]);

    $pptx = app(\App\Actions\ToolboxTalks\ExportToolboxTalkPowerpoint::class)->handle($date);
    $tmp = tempnam(sys_get_temp_dir(), 'pptx-dense-');
    expect($tmp)->not->toBeFalse();
    $path = $tmp.'.pptx';
    @unlink($tmp);
    file_put_contents($path, $pptx['content']);

    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue();
    $slideXml = '';
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $xml = $zip->getFromIndex($i);
        $name = $zip->getNameIndex($i);
        if (is_string($name) && str_contains($name, 'ppt/slides/slide') && is_string($xml) && str_contains($xml, 'hi-vis stays on')) {
            $slideXml = $xml;
            break;
        }
    }
    $zip->close();
    @unlink($path);

    expect($slideXml)->not->toBe('')
        ->and($slideXml)->toContain('<a:normAutofit')
        ->and($slideXml)->not->toContain('<a:spAutoFit');

    preg_match_all('/sz="(\d+)"/', $slideXml, $sizes);
    $bodySizes = array_map(fn ($s) => ((int) $s) / 100, $sizes[1] ?? []);
    // Dense content should drop below the roomy 18pt body size.
    expect(min($bodySizes))->toBeLessThanOrEqual(16);
});

test('copy toolbox talk from date action copies matching deck key only', function () {
    $park = makeCarPark('West');
    $yesterday = now()->subDay()->toDateString();
    $today = now()->toDateString();

    ToolboxTalk::firstOrCreateCore($yesterday)->slides()->create([
        'sort_order' => 0,
        'title' => 'Core Y',
        'body' => null,
    ]);
    ToolboxTalk::firstOrCreatePark($yesterday, $park->id)->slides()->create([
        'sort_order' => 0,
        'title' => 'Park Y',
        'body' => null,
    ]);

    $target = ToolboxTalk::firstOrCreatePark($today, $park->id);
    $copied = app(CopyToolboxTalkFromDate::class)->handle($target, $yesterday);

    expect($copied)->toBe(1)
        ->and($target->fresh()->slides->first()->title)->toBe('Park Y');
});
