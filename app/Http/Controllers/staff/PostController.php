<?php
// app/Http/Controllers/Admin/PostController.php

namespace App\Http\Controllers\staff;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Comment;
use App\Models\PostLike;
use App\Support\MediaPath;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Support\UserActivityLogger;
class PostController extends Controller
{

public function index()
    {
        return view('firefighters.announcements');
    }

    public function getPosts(Request $request)
    {
        $posts = Post::with(['user.role', 'comments.user', 'likes'])
            ->recent()
            ->paginate(10);

        if (auth()->check()) {
            foreach ($posts as $post) {
                $post->liked_by_user = $post->isLikedBy(auth()->user());
                $post->can_manage = $post->user_id === auth()->user()->user_id;

                foreach ($post->comments as $comment) {
                    $comment->can_manage = (int) $comment->user_id === (int) auth()->user()->user_id;
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
            'is_important' => 'nullable|boolean',
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
            Post::flushDashboardRecentPostsCache();
            UserActivityLogger::log('created post', auth()->user()?->user_id, $request);

            $post->load(['user.role', 'comments.user', 'likes']);
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
        $post = Post::where('user_id', auth()->user()->user_id)->findOrFail($id);

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
            Post::flushDashboardRecentPostsCache();
            UserActivityLogger::log('updated post', auth()->user()?->user_id, $request);

            $post->refresh()->load(['user.role', 'comments.user', 'likes']);
            $post->liked_by_user = $post->isLikedBy(auth()->user());
            $post->can_manage = true;

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
                ->where('user_id', auth()->user()->user_id)
                ->first();

            if ($existingLike) {
                $existingLike->delete();
                $post->decrement('likes_count');
                $liked = false;
            } else {
                PostLike::create([
                    'post_id' => $postId,
                    'user_id' => auth()->user()->user_id
                ]);
                $post->increment('likes_count');
                $liked = true;
            }

            DB::commit();
            Post::flushDashboardRecentPostsCache();
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
        $validated = $request->validate([
            'content' => 'required|string|max:1000'
        ]);

        try {
            $comment = DB::transaction(function () use ($postId, $validated) {
                $post = Post::query()->lockForUpdate()->findOrFail($postId);
                $comment = $post->comments()->create([
                    'user_id' => auth()->user()->user_id,
                    'content' => trim($validated['content']),
                ]);
                $post->update(['comments_count' => $post->comments()->count()]);

                return $comment;
            });

            Post::flushDashboardRecentPostsCache();
            $this->logCommentActivity('commented post', $request);

            $comment->load('user');

            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully',
                'data' => $this->commentPayload($comment, true),
            ], 201);

        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment. Please try again.',
            ], 500);
        }
    }

    public function updateComment(Request $request, $postId, $commentId)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment = $this->ownedComment($postId, $commentId);
        $comment->update(['content' => trim($validated['content'])]);
        $comment->load('user');

        Post::flushDashboardRecentPostsCache();
        $this->logCommentActivity('edited comment', $request);

        return response()->json([
            'success' => true,
            'message' => 'Comment updated successfully',
            'data' => $this->commentPayload($comment, true),
        ]);
    }

    public function destroyComment(Request $request, $postId, $commentId)
    {
        $commentsCount = DB::transaction(function () use ($postId, $commentId) {
            $post = Post::query()->lockForUpdate()->findOrFail($postId);
            $comment = $this->ownedComment($post->id, $commentId);
            $comment->delete();
            $commentsCount = $post->comments()->count();
            $post->update(['comments_count' => $commentsCount]);

            return $commentsCount;
        });

        Post::flushDashboardRecentPostsCache();
        $this->logCommentActivity('deleted comment', $request);

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully',
            'comments_count' => $commentsCount,
        ]);
    }

    private function ownedComment($postId, $commentId): Comment
    {
        $comment = Comment::query()
            ->where('post_id', $postId)
            ->whereKey($commentId)
            ->firstOrFail();

        abort_unless((int) $comment->user_id === (int) auth()->user()->user_id, 403, 'You can only manage your own comments.');

        return $comment;
    }

    private function commentPayload(Comment $comment, bool $canManage): array
    {
        return [
            'id' => $comment->getKey(),
            'comment_id' => $comment->getKey(),
            'content' => $comment->content,
            'user_id' => $comment->user_id,
            'user' => [
                'first_name' => $comment->user?->first_name,
                'last_name' => $comment->user?->last_name,
                'avatar' => strtoupper(substr($comment->user?->first_name ?? 'U', 0, 1) . substr($comment->user?->last_name ?? 'S', 0, 1)),
                'profile_thumbnail_url' => $comment->user?->profile_thumbnail_url,
                'profile_image_url' => $comment->user?->profile_image_url,
            ],
            'time_ago' => $comment->time_ago,
            'can_manage' => $canManage,
        ];
    }

    private function logCommentActivity(string $action, Request $request): void
    {
        try {
            UserActivityLogger::log($action, auth()->user()?->user_id, $request);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function destroy($id)
    {
        $post = Post::where('user_id', auth()->user()->user_id)->findOrFail($id);

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
            Post::flushDashboardRecentPostsCache();
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
}
