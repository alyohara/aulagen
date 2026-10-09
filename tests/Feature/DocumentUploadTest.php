<?php

namespace Tests\Feature;

use App\Jobs\ProcessDocument;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploading_a_document_queues_processing(): void
    {
        Storage::fake('local');
        Queue::fake();

        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->create(['owner_id' => $teacher->id]);
        $file = UploadedFile::fake()->create('lesson-notes.txt', 12, 'text/plain');

        $this->actingAs($teacher)
            ->post(route('courses.documents.store', $course), ['files' => [$file]])
            ->assertRedirect();

        $document = $course->documents()->sole();

        $this->assertSame('pending', $document->status->value);
        Storage::disk('local')->assertExists($document->path);
        Queue::assertPushed(ProcessDocument::class, fn (ProcessDocument $job) => $job->document->is($document));
    }
}
