// resources/js/admin/announcements.js

class AnnouncementSystem {
    constructor() {
        this.posts = [];
        this.currentPage = 1;
        this.loading = false;
        this.hasMore = true;
        this.selectedImages = [];
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
        this.init();
    }

    init() {
        this.loadPosts();
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        window.addEventListener('scroll', () => this.handleInfiniteScroll());
        
        const postInput = document.getElementById('postContent');
        if (postInput) {
            postInput.addEventListener('input', this.autoResizeTextarea);
        }

        const imageUpload = document.getElementById('imageUpload');
        if (imageUpload) {
            imageUpload.addEventListener('change', (e) => this.handleImageUpload(e));
        }
    }

    autoResizeTextarea() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    }

    async loadPosts(reset = false) {
    if (this.loading || (!this.hasMore && !reset)) return;

    this.loading = true;
    this.showLoading(true);

    try {
        const response = await fetch(`/admin/posts/get?page=${this.currentPage}`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            }
        });

        if (!response.ok) throw new Error('Failed to load posts');

        const result = await response.json();
        
        // DEBUG: Log the first post to see its structure
        if (result.data && result.data.data && result.data.data.length > 0) {
            console.log('First post:', result.data.data[0]);
            console.log('Images array:', result.data.data[0].images);
            console.log('Image URLs:', result.data.data[0].image_urls);
        }

        if (reset) {
            this.posts = result.data.data;
            this.currentPage = 1;
        } else {
            this.posts = [...this.posts, ...result.data.data];
        }

        this.hasMore = result.has_more;
        this.currentPage++;

        this.renderPosts();

    } catch (error) {
        console.error('Error loading posts:', error);
        this.showNotification('Failed to load posts', 'error');
    } finally {
        this.loading = false;
        this.showLoading(false);
    }
}
    async loadPosts(reset = false) {
        if (this.loading || (!this.hasMore && !reset)) return;

        this.loading = true;
        this.showLoading(true);

        try {
            const response = await fetch(`/admin/posts/get?page=${this.currentPage}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                }
            });

            if (!response.ok) throw new Error('Failed to load posts');

            const result = await response.json();

            if (reset) {
                this.posts = result.data.data;
                this.currentPage = 1;
            } else {
                this.posts = [...this.posts, ...result.data.data];
            }

            this.hasMore = result.has_more;
            this.currentPage++;

            this.renderPosts();

        } catch (error) {
            console.error('Error loading posts:', error);
            this.showNotification('Failed to load posts', 'error');
        } finally {
            this.loading = false;
            this.showLoading(false);
        }
    }

    async createPost() {
        const content = document.getElementById('postContent')?.value.trim();
        
        if (!content && this.selectedImages.length === 0) {
            this.showNotification('Please write something or add an image', 'warning');
            return;
        }

        const formData = new FormData();
        formData.append('content', content);
        
        if (this.selectedImages.length > 0) {
            for (let i = 0; i < this.selectedImages.length; i++) {
                formData.append('images[]', this.selectedImages[i]);
            }
        }

        this.showLoading(true);

        try {
            const response = await fetch('/admin/posts/store', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.posts.unshift(result.data);
                this.renderPosts();
                this.clearPost();
                this.showNotification('Announcement posted successfully!', 'success');
            } else {
                throw new Error(result.message);
            }

        } catch (error) {
            console.error('Error creating post:', error);
            this.showNotification('Failed to create post', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async likePost(postId) {
        try {
            const response = await fetch(`/admin/posts/${postId}/like`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (result.success) {
                const post = this.posts.find(p => p.id === postId);
                if (post) {
                    post.liked_by_user = result.liked;
                    post.likes_count = result.likes_count;
                    this.renderPosts();
                }
            }

        } catch (error) {
            console.error('Error liking post:', error);
            this.showNotification('Failed to process like', 'error');
        }
    }

    async addComment(postId) {
        const input = document.getElementById(`comment-input-${postId}`);
        if (!input) return;

        const commentText = input.value.trim();
        if (!commentText) return;

        this.showLoading(true);

        try {
            const response = await fetch(`/admin/posts/${postId}/comment`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ content: commentText })
            });

            const result = await response.json();

            if (result.success) {
                const post = this.posts.find(p => p.id === postId);
                if (post) {
                    if (!post.comments) post.comments = [];
                    post.comments.push(result.data);
                    post.comments_count = (post.comments_count || 0) + 1;
                }
                
                input.value = '';
                this.renderPosts();
                this.showNotification('Comment added', 'success');
            }

        } catch (error) {
            console.error('Error adding comment:', error);
            this.showNotification('Failed to add comment', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    handleImageUpload(event) {
        const files = Array.from(event.target.files);
        
        if (files.length > 0) {
            const validFiles = files.filter(file => {
                if (file.size > 5 * 1024 * 1024) {
                    this.showNotification(`${file.name} is too large (max 5MB)`, 'warning');
                    return false;
                }
                return true;
            });

            this.selectedImages = [...this.selectedImages, ...validFiles];
            this.displayImagePreviews();
        }
    }

    displayImagePreviews() {
        const previewArea = document.getElementById('imagePreview');
        if (!previewArea) return;

        previewArea.innerHTML = '';

        this.selectedImages.forEach((file, index) => {
            const reader = new FileReader();
            
            reader.onload = (e) => {
                const previewItem = document.createElement('div');
                previewItem.className = 'preview-item';
                previewItem.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    <button class="remove-preview" onclick="window.announcementSystem.removeImage(${index})">
                        <i class="fa-solid fa-times"></i>
                    </button>
                `;
                previewArea.appendChild(previewItem);
            };

            reader.readAsDataURL(file);
        });
    }

    removeImage(index) {
        this.selectedImages.splice(index, 1);
        this.displayImagePreviews();
        
        const imageUpload = document.getElementById('imageUpload');
        if (imageUpload) imageUpload.value = '';
    }

    clearPost() {
        const postInput = document.getElementById('postContent');
        if (postInput) postInput.value = '';
        
        this.selectedImages = [];
        this.displayImagePreviews();
        
        const imageUpload = document.getElementById('imageUpload');
        if (imageUpload) imageUpload.value = '';
    }

    renderPosts() {
        const feed = document.getElementById('feed');
        if (!feed) return;

        if (this.posts.length === 0) {
            feed.innerHTML = this.getEmptyStateHtml();
            return;
        }

        feed.innerHTML = this.posts.map(post => this.createPostElement(post)).join('');
    }

//     createPostElement(post) {
//         const timeAgo = post.time_ago || this.getTimeAgo(new Date(post.created_at));
//         const userAvatar = post.user?.name ? post.user.name.substring(0, 2).toUpperCase() : 'JD';
//         const userName = post.user?.name || 'Unknown User';
//         const userRole = post.user?.role || 'Personnel';
        
//         // let imagesHtml = '';
//         // if (post.image_urls && post.image_urls.length > 0) {
//         //     if (post.image_urls.length === 1) {
//         //         imagesHtml = `
//         //             <div class="post-media">
//         //                 <img src="${post.image_urls[0]}" alt="Post image" loading="lazy">
//         //             </div>
//         //         `;
//         //     } else {
//         //         imagesHtml = `
//         //             <div style="display: grid; grid-template-columns: repeat(${Math.min(post.image_urls.length, 2)}, 1fr); gap: 10px;">
//         //                 ${post.image_urls.map(url => `
//         //                     <img src="${url}" alt="Post image" loading="lazy" 
//         //                          style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px;">
//         //                 `).join('')}
//         //             </div>
//         //         `;
//         //     }
//         // }
//       let imagesHtml = '';
    
//     // Use image_urls if available (recommended)
//     if (post.image_urls && post.image_urls.length > 0) {
//         const imageUrl = post.image_urls[0];
        
//         imagesHtml = `
//             <div class="post-media">
//                 <img src="${imageUrl}" alt="Post image" loading="lazy"
//                      onerror="this.onerror=null; this.src='/storage/defaults/placeholder.jpg'">
//             </div>
//         `;
        
//         // Handle multiple images
//         if (post.image_urls.length > 1) {
//             const imageGrid = post.image_urls.map(url => 
//                 `<img src="${url}" alt="Post image" loading="lazy" style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px;">`
//             ).join('');
            
//           imagesHtml = `
//     <div class="post-media">
//         <img src="${imageUrl}" alt="Post image" loading="lazy"
//              onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100%25\' height=\'100%25\' viewBox=\'0 0 400 300\'%3E%3Crect width=\'400\' height=\'300\' fill=\'%23f3f4f6\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' font-family=\'Inter, sans-serif\' font-size=\'16\' fill=\'%239ca3af\'%3EImage not available%3C/text%3E%3C/svg%3E';">
//     </div>
// `;
//         }
//     }
//         let commentsHtml = '';
//         if (post.comments && post.comments.length > 0) {
//             commentsHtml = `
//                 <div class="comments-list">
//                     ${post.comments.map(comment => `
//                         <div class="comment">
//                             <div class="comment-avatar"></div>
//                             <div class="comment-content">
//                                 <div class="comment-author">${this.escapeHtml(comment.user?.name || 'User')}</div>
//                                 <div class="comment-text">${this.escapeHtml(comment.content)}</div>
//                                 <div class="comment-time">${comment.time_ago || 'just now'}</div>
//                             </div>
//                         </div>
//                     `).join('')}
//                 </div>
//             `;
//         }

//         return `
//             <div class="post-card" data-post-id="${post.id}">
//                 <div class="post-header">
//                     <div class="post-author-avatar">${this.escapeHtml(userAvatar)}</div>
//                     <div class="post-author-info">
//                         <div class="post-author-name">${this.escapeHtml(userName)}</div>
//                         <div class="post-meta">
//                             <span>${this.escapeHtml(userRole)}</span>
//                             <span><i class="fa-solid fa-circle" style="font-size: 4px;"></i></span>
//                             <span class="post-time">${this.escapeHtml(timeAgo)}</span>
//                             ${post.is_important ? '<span class="post-badge" style="background: #fee2e2; color: #dc2626;">IMPORTANT</span>' : ''}
//                         </div>
//                     </div>
//                 </div>
                
//                 <div class="post-content">
//                     <div class="post-text">${this.escapeHtml(post.content).replace(/\n/g, '<br>')}</div>
//                     ${imagesHtml}
//                 </div>
                
//                 <div class="post-stats">
//                     <span><i class="fa-regular fa-thumbs-up"></i> ${post.likes_count || 0}</span>
//                     <span><i class="fa-regular fa-comment"></i> ${post.comments_count || 0} comments</span>
//                 </div>
                
//                 <div class="post-engagement">
//                     <button class="engagement-btn ${post.liked_by_user ? 'liked' : ''}" onclick="window.announcementSystem.likePost(${post.id})">
//                         <i class="fa-regular fa-thumbs-up"></i>
//                         Like
//                     </button>
//                     <button class="engagement-btn" onclick="window.announcementSystem.toggleCommentSection(${post.id})">
//                         <i class="fa-regular fa-comment"></i>
//                         Comment
//                     </button>
//                 </div>
                
//                 <div class="comment-section" id="comment-section-${post.id}" style="display: none;">
//                     <div class="comment-input">
//                         <div class="comment-avatar"></div>
//                         <div class="comment-field">
//                             <input type="text" placeholder="Write a comment..." id="comment-input-${post.id}" maxlength="1000">
//                             <button onclick="window.announcementSystem.addComment(${post.id})">
//                                 <i class="fa-regular fa-paper-plane"></i>
//                             </button>
//                         </div>
//                     </div>
//                     ${commentsHtml}
//                 </div>
//             </div>
//         `;
//     }
createPostElement(post) {
    const timeAgo = post.time_ago || this.getTimeAgo(new Date(post.created_at));
    const userAvatar = post.user?.name ? post.user.name.substring(0, 2).toUpperCase() : 'JD';
    const userName = post.user?.first_name || 'Unknown User';
    const userRole = post.user?.role || 'Personnel';
    
    let imagesHtml = '';
    
    // Use image_urls if available
    console.log(post);
    if (post.image_urls && post.image_urls.length > 0) {
        if (post.image_urls.length === 1) {
            // Single image
            const imageUrl = post.image_urls[0];
            imagesHtml = `
                <div class="post-media">
                    <img src="${imageUrl}" alt="Post image" loading="lazy"
                         onerror="this.onerror=null; ">
                </div>
            `;
        } else {
            // Multiple images - create grid
            const imageGrid = post.image_urls.map(url => 
                `<img src="${url}" alt="Post image" loading="lazy" 
                      style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px;"
                      onerror="this.onerror=null; ">`
            ).join('');
            
            imagesHtml = `
                <div style="display: grid; grid-template-columns: repeat(${Math.min(post.image_urls.length, 2)}, 1fr); gap: 10px;">
                    ${imageGrid}
                </div>
            `;
        }
    }
    
    let commentsHtml = '';
    if (post.comments && post.comments.length > 0) {
        commentsHtml = `
            <div class="comments-list">
                ${post.comments.map(comment => `
                    <div class="comment">
                        <div class="comment-avatar"></div>
                        <div class="comment-content">
                            <div class="comment-author">${this.escapeHtml(comment.user?.name || 'User')}</div>
                            <div class="comment-text">${this.escapeHtml(comment.content)}</div>
                            <div class="comment-time">${comment.time_ago || 'just now'}</div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    return `
        <div class="post-card" data-post-id="${post.id}">
            <div class="post-header">
                <div class="post-author-avatar">${this.escapeHtml(userAvatar)}</div>
                <div class="post-author-info">
                    <div class="post-author-name">${this.escapeHtml(userName)}</div>
                    <div class="post-meta">
                        <span>${this.escapeHtml(userRole)}</span>
                        <span><i class="fa-solid fa-circle" style="font-size: 4px;"></i></span>
                        <span class="post-time">${this.escapeHtml(timeAgo)}</span>
                        ${post.is_important ? '<span class="post-badge" style="background: #fee2e2; color: #dc2626;">IMPORTANT</span>' : ''}
                    </div>
                </div>
            </div>
            
            <div class="post-content">
                <div class="post-text">${this.escapeHtml(post.content).replace(/\n/g, '<br>')}</div>
                ${imagesHtml}
            </div>
            
            <div class="post-stats">
                <span><i class="fa-regular fa-thumbs-up"></i> ${post.likes_count || 0}</span>
                <span><i class="fa-regular fa-comment"></i> ${post.comments_count || 0} comments</span>
            </div>
            
            <div class="post-engagement">
                <button class="engagement-btn ${post.liked_by_user ? 'liked' : ''}" onclick="window.announcementSystem.likePost(${post.id})">
                    <i class="fa-regular fa-thumbs-up"></i>
                    Like
                </button>
                <button class="engagement-btn" onclick="window.announcementSystem.toggleCommentSection(${post.id})">
                    <i class="fa-regular fa-comment"></i>
                    Comment
                </button>
            </div>
            
            <div class="comment-section" id="comment-section-${post.id}" style="display: none;">
                <div class="comment-input">
                    <div class="comment-avatar"></div>
                    <div class="comment-field">
                        <input type="text" placeholder="Write a comment..." id="comment-input-${post.id}" maxlength="1000">
                        <button onclick="window.announcementSystem.addComment(${post.id})">
                            <i class="fa-regular fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
                ${commentsHtml}
            </div>
        </div>
    `;
}
    getEmptyStateHtml() {
        return `
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fa-regular fa-message"></i>
                </div>
                <h3>No Announcements Yet</h3>
                <p>Be the first to post an announcement!</p>
                <button onclick="document.getElementById('postContent').focus()">
                    <i class="fa-regular fa-pen-to-square"></i>
                    Create First Announcement
                </button>
            </div>
        `;
    }

    toggleCommentSection(postId) {
        const section = document.getElementById(`comment-section-${postId}`);
        if (section) {
            const isHidden = section.style.display === 'none';
            section.style.display = isHidden ? 'block' : 'none';
            
            if (isHidden) {
                setTimeout(() => {
                    const input = document.getElementById(`comment-input-${postId}`);
                    if (input) input.focus();
                }, 100);
            }
        }
    }

    handleInfiniteScroll() {
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 1000) {
            this.loadPosts();
        }
    }

    showLoading(show) {
        const spinner = document.getElementById('loadingSpinner');
        if (spinner) {
            spinner.style.display = show ? 'block' : 'none';
        }
    }

    showNotification(message, type = 'info') {
        window.PyroNotify?.notify(message, type);
    }

    getTimeAgo(date) {
        const seconds = Math.floor((new Date() - date) / 1000);
        
        const intervals = [
            { label: 'year', seconds: 31536000 },
            { label: 'month', seconds: 2592000 },
            { label: 'day', seconds: 86400 },
            { label: 'hour', seconds: 3600 },
            { label: 'minute', seconds: 60 }
        ];
        
        for (let interval of intervals) {
            const count = Math.floor(seconds / interval.seconds);
            if (count > 1) return `${count} ${interval.label}s ago`;
            if (count === 1) return `1 ${interval.label} ago`;
        }
        
        return 'just now';
    }

    escapeHtml(unsafe) {
        if (!unsafe) return unsafe;
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
}

// Initialize the system
document.addEventListener('DOMContentLoaded', () => {
    window.announcementSystem = new AnnouncementSystem();
});
