<?php

namespace App\Http\Controllers;

use App\Models\FollowUpItem;
use App\Models\ItemDocument;
use App\Models\ItemHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ItemDocumentController extends Controller
{
    public function store(Request $request, FollowUpItem $item): RedirectResponse
    {
        $this->authorize('update', $item);

        $validated = $request->validate([
            'document' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png'],
            'category' => ['required', Rule::in(ItemDocument::CATEGORIES)],
            'document_label' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $validated['document'];
        $storedName = now()->format('YmdHis').'_'.$file->hashName();
        $storagePath = $file->storeAs("items/{$item->id}", $storedName, 'local');
        $documentLabel = trim((string) ($validated['document_label'] ?? '')) ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $versionNumber = (int) $item->documents()
            ->where('category', $validated['category'])
            ->where('document_label', $documentLabel)
            ->max('version_number') + 1;

        if ($request->boolean('is_primary')) {
            $item->documents()->update(['is_primary' => false]);
        }

        $document = $item->documents()->create([
            'category' => $validated['category'],
            'document_label' => $documentLabel,
            'version_number' => $versionNumber,
            'is_primary' => $request->boolean('is_primary'),
            'user_id' => Auth::id(),
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'storage_path' => $storagePath,
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'description' => $validated['description'] ?? null,
        ]);

        ItemHistory::create([
            'follow_up_item_id' => $item->id,
            'user_id' => Auth::id(),
            'event_type' => 'document_added',
            'old_status' => $item->status,
            'new_status' => $item->status,
            'action_summary' => "Document added: {$document->document_label} v{$document->version_number}",
            'notes' => trim('Category: '.str_replace('_', ' ', $document->category).($document->description ? ' Notes: '.$document->description : '')),
        ]);

        return back()->with('status', 'Document uploaded successfully.');
    }

    public function setPrimary(FollowUpItem $item, ItemDocument $document): RedirectResponse
    {
        $this->authorize('update', $item);

        abort_unless($document->follow_up_item_id === $item->id, 404);

        $item->documents()->update(['is_primary' => false]);
        $document->update(['is_primary' => true]);

        ItemHistory::create([
            'follow_up_item_id' => $item->id,
            'user_id' => Auth::id(),
            'event_type' => 'document_marked_primary',
            'old_status' => $item->status,
            'new_status' => $item->status,
            'action_summary' => "Main document set: {$document->document_label} v{$document->version_number}",
            'notes' => $document->original_name,
        ]);

        return back()->with('status', 'Main document updated successfully.');
    }

    public function download(FollowUpItem $item, ItemDocument $document)
    {
        $this->authorize('view', $item);

        abort_unless($document->follow_up_item_id === $item->id, 404);

        return Storage::disk('local')->download($document->storage_path, $document->original_name);
    }

    public function preview(FollowUpItem $item, ItemDocument $document)
    {
        $this->authorize('view', $item);

        abort_unless($document->follow_up_item_id === $item->id, 404);

        $mimeType = $document->mime_type ?: Storage::disk('local')->mimeType($document->storage_path);

        abort_unless(
            str_contains((string) $mimeType, 'pdf') || str_starts_with((string) $mimeType, 'image/'),
            415
        );

        return response(Storage::disk('local')->get($document->storage_path), 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$document->original_name.'"',
        ]);
    }
}
