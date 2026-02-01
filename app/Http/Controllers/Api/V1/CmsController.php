<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\ContactInquiry;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CmsController extends Controller
{
    // --- Blog Categories ---
    public function indexCategories()
    {
        return BlogCategory::latest()->get();
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = BlogCategory::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json($category, 201);
    }

    public function updateCategory(Request $request, BlogCategory $category)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json($category);
    }

    public function destroyCategory(BlogCategory $category)
    {
        $category->delete();
        return response()->noContent();
    }

    // --- Blog Posts ---
    public function indexPosts()
    {
        return BlogPost::with('category', 'author')->latest()->get();
    }

    public function storePost(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:blog_categories,id',
            'content' => 'required|string',
            'status' => 'required|in:draft,published',
        ]);

        $slug = Str::slug($request->title);
        // Ensure unique slug
        if (BlogPost::where('slug', $slug)->exists()) {
            $slug = $slug . '-' . time();
        }

        $post = BlogPost::create([
            'title' => $request->title,
            'slug' => $slug,
            'category_id' => $request->category_id,
            'summary' => $request->summary,
            'content' => $request->input('content'),
            'featured_image' => $request->featured_image,
            'status' => $request->status,
            'published_at' => $request->status === 'published' ? now() : null,
            'author_id' => auth()->id(),
            'seo_title' => $request->seo_title,
            'seo_description' => $request->seo_description,
            'meta_keywords' => $request->meta_keywords,
            'canonical_url' => $request->canonical_url,
        ]);

        return response()->json($post, 201);
    }

    public function showPost(BlogPost $post)
    {
        return $post->load('category', 'author');
    }

    public function updatePost(Request $request, BlogPost $post)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:blog_categories,id',
            'content' => 'required|string',
            'status' => 'required|in:draft,published',
        ]);

        $data = $request->only([
            'title',
            'category_id',
            'summary',
            'content',
            'featured_image',
            'status',
            'seo_title',
            'seo_description',
            'meta_keywords',
            'canonical_url'
        ]);

        if ($request->title !== $post->title) {
            $slug = Str::slug($request->title);
            if (BlogPost::where('slug', $slug)->where('id', '!=', $post->id)->exists()) {
                $slug = $slug . '-' . time();
            }
            $data['slug'] = $slug;
        }

        if ($request->status === 'published' && $post->status !== 'published') {
            $data['published_at'] = now();
        }

        $post->update($data);

        return response()->json($post);
    }

    public function destroyPost(BlogPost $post)
    {
        $post->delete();
        return response()->noContent();
    }

    // --- Contact Inquiries ---
    public function indexInquiries()
    {
        return ContactInquiry::latest()->get();
    }

    public function showInquiry(ContactInquiry $inquiry)
    {
        if ($inquiry->status === 'new') {
            $inquiry->update(['status' => 'read']);
        }
        return $inquiry;
    }

    public function updateInquiryStatus(Request $request, ContactInquiry $inquiry)
    {
        $request->validate(['status' => 'required|in:new,read,replied']);
        $inquiry->update(['status' => $request->status]);
        return $inquiry;
    }

    // --- Site Settings (Contact Info) ---
    public function getSettings()
    {
        return Setting::where('type', 'contact')->pluck('value', 'key');
    }

    public function updateSettings(Request $request)
    {
        $data = $request->all(); // Expect key-value pairs

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'type' => 'contact', 'group' => 'general']
            );
        }

        return response()->json(['message' => 'Settings updated']);
    }
}
