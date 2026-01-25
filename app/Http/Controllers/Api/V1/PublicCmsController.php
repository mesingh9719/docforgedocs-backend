<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\ContactInquiry;
use App\Models\Setting;
use Illuminate\Http\Request;

class PublicCmsController extends Controller
{
    public function getCategories()
    {
        return BlogCategory::where('is_active', true)->get();
    }

    public function getPosts(Request $request)
    {
        $query = BlogPost::with('category', 'author')
            ->where('status', 'published')
            ->where('published_at', '<=', now());

        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        return $query->latest('published_at')->paginate(10);
    }

    public function getPostBySlug($slug)
    {
        return BlogPost::with('category', 'author')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();
    }

    public function getContactInfo()
    {
        return Setting::where('type', 'contact')->pluck('value', 'key');
    }

    public function submitContactForm(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string',
        ]);

        ContactInquiry::create([
            'name' => $request->name,
            'email' => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
        ]);

        return response()->json(['message' => 'Inquiry submitted successfully']);
    }
}
