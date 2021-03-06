<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Post;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Type;
use App\Models\Favorite;
use Inertia\Inertia;
use App\Services\ImageService;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{

    public function mypost()
    {
        $userId = Auth::id();
        $user = USer::findOrFail(Auth::id());
        $posts = Post::where('user_id', $userId)->paginate(12);
        return Inertia::render(
            'Post/MyPost',
            [
                'user' => $user,
                'posts' => $posts,
            ]
        );
    }

    public function index()
    {
        $posts = DB::table('users')
            ->join('posts', 'users.id', '=', 'posts.user_id')
            ->paginate(12);
             
        return Inertia::render(
            'Post/Index',
            [
                'posts' => $posts,
            ]
        );
    }

    public function search(Request $request)
    {
        $keyword = $request->keyword;
        $posts = Post::where('foodname', 'like', '%' . $keyword . '%')->paginate(50);
        return Inertia::render(
            'Post/Search',
            [
                'posts' => $posts,
            ]
        );
    }

    public function create()
    {
        return Inertia::render(
            'Post/Create',
            ['types' => Type::all()]
        );
    }

    public function store(StorePostRequest $request)
    {
        //投稿した際にまとめてnutrition::tableにも保存したい
        $user = Auth::id();
        $image = $request->file;

        if (!is_null($image) && $image->isValid()) {
            $fileNameToStore = ImageService::upload($image);
        }
        Post::create([
            'user_id' => $user,
            'type_id' => $request->type,
            'foodname' => $request->foodname,
            'content' => $request->postText,
            'calorie' => $request->calorie,
            'carbon' => $request->carbon,
            'protein' => $request->protein,
            'fat' => $request->fat,
            'filename' => $fileNameToStore,
        ]);
        return redirect()->route('posts.index');
    }


    public function show(Post $post)
    {
        $userId = Auth::id();
        $user = User::findOrfail(Auth::id());
        $postUser = $post->user;
        $typeId = $post->type_id;
        $type = Type::findOrFail($typeId);
        $favorite = Favorite::where('post_id', $post->id)->where('user_id', $userId)->first();
        $favoriteCount = Favorite::where('post_id', $post->id)->get();
        return Inertia::render('Post/Show', ['user'=> $user,'post' => $post, 'type' => $type, 'postUser' => $postUser, 'favorite' => $favorite, 'favoriteCount' => $favoriteCount]);
    }


    public function edit(Post $post)
    {
        //
    }


    public function update(UpdatePostRequest $request, Post $post)
    {
        //
    }


    public function destroy(Post $post)
    {
        $file = $post->filename;
        $file = str_replace('/storage/', '', $file);
        $filePath = 'public/' . $file;
        Storage::delete($filePath);
        $post->delete();
        return redirect()->route('posts.index');
    }
}
