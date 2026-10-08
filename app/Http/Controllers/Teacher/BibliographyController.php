<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Bibliography;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BibliographyController extends Controller
{
    public function index(Course $course)
    {
        $this->authorize('view', $course);

        return [
            'entries' => $course->bibliography()->get()->map(fn (Bibliography $entry) => [
                'id' => $entry->id,
                'kind' => $entry->kind,
                'authors' => $entry->authors,
                'title' => $entry->title,
                'edition' => $entry->edition,
                'publisher' => $entry->publisher,
                'year' => $entry->year,
                'url' => $entry->url,
                'note' => $entry->note,
                'formatted' => $entry->formatted(),
            ]),
        ];
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('update', $course);

        $data = $request->validate([
            'kind' => ['required', 'string', 'in:principal,complementaria'],
            'authors' => ['nullable', 'string', 'max:250'],
            'title' => ['required', 'string', 'max:300'],
            'edition' => ['nullable', 'string', 'max:120'],
            'publisher' => ['nullable', 'string', 'max:180'],
            'year' => ['nullable', 'string', 'max:20'],
            'url' => ['nullable', 'url:http,https', 'max:500'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $course->bibliography()->create(array_merge($data, [
            'position' => ((int) $course->bibliography()->max('position')) + 1,
        ]));

        return back()->with('success', 'Referencia agregada.');
    }

    public function update(Request $request, Course $course, Bibliography $bibliography): RedirectResponse
    {
        $this->authorize('update', $course);

        abort_unless($bibliography->course_id === $course->id, 404);

        $data = $request->validate([
            'kind' => ['sometimes', 'string', 'in:principal,complementaria'],
            'authors' => ['nullable', 'string', 'max:250'],
            'title' => ['sometimes', 'string', 'max:300'],
            'edition' => ['nullable', 'string', 'max:120'],
            'publisher' => ['nullable', 'string', 'max:180'],
            'year' => ['nullable', 'string', 'max:20'],
            'url' => ['nullable', 'url:http,https', 'max:500'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $bibliography->fill($data)->save();

        return back()->with('success', 'Referencia actualizada.');
    }

    public function destroy(Course $course, Bibliography $bibliography): RedirectResponse
    {
        $this->authorize('update', $course);

        abort_unless($bibliography->course_id === $course->id, 404);

        $bibliography->delete();

        return back()->with('success', 'Referencia eliminada.');
    }
}
