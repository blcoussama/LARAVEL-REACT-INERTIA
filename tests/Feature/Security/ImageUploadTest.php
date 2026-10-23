<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->user = User::factory()->create();
});

function uploadProject(UploadedFile $file)
{
    return test()->actingAs(test()->user)
        ->from('/project/create')
        ->post('/project', ['name' => 'Upload test', 'status' => 'pending', 'image' => $file]);
}

test('a valid image is stored on the public disk', function () {
    uploadProject(UploadedFile::fake()->image('cover.jpg', 800, 400)->size(500))
        ->assertRedirect('/project');

    $project = Project::where('name', 'Upload test')->firstOrFail();
    Storage::disk('public')->assertExists($project->image_path);
});

test('images larger than 2 MB are rejected', function () {
    uploadProject(UploadedFile::fake()->image('huge.jpg')->size(3000))
        ->assertSessionHasErrors('image');

    expect(Project::count())->toBe(0);
});

test('files that are not images are rejected', function (UploadedFile $file) {
    uploadProject($file)->assertSessionHasErrors('image');

    expect(Project::count())->toBe(0);
})->with([
    'php script' => fn () => UploadedFile::fake()->create('shell.php', 1, 'application/x-php'),
    'svg with script' => fn () => UploadedFile::fake()->createWithContent(
        'logo.svg',
        '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'
    ),
    'pdf' => fn () => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
]);
