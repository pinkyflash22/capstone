<?php
// app/Http/Controllers/Admin/PostController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Comment;
use App\Models\PostLike;
use App\Models\StationOfficer;
use App\Support\MediaPath;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Support\UserActivityLogger;
class PostController extends Controller
{

public function index()
    {
        return view('admin.announcements');
    }
public function findex()
    {
        return view('firefighters.announcements');
    }
public function getPosts(Request $request)
    {
        $currentUserId = (int) auth()->id();
        $posts = Post::with([
                'user:user_id,role_id,first_name,middle_name,last_name,image,profile_thumbnail',
                'user.role:role_id,role',
                'comments:comment_id,post_id,user_id,content,created_at,updated_at',
                'comments.user:user_id,role_id,first_name,middle_name,last_name,image,profile_thumbnail',
                'comments.user.role:role_id,role',
            ])
            ->recent()
            ->simplePaginate(10);

        if ($currentUserId > 0) {
            $postUserIds = $posts->getCollection()->pluck('user_id')
                ->push($currentUserId)
                ->filter()
                ->unique()
                ->values();
            $stationIdsByUser = StationOfficer::query()
                ->whereIn('user_id', $postUserIds)
                ->pluck('station_id', 'user_id');
            $currentStationId = $stationIdsByUser->get($currentUserId);
            $likedPostIds = PostLike::query()
                ->where('user_id', $currentUserId)
                ->whereIn('post_id', $posts->getCollection()->pluck('id'))
                ->pluck('post_id')
                ->map(fn ($id) => (int) $id)
                ->flip();

            foreach ($posts as $post) {
                $posterStationId = $stationIdsByUser->get($post->user_id);
                $post->liked_by_user = $likedPostIds->has((int) $post->id);
                $post->can_manage = (int) $post->user_id === $currentUserId
                    || ($currentStationId && (int) $posterStationId === (int) $currentStationId);

                foreach ($post->comments as $comment) {
                    $comment->can_manage = (int) $comment->user_id === $currentUserId;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $posts,
            'has_more' => $posts->hasMorePages()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required_without_all:images,video,poll_options|string|max:5000',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'video' => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/webm|max:51200',
            'poll_question' => 'nullable|string|max:255',
            'poll_options' => 'nullable|array|min:2|max:6',
            'poll_options.*' => 'nullable|string|max:120',
            'post_type' => 'nullable|in:announcement,update,alert,poll',
            'is_important' => 'nullable|boolean'
        ]);

        DB::beginTransaction();

        try {
            $imagePaths = [];
            $videoPath = null;

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $randomString = Str::random(30);
                    $extension = $image->getClientOriginalExtension();
                    $filename = $randomString . '.' . $extension;
                    $path = $image->storeAs('posts', $filename, 'supabase');
                    $imagePaths[] = MediaPath::storeForDisk($path, 'supabase');
                }
            }

            if ($request->hasFile('video')) {
                $videoFile = $request->file('video');
                $videoFilename = Str::random(30) . '.' . $videoFile->getClientOriginalExtension();
                $videoPath = MediaPath::storeForDisk(
                    $videoFile->storeAs('posts', $videoFilename, 'supabase'),
                    'supabase'
                );
            }

            $pollOptions = collect($request->input('poll_options', []))
                ->filter(fn ($option) => filled($option))
                ->map(fn ($option) => ['text' => $option, 'votes' => 0])
                ->values()
                ->all();

            $pollData = null;
            if (filled($request->poll_question) && count($pollOptions) >= 2) {
                $pollData = [
                    'question' => $request->poll_question,
                    'options' => $pollOptions,
                ];
            }

            $post = Post::create([
                'user_id' => auth()->user()->user_id,
                'content' => $request->content ?? $request->poll_question ?? '',
                'images' => !empty($imagePaths) ? $imagePaths : null,
                'video' => $videoPath,
                'poll_data' => $pollData,
                'post_type' => $pollData ? 'poll' : ($request->post_type ?? 'announcement'),
                'is_important' => $request->is_important ?? false,
                'likes_count' => 0,
                'comments_count' => 0,
                'shares_count' => 0
            ]);

            DB::commit();
            UserActivityLogger::log('created post', auth()->user()?->user_id, $request);

            $post->load(['user.role', 'comments.user.role', 'likes']);
            $post->liked_by_user = false;
            $post->can_manage = true;

            return response()->json([
                'success' => true,
                'message' => 'Post created successfully',
                'data' => $post
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            if (!empty($imagePaths)) {
                foreach ($imagePaths as $path) {
                    MediaPath::delete($path);
                }
            }
            if ($videoPath) {
                MediaPath::delete($videoPath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to create post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $post = Post::findOrFail($id);
        $currentStationId = $this->getCurrentUserStationId();

        if (!$this->canManagePost($post, $currentStationId)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to edit this post.',
            ], 403);
        }

        $request->validate([
            'content' => 'required_without_all:images,video,poll_options|string|max:5000',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'video' => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/webm|max:51200',
            'poll_question' => 'nullable|string|max:255',
            'poll_options' => 'nullable|array|min:2|max:6',
            'poll_options.*' => 'nullable|string|max:120',
            'post_type' => 'nullable|in:announcement,update,alert,poll',
            'is_important' => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        try {
            $imagePaths = $post->images ?? [];
            $videoPath = $post->video;

            if ($request->boolean('remove_images')) {
                foreach ($imagePaths as $path) {
                    MediaPath::delete($path);
                }
                $imagePaths = [];
            }

            if ($request->hasFile('images')) {
                foreach ($imagePaths as $path) {
                    MediaPath::delete($path);
                }
                $imagePaths = [];

                foreach ($request->file('images') as $image) {
                    $filename = Str::random(30) . '.' . $image->getClientOriginalExtension();
                    $imagePaths[] = MediaPath::storeForDisk(
                        $image->storeAs('posts', $filename, 'supabase'),
                        'supabase'
                    );
                }
            }

            if ($request->boolean('remove_video') && $videoPath) {
                MediaPath::delete($videoPath);
                $videoPath = null;
            }

            if ($request->hasFile('video')) {
                if ($videoPath) {
                    MediaPath::delete($videoPath);
                }
                $videoFile = $request->file('video');
                $videoFilename = Str::random(30) . '.' . $videoFile->getClientOriginalExtension();
                $videoPath = MediaPath::storeForDisk(
                    $videoFile->storeAs('posts', $videoFilename, 'supabase'),
                    'supabase'
                );
            }

            $pollOptions = collect($request->input('poll_options', []))
                ->filter(fn ($option) => filled($option))
                ->map(fn ($option) => ['text' => $option, 'votes' => 0])
                ->values()
                ->all();

            $pollData = null;
            if (filled($request->poll_question) && count($pollOptions) >= 2) {
                $pollData = [
                    'question' => $request->poll_question,
                    'options' => $pollOptions,
                ];
            }

            $post->update([
                'content' => $request->content ?? $request->poll_question ?? '',
                'images' => !empty($imagePaths) ? $imagePaths : null,
                'video' => $videoPath,
                'poll_data' => $pollData,
                'post_type' => $pollData ? 'poll' : ($request->post_type ?? 'announcement'),
                'is_important' => $request->is_important ?? false,
            ]);

            DB::commit();
            UserActivityLogger::log('updated post', auth()->user()?->user_id, $request);

            $post->refresh()->load(['user.role', 'comments.user.role', 'likes']);
            $post->liked_by_user = $post->isLikedBy(auth()->user());
            $post->can_manage = $this->canManagePost($post, $currentStationId);

            return response()->json([
                'success' => true,
                'message' => 'Post updated successfully',
                'data' => $post,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update post',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // public function index()
    // {
    //     return view('admin.announcements');
    // }

    // public function getPosts(Request $request)
    // {
    //     $posts = Post::with(['user', 'comments.user', 'likes'])
    //         ->recent()
    //         ->paginate(10);

    //     if (auth()->check()) {
    //         foreach ($posts as $post) {
    //             $post->liked_by_user = $post->isLikedBy(auth()->user());
    //         }
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'data' => $posts,
    //         'has_more' => $posts->hasMorePages()
    //     ]);
    // }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'content' => 'required_without:images|string|max:5000',
    //         'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
    //         'post_type' => 'nullable|in:announcement,update,alert,poll',
    //         'is_important' => 'nullable|boolean'
    //     ]);

    //     DB::beginTransaction();

    //     try {
    //         $imagePaths = [];

    //         if ($request->hasFile('images')) {
    //             foreach ($request->file('images') as $image) {
    //                 $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
    //                 $path = $image->storeAs('posts', $filename, 'public');
    //                 $imagePaths[] = $path;
    //             }
    //         }

    //         $post = Post::create([
    //             'user_id' => auth()->id(),
    //             'content' => $request->content,
    //             'images' => !empty($imagePaths) ? $imagePaths : null,
    //             'post_type' => $request->post_type ?? 'announcement',
    //             'is_important' => $request->is_important ?? false,
    //             'likes_count' => 0,
    //             'comments_count' => 0,
    //             'shares_count' => 0
    //         ]);

    //         DB::commit();

    //         $post->load(['user', 'comments.user', 'likes']);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Post created successfully',
    //             'data' => $post
    //         ], 201);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
            
    //         if (!empty($imagePaths)) {
    //             foreach ($imagePaths as $path) {
    //                 Storage::disk('public')->delete($path);
    //             }
    //         }

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to create post',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function like($postId)
    {
        $post = Post::findOrFail($postId);

        DB::beginTransaction();

        try {
            $existingLike = PostLike::where('post_id', $postId)
                ->where('user_id', auth()->id())
                ->first();

            if ($existingLike) {
                $existingLike->delete();
                $post->decrement('likes_count');
                $liked = false;
            } else {
                PostLike::create([
                    'post_id' => $postId,
                    'user_id' => auth()->id()
                ]);
                $post->increment('likes_count');
                $liked = true;
            }

            DB::commit();
            UserActivityLogger::log($liked ? 'liked post' : 'unliked post', auth()->user()?->user_id, request());

            return response()->json([
                'success' => true,
                'liked' => $liked,
                'likes_count' => $post->fresh()->likes_count
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to process like',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function comment(Request $request, $postId)
    {
        $request->validate([
            'content' => 'required|string|max:1000'
        ]);

        DB::beginTransaction();

        try {
            $comment = Comment::create([
                'post_id' => $postId,
                'user_id' => auth()->user()->user_id,
                'content' => $request->content
            ]);

            Post::where('id', $postId)->increment('comments_count');

            DB::commit();
            UserActivityLogger::log('commented post', auth()->user()?->user_id, $request);

            $comment->load('user.role');

            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully',
                'data' => $comment
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $post = Post::findOrFail($id);
        $currentStationId = $this->getCurrentUserStationId();

        if (!$this->canManagePost($post, $currentStationId)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to delete this post.',
            ], 403);
        }

        DB::beginTransaction();

        try {
            if ($post->images) {
                foreach ($post->images as $image) {
                    MediaPath::delete($image);
                }
            }

            if ($post->video) {
                MediaPath::delete($post->video);
            }

            $post->delete();

            DB::commit();
            UserActivityLogger::log('deleted post', auth()->user()?->user_id, request());

            return response()->json([
                'success' => true,
                'message' => 'Post deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function getCurrentUserStationId(): ?int
    {
        return StationOfficer::where('user_id', auth()->user()->user_id)->value('station_id');
    }

    protected function canManagePost(Post $post, ?int $currentStationId): bool
    {
        if (!auth()->check()) {
            return false;
        }

        if ((int) $post->user_id === (int) auth()->user()->user_id) {
            return true;
        }

        if (!$currentStationId) {
            return false;
        }

        $posterStationId = StationOfficer::where('user_id', $post->user_id)->value('station_id');

        return $posterStationId && (int) $posterStationId === (int) $currentStationId;
    }
}
