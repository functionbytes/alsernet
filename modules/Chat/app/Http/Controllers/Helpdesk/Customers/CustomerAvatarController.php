<?php

namespace Modules\Chat\Http\Controllers\Helpdesk\Customers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Customers\Customer;

class CustomerAvatarController extends Controller
{
    /**
     * Upload avatar for a contact.
     */
    public function upload(Request $request, Customer $customer)
    {
        $this->authorize('update', $customer);

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);

        // Delete existing avatar if any
        if ($customer->avatar) {
            $customer->avatar->delete();
        }

        $file = $request->file('avatar');
        $fileName = uniqid('avatar_').'Settings'.$file->getClientOriginalExtension();
        $thumbFileName = uniqid('thumb_').'Settings'.$file->getClientOriginalExtension();

        // Create full size (200x200)
        $image = Image::read($file);
        $image->cover(200, 200);

        // Save full size
        $fullPath = 'avatars/'.$fileName;
        Storage::put($fullPath, (string) $image->encode());

        // Create thumbnail (50x50)
        $thumbnail = Image::read($file);
        $thumbnail->cover(50, 50);

        // Save thumbnail
        $thumbPath = 'avatars/thumbnails/'.$thumbFileName;
        Storage::put($thumbPath, (string) $thumbnail->encode());

        // Create avatar record
        $customer->avatar()->create([
            'file_path' => $fullPath,
            'thumbnail_path' => $thumbPath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        return redirect()->back()->with('success', 'Avatar uploaded successfully.');
    }

    /**
     * Delete avatar for a contact.
     */
    public function destroy(Customer $customer)
    {
        $this->authorize('update', $customer);

        if ($customer->avatar) {
            $customer->avatar->delete();

            return redirect()->back()
                ->with('success', 'Avatar deleted successfully.');
        }

        return redirect()->back()->with('error', 'No avatar to delete.');
    }
}
