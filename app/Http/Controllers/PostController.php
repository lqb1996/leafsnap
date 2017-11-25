<?php

namespace App\Http\Controllers;

use App\Comment;
use App\Relationship;
use Illuminate\Http\Request;
use App\Post;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    /*
     * 文章列表
     */
    public function index(Request $request)
    {
        $user = \Auth::user();
        $posts = Post::aviable()->orderBy('created_at', 'desc')->withCount(["zans", "comments"])->with(['user'])->paginate(6);
        if($request['type'] == 'ajax'){
            return compact('posts');
        }
        return view('post/index', compact('posts'));
//        return $posts;
    }

    public function imageUpload(Request $request)
    {
        $path = $request->file('wangEditorH5File')->storePublicly(md5(\Auth::id() . time()));
        return asset('storage/'. $path);
    }

    public function create()
    {
        return view('post/create');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'title' => 'required|max:255|min:4',
            'content' => 'required|min:100',
        ]);
        $params = array_merge(request(['title', 'content']), ['user_id' => \Auth::id()]);
        Post::create($params);
        return redirect('/posts');
    }

    public function edit(Post $post)
    {
        return view('post/edit', compact('post'));
    }

    public function show(Request $request, \App\Post $post)
    {
        if($request['type'] == 'ajax'){
            return compact('post');
        }
        return view('post/show', compact('post'));
    }

    public function update(Request $request, Post $post)
    {
        $this->validate($request, [
            'title' => 'required|max:255|min:4',
            'content' => 'required|min:100',
        ]);

        $this->authorize('update', $post);

        $post->update(request(['title', 'content']));
        return redirect("/posts/{$post->id}");
    }

    /*
     * 文章评论保存
     */
    public function comment(Post $post)
    {
        $this->validate(request(),[
            'post_id' => 'required|exists:posts,id',
            'content' => 'required|min:10',
        ]);

        $user_id = \Auth::id();
        $commentable_id = request('post_id');
        $parent_id = 0;
        $comment = new Comment();
        $comment->user_id = $user_id;
        if(request('parent_id')){
            $comment->parent_id = request('parent_id');
        }
        else {
            $comment->parent_id = 0;
        }
        $comment->content = request('content');
        $post->id = request('post_id');

//        $params = array_merge(
//            compact('commentable_id'),
//            request(['content']),
//            compact('user_id'),
//            compact('parent_id')
//        );
//        \App\Comment::create($params);
        $post->commentable()->save($comment);
        return back();
    }

    /*
     * 点赞
     */
    public function zan(Post $post)
    {
        $zan = new \App\Zan;
        $zan->user_id = \Auth::id();
        $post->zans()->save($zan);
//        $post->target()->save($zan);


        $relationship = new Relationship();
        //commentable_type取值例如：App\Post，App\Page等等
//        $target = app('App\Post')->where('id', $post->id)->firstOrFail();
        $relationship->user_id = \Auth::id();

        $post->targets()->save($relationship);
        return back();
    }

    /*
     * 取消点赞
     */
    public function unzan(Post $post)
    {
        $post->zan(\Auth::id())->delete();
        $post->target(\Auth::id())->delete();
        return back();
    }

    /*
     * 搜索页面
     */
    public function search()
    {
        $this->validate(request(),[
            'query' => 'required'
        ]);

        $query = request('query');
        $posts = Post::search(request('query'))->paginate(10);
        return view('post/search', compact('posts', 'query'));
    }
}
