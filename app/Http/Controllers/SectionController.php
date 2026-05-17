<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SectionController extends Controller
{
    public function __construct()
    {
        abort_unless(Auth::user()?->isSecretary(), 403);
    }

    public function index()
    {
        return view('admin.sections.index', [
            'sections' => Section::withCount('users')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.sections.form', ['section' => new Section]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:sections,name'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        Section::create($validated);

        return redirect()->route('admin.sections.index')
            ->with('status', 'Section created successfully.');
    }

    public function edit(Section $section)
    {
        return view('admin.sections.form', ['section' => $section]);
    }

    public function update(Request $request, Section $section): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', "unique:sections,name,{$section->id}"],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $section->update($validated);

        return redirect()->route('admin.sections.index')
            ->with('status', 'Section updated successfully.');
    }

    public function destroy(Section $section): RedirectResponse
    {
        if ($section->users()->exists()) {
            return back()->withErrors(['section' => 'Cannot delete a section that has users assigned to it. Reassign those users first.']);
        }

        $section->delete();

        return redirect()->route('admin.sections.index')
            ->with('status', 'Section deleted.');
    }
}
