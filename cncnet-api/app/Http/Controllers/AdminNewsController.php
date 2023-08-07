<?php

namespace App\Http\Controllers;

use App\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class AdminNewsController extends Controller
{
    public function __construct()
    {
    }

    public function getIndex(Request $request)
    {
        $news = News::orderBy("created_at", "DESC")->paginate(20);

        return view("admin.news.index", [
            "news" => $news
        ]);
    }

    public function getEdit(Request $request, $id)
    {
        $news = News::find($id);
        if ($news == null)
        {
            abort(404);
        }

        return view("admin.news.create", [
            "news" => $news,
        ]);
    }

    public function getCreate(Request $request)
    {
        return view("admin.news.create", ["news" => null]);
    }

    public function save(Request $request)
    {
        $this->validate($request, [
            "featured_image" => "image|mimes:jpg,jpeg,png,gif|max:3000",
        ]);

        if ($request->id)
        {
            $news = News::find($request->id);

            if ($news == null)
            {
                abort(404);
            }
        }
        else
        {
            $news = new News();
        }

        $news->slug = Str::slug($request->title);
        $news->title = $request->title;
        $news->description = $request->description;
        $news->body = $request->body;
        $news->author_id = Auth::user()->id;
        $news->save();

        if ($request->hasFile("featured_image"))
        {
            $image = Image::make($request->file('featured_image')->getRealPath());

            $image->resize(720, null);

            // Save the image as WebP format
            $webpHash = md5(time() . $request->file('featured_image')->getClientOriginalName());
            $outputPath = "news/{$webpHash}.webp";
            $image->encode('webp', 80)->save(public_path($outputPath));

            $news->featured_image = $outputPath;
            $news->save();
        }

        if ($request->id)
        {
            return redirect('/admin/news/edit/' . $request->id)->with('success', 'News updated!');
        }
        return redirect('/admin/news')->with('success', 'News updated!');
    }
}
