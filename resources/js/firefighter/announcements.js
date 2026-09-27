import '../../css/staff/announcements.css';

class AnnouncementSystem {
    constructor() {
        this.posts = [];
        this.currentPage = 1;
        this.loading = false;
        this.hasMore = true;
        this.selectedImages = [];
        this.selectedVideo = null;
        this.imagePreviewUrls = [];
        this.videoPreviewUrl = null;
        this.isSubmitting = false;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        this.editorPost = null;
        this.activeMediaPostId = null;
        this.maxVideoBytes = Number(window.PyroSenseUploadConfig?.announcementVideoMaxBytes || (50 * 1024 * 1024));
        this.handleInfiniteScrollBound = () => this.handleInfiniteScroll();
        this.documentClickHandler = (event) => {
            const menuTrigger = event.target.closest('[data-post-menu-trigger]');
            if (menuTrigger) {
                this.togglePostMenu(Number(menuTrigger.dataset.postMenuTrigger));
                return;
            }

            const mediaTrigger = event.target.closest('[data-open-media-post]');
            if (mediaTrigger) {
                this.openMediaDrawer(Number(mediaTrigger.dataset.openMediaPost), mediaTrigger.dataset.mediaType || 'image', mediaTrigger.dataset.mediaSrc || '');
                return;
            }

            const editTrigger = event.target.closest('[data-post-edit]');
            if (editTrigger) {
                this.openEditor(Number(editTrigger.dataset.postEdit));
                return;
            }

            const deleteTrigger = event.target.closest('[data-post-delete]');
            if (deleteTrigger) {
                this.deletePost(Number(deleteTrigger.dataset.postDelete));
                return;
            }

            this.closeAllMenus();
        };

        this.init();
    }

    init() {
        this.loadPosts(true);
        this.initializeEventListeners();
        this.updateComposerState();
    }

    initializeEventListeners() {
        window.addEventListener('scroll', this.handleInfiniteScrollBound);

        const postInput = document.getElementById('postContent');
        if (postInput) {
            postInput.addEventListener('input', this.autoResizeTextarea);
            postInput.addEventListener('input', () => this.updateComposerState());
        }

        document.getElementById('imageUpload')?.addEventListener('change', (event) => this.handleImageUpload(event));
        document.getElementById('videoUpload')?.addEventListener('change', (event) => this.handleVideoUpload(event));
        document.getElementById('togglePollBuilder')?.addEventListener('click', () => {
            const builder = document.getElementById('pollBuilder');
            const trigger = document.getElementById('togglePollBuilder');
            builder?.classList.toggle('active');
            trigger?.setAttribute('aria-pressed', builder?.classList.contains('active') ? 'true' : 'false');
            this.updateComposerState();
        });
        document.getElementById('addPollOption')?.addEventListener('click', () => this.addPollOptionField());
        document.getElementById('pollBuilder')?.addEventListener('input', () => this.updateComposerState());
        document.getElementById('closePostEditor')?.addEventListener('click', () => this.closeEditor());
        document.getElementById('postEditorModal')?.addEventListener('click', (event) => {
            if (event.target.id === 'postEditorModal') {
                this.closeEditor();
            }
        });
        document.getElementById('postMediaDrawer')?.addEventListener('click', (event) => {
            if (event.target.id === 'postMediaDrawer') {
                this.closeMediaDrawer();
            }
        });

        document.addEventListener('click', this.documentClickHandler);
        window.addEventListener('beforeunload', (event) => {
            if (!this.hasComposerContent()) return;
            event.preventDefault();
            event.returnValue = '';
        });
    }

    destroy() {
        window.removeEventListener('scroll', this.handleInfiniteScrollBound);
        document.removeEventListener('click', this.documentClickHandler);
    }

    autoResizeTextarea() {
        this.style.height = 'auto';
        const maxHeight = window.matchMedia('(max-width: 768px)').matches ? 132 : 180;
        const nextHeight = Math.min(this.scrollHeight, maxHeight);
        this.style.height = `${nextHeight}px`;
        this.style.overflowY = this.scrollHeight > maxHeight ? 'auto' : 'hidden';
    }

    async loadPosts(reset = false) {
        if (this.loading || (!this.hasMore && !reset)) return;

        this.loading = true;
        if (reset) {
            this.currentPage = 1;
            this.hasMore = true;
        }
        this.showLoading(true);

        try {
            const response = await fetch(`/firefighter/posts/get?page=${this.currentPage}`, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
            });

            if (!response.ok) {
                throw new Error('Failed to load posts');
            }

            const result = await response.json();
            const incomingPosts = result.data?.data || [];

            this.posts = reset ? incomingPosts : [...this.posts, ...incomingPosts];
            this.hasMore = Boolean(result.has_more);
            this.currentPage += 1;
            this.renderPosts();
        } catch (error) {
            console.error('Error loading posts:', error);
            this.showNotification('Failed to load posts.', 'error');
        } finally {
            this.loading = false;
            this.showLoading(false);
        }
    }

    async createPost() {
        if (this.isSubmitting) return;

        const content = document.getElementById('postContent')?.value.trim() || '';
        const pollPayload = this.collectPollPayload({ silent: false });

        if (pollPayload?.__invalid) {
            this.updateComposerState();
            return;
        }

        if (!content && this.selectedImages.length === 0 && !this.selectedVideo && !pollPayload) {
            this.setInlineError('Write an announcement or add media.');
            this.showNotification('Add announcement content first.', 'warning');
            return;
        }

        this.setInlineError('');
        this.isSubmitting = true;

        const formData = new FormData();
        formData.append('content', content);
        formData.append('post_type', pollPayload ? 'poll' : 'announcement');

        this.selectedImages.forEach((image) => formData.append('images[]', image));
        if (this.selectedVideo) {
            formData.append('video', this.selectedVideo);
        }
        if (pollPayload) {
            formData.append('poll_question', pollPayload.question);
            pollPayload.options.forEach((option) => formData.append('poll_options[]', option));
        }

        const submitButton = document.getElementById('submitAnnouncementButton');
        const originalHtml = submitButton?.innerHTML;
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Posting...';
        }

        this.showNotification('Posting announcement...', 'info');

        try {
            const response = await fetch('/firefighter/posts/store', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    Accept: 'application/json',
                },
                body: formData,
            });

            const result = await this.parseJsonResponse(response, 'The server returned an upload error instead of JSON.');
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to create post.');
            }

            this.posts.unshift(result.data);
            this.renderPosts();
            this.clearPost({ force: true });
            this.showNotification('Post published successfully.', 'success');
        } catch (error) {
            console.error('Error creating post:', error);
            this.showNotification(error.message || 'Failed to create post.', 'error');
        } finally {
            this.isSubmitting = false;
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = originalHtml;
            }
            this.updateComposerState();
        }
    }

    async updatePost(postId, formElement) {
        const formData = new FormData(formElement);
        const saveButton = document.getElementById('savePostChangesButton');
        const originalHtml = saveButton?.innerHTML;

        if (saveButton) {
            saveButton.disabled = true;
            saveButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
        }

        this.showNotification('Saving changes...', 'info');

        try {
            const response = await fetch(`/firefighter/posts/${postId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    Accept: 'application/json',
                    'X-HTTP-Method-Override': 'PUT',
                },
                body: formData,
            });

            const result = await this.parseJsonResponse(response, 'The server returned an upload error instead of JSON.');
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to update post.');
            }

            const index = this.posts.findIndex((post) => post.id === postId);
            if (index !== -1) {
                this.posts[index] = result.data;
            }

            this.renderPosts();
            this.closeEditor();
            this.showNotification('Post updated successfully.', 'success');
        } catch (error) {
            console.error('Error updating post:', error);
            this.showNotification(error.message || 'Failed to update post.', 'error');
        } finally {
            if (saveButton) {
                saveButton.disabled = false;
                saveButton.innerHTML = originalHtml;
            }
        }
    }

    async deletePost(postId) {
        const post = this.posts.find((item) => item.id === postId);
        if (!post) return;

        const confirmed = await this.confirmAction({
            eyebrow: 'Delete post',
            title: 'Delete announcement?',
            message: 'This post will be removed from the station feed.',
            confirmText: 'Delete',
            cancelText: 'Cancel',
        });
        if (!confirmed) return;

        this.showNotification('Deleting post...', 'info');

        try {
            const response = await fetch(`/firefighter/posts/${postId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    Accept: 'application/json',
                },
            });

            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to delete post.');
            }

            this.posts = this.posts.filter((item) => item.id !== postId);
            this.renderPosts();
            this.closeEditor();
            this.showNotification('Post deleted successfully.', 'success');
        } catch (error) {
            console.error('Error deleting post:', error);
            this.showNotification(error.message || 'Failed to delete post.', 'error');
        }
    }

    async likePost(postId) {
        try {
            const response = await fetch(`/firefighter/posts/${postId}/like`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    Accept: 'application/json',
                },
            });

            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to like post.');
            }

            const post = this.posts.find((item) => item.id === postId);
            if (post) {
                post.liked_by_user = result.liked;
                post.likes_count = result.likes_count;
                this.renderPosts();
            }
        } catch (error) {
            console.error('Error liking post:', error);
            this.showNotification('Failed to process like.', 'error');
        }
    }

    async addComment(postId) {
        const input = document.getElementById(`comment-input-${postId}`);
        const commentText = input?.value.trim();
        if (!commentText) return;

        try {
            const response = await fetch(`/firefighter/posts/${postId}/comment`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ content: commentText }),
            });

            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to add comment.');
            }

            const post = this.posts.find((item) => item.id === postId);
            if (post) {
                post.comments = post.comments || [];
                post.comments.push(result.data);
                post.comments_count = (post.comments_count || 0) + 1;
                input.value = '';
                this.renderPosts();
            }
        } catch (error) {
            console.error('Error adding comment:', error);
            this.showNotification('Failed to add comment.', 'error');
        }
    }

    handleImageUpload(event) {
        const files = Array.from(event.target.files || []);
        const validFiles = files.filter((file) => {
            if (!file.type.startsWith('image/')) {
                this.setInlineError('Only image files are supported.');
                return false;
            }
            if (file.size > 5 * 1024 * 1024) {
                this.setInlineError(`${file.name} is larger than 5MB.`);
                this.showNotification(`${file.name} is too large. Max 5MB.`, 'warning');
                return false;
            }
            return true;
        });

        this.selectedImages = [...validFiles];
        this.displayImagePreviews();
        this.setInlineError('');
        this.updateComposerState();
    }

    handleVideoUpload(event) {
        const file = event.target.files?.[0];
        if (!file) return;

        if (!file.type.startsWith('video/')) {
            this.setInlineError('Only video files are supported.');
            event.target.value = '';
            return;
        }

        if (file.size > this.maxVideoBytes) {
            const limitLabel = this.formatBytes(this.maxVideoBytes);
            this.setInlineError(`Video is larger than ${limitLabel}.`);
            this.showNotification(`Video is too large. Max ${limitLabel}.`, 'warning');
            event.target.value = '';
            return;
        }

        this.selectedVideo = file;
        this.displayVideoPreview();
        this.setInlineError('');
        this.updateComposerState();
    }

    addPollOptionField() {
        const container = document.getElementById('pollOptionsContainer');
        if (!container || container.querySelectorAll('[data-poll-option]').length >= 6) {
            return;
        }

        const row = document.createElement('div');
        row.className = 'poll-option-row';
        row.innerHTML = `
            <input class="post-input" data-poll-option maxlength="120" placeholder="Another option">
            <button class="btn btn-outline" type="button" data-remove-poll-option><i class="fa-solid fa-minus"></i></button>
        `;

        container.appendChild(row);
        row.querySelector('[data-remove-poll-option]')?.addEventListener('click', () => {
            row.remove();
            this.updateComposerState();
        });
        this.updateComposerState();
    }

    collectPollPayload({ silent = false } = {}) {
        const builder = document.getElementById('pollBuilder');
        if (!builder?.classList.contains('active')) return null;

        const question = document.getElementById('pollQuestion')?.value.trim();
        const options = Array.from(document.querySelectorAll('[data-poll-option]'))
            .map((input) => input.value.trim())
            .filter(Boolean);

        if (!question && options.length === 0) return null;
        if (!question || options.length < 2) {
            if (!silent) {
                this.setInlineError('Polls need one question and at least two options.');
                this.showNotification('Complete the poll first.', 'warning');
            }
            return { __invalid: true };
        }

        const uniqueOptions = new Set(options.map((option) => option.toLowerCase()));
        if (uniqueOptions.size !== options.length) {
            if (!silent) {
                this.setInlineError('Poll options cannot be identical.');
                this.showNotification('Poll options must be unique.', 'warning');
            }
            return { __invalid: true };
        }

        return { question, options };
    }

    displayImagePreviews() {
        const previewArea = document.getElementById('imagePreview');
        if (!previewArea) return;
        this.revokeImagePreviewUrls();
        previewArea.innerHTML = '';
        previewArea.classList.toggle('has-items', this.selectedImages.length > 0);
        document.querySelector('[data-attachment-action="image"]')?.classList.toggle('is-active', this.selectedImages.length > 0);

        if (this.selectedImages.length === 0) return;

        const grid = document.createElement('div');
        grid.className = `image-preview-grid count-${Math.min(this.selectedImages.length, 4)}`;
        const summary = document.createElement('div');
        summary.className = 'preview-summary';
        summary.textContent = `${this.selectedImages.length} photo${this.selectedImages.length === 1 ? '' : 's'} ready`;
        previewArea.appendChild(summary);
        previewArea.appendChild(grid);

        this.selectedImages.forEach((file, index) => {
            const objectUrl = URL.createObjectURL(file);
            this.imagePreviewUrls.push(objectUrl);
            const previewItem = document.createElement('div');
            previewItem.className = 'preview-item';
            previewItem.innerHTML = `
                <img src="${objectUrl}" alt="Selected photo preview">
                <button class="remove-preview" type="button" data-remove-image="${index}" aria-label="Remove selected photo">
                    <i class="fa-solid fa-times"></i>
                </button>
            `;
            grid.appendChild(previewItem);
            previewItem.querySelector('[data-remove-image]')?.addEventListener('click', () => this.removeImage(index));
        });
    }

    displayVideoPreview() {
        const previewArea = document.getElementById('videoPreview');
        if (!previewArea) return;

        if (!this.selectedVideo) {
            previewArea.classList.remove('active');
            previewArea.innerHTML = '';
            document.querySelector('[data-attachment-action="video"]')?.classList.remove('is-active');
            return;
        }

        this.revokeVideoPreviewUrl();
        this.videoPreviewUrl = URL.createObjectURL(this.selectedVideo);
        previewArea.classList.add('active');
        document.querySelector('[data-attachment-action="video"]')?.classList.add('is-active');
        previewArea.innerHTML = `
            <video controls preload="metadata" src="${this.videoPreviewUrl}"></video>
            <div class="video-preview-meta">
                <span>${this.escapeHtml(this.selectedVideo.name)} · ${this.formatBytes(this.selectedVideo.size)}</span>
                <button class="video-preview-remove" type="button" id="removeVideoPreviewButton">
                    Remove
                </button>
            </div>
        `;

        document.getElementById('removeVideoPreviewButton')?.addEventListener('click', () => {
            this.selectedVideo = null;
            document.getElementById('videoUpload').value = '';
            this.displayVideoPreview();
            this.updateComposerState();
        });
    }

    removeImage(index) {
        this.selectedImages.splice(index, 1);
        this.displayImagePreviews();
        const imageUpload = document.getElementById('imageUpload');
        if (imageUpload) imageUpload.value = '';
        this.updateComposerState();
    }

    async clearPost({ force = false } = {}) {
        if (!force && this.hasComposerContent()) {
            const confirmed = await this.confirmAction({
                eyebrow: 'Discard draft',
                title: 'Discard announcement?',
                message: 'Your text and selected media will be removed.',
                confirmText: 'Discard',
                cancelText: 'Keep editing',
            });

            if (!confirmed) return;
        }

        document.getElementById('postContent').value = '';
        document.getElementById('pollQuestion').value = '';
        document.getElementById('pollBuilder')?.classList.remove('active');
        document.getElementById('togglePollBuilder')?.setAttribute('aria-pressed', 'false');
        document.getElementById('pollOptionsContainer').innerHTML = `
            <div class="poll-option-row">
                <input class="post-input" data-poll-option maxlength="120" placeholder="Option 2">
                <button class="btn btn-outline" type="button" disabled><i class="fa-solid fa-grip-lines"></i></button>
            </div>
        `;
        const firstOption = document.querySelector('.poll-builder [data-poll-option]');
        if (firstOption) firstOption.value = '';

        this.selectedImages = [];
        this.selectedVideo = null;
        this.revokeImagePreviewUrls();
        this.revokeVideoPreviewUrl();
        document.getElementById('imageUpload').value = '';
        document.getElementById('videoUpload').value = '';
        this.displayImagePreviews();
        this.displayVideoPreview();
        this.setInlineError('');
        this.updateComposerState();
    }

    renderPosts() {
        const feed = document.getElementById('feed');
        if (!feed) return;

        if (this.posts.length === 0) {
            feed.innerHTML = this.getEmptyStateHtml();
            return;
        }

        feed.innerHTML = this.posts.map((post) => this.createPostElement(post)).join('');
    }

    createPostElement(post) {
        const authorName = this.getFullName(post.user);
        const authorRole = post.user?.role?.role ? this.capitalize(post.user.role.role) : 'Personnel';
        const timeAgo = post.time_ago || this.getTimeAgo(new Date(post.created_at));
        const canManage = Boolean(post.can_manage);
        const imagesHtml = this.renderImages(post.id, post.image_urls || []);
        const videoHtml = post.video_url ? `
            <div class="post-media">
                <video controls preload="metadata" src="${this.normalizeMediaUrl(post.video_url)}" data-open-media-post="${post.id}" data-media-type="video" data-media-src="${this.normalizeMediaUrl(post.video_url)}"></video>
            </div>
        ` : '';
        const pollHtml = this.renderPoll(post.poll_data);
        const commentsHtml = (post.comments || []).map((comment) => `
            <div class="comment">
                ${this.renderUserAvatar(comment.user, 'comment-avatar')}
                <div class="comment-content">
                    <div class="comment-author">${this.escapeHtml(this.getFullName(comment.user) || 'User')}</div>
                    <div class="comment-text">${this.escapeHtml(comment.content)}</div>
                    <div class="comment-time">${this.escapeHtml(comment.time_ago || 'just now')}</div>
                </div>
            </div>
        `).join('');

        return `
            <article class="post-card" data-post-id="${post.id}">
                <div class="post-header">
                    <div class="post-header-main">
                        ${this.renderUserAvatar(post.user, 'post-author-avatar')}
                        <div class="post-author-info">
                            <div class="post-author-meta">
                                <div>
                                    <div class="post-author-name">${this.escapeHtml(authorName)}</div>
                                    <div class="post-author-role">${this.escapeHtml(authorRole)}</div>
                                </div>
                                ${canManage ? `
                                    <div class="post-menu">
                                        <button type="button" class="post-menu-trigger" data-post-menu-trigger="${post.id}">
                                            <i class="fa-solid fa-ellipsis"></i>
                                        </button>
                                        <div class="post-menu-dropdown" id="post-menu-${post.id}">
                                            <button type="button" data-post-edit="${post.id}"><i class="fa-regular fa-pen-to-square"></i> Edit</button>
                                            <button type="button" data-post-delete="${post.id}"><i class="fa-regular fa-trash-can"></i> Delete</button>
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                            <div class="post-meta">
                                <span class="post-time">${this.escapeHtml(timeAgo)}</span>
                                ${post.post_type ? `<span class="post-badge">${this.escapeHtml(this.capitalize(post.post_type))}</span>` : ''}
                                ${post.is_important ? '<span class="post-badge" style="background:#fee2e2;color:#b91c1c;">Important</span>' : ''}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="post-content">
                    ${post.content ? `<div class="post-text">${this.escapeHtml(post.content).replace(/\n/g, '<br>')}</div>` : ''}
                    ${imagesHtml}
                    ${videoHtml}
                    ${pollHtml}
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

                <div class="comment-section" id="comment-section-${post.id}" style="display:none;">
                    <div class="comment-input">
                        ${this.renderUserAvatar(post.user, 'comment-avatar')}
                        <div class="comment-field">
                            <input type="text" placeholder="Write a comment..." id="comment-input-${post.id}" maxlength="1000">
                            <button type="button" onclick="window.announcementSystem.addComment(${post.id})">
                                <i class="fa-regular fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                    <div class="comments-list">${commentsHtml}</div>
                </div>
            </article>
        `;
    }

    renderImages(postId, imageUrls) {
        if (!imageUrls || imageUrls.length === 0) return '';
        const visibleUrls = imageUrls.slice(0, 4);
        const hiddenCount = Math.max(imageUrls.length - visibleUrls.length, 0);
        const items = visibleUrls.map((url, index) => {
            const normalizedUrl = this.normalizeMediaUrl(url);
            const image = `<img src="${normalizedUrl}" alt="Announcement photo" loading="lazy" data-open-media-post="${postId}" data-media-type="image" data-media-src="${normalizedUrl}" onerror="this.style.display='none'; this.closest('.post-media')?.classList.add('media-error');">`;
            if (hiddenCount > 0 && index === visibleUrls.length - 1) {
                return `<span class="post-media-more" data-more-label="+${hiddenCount}">${image}</span>`;
            }
            return image;
        }).join('');
        return `<div class="post-media post-media--count-${Math.min(imageUrls.length, 4)}">${items}</div>`;
    }

    openMediaDrawer(postId, mediaType, mediaSrc) {
        const post = this.posts.find((item) => item.id === postId);
        const drawer = document.getElementById('postMediaDrawer');
        if (!post || !drawer) return;

        const normalizedSrc = this.normalizeMediaUrl(mediaSrc || post.image_urls?.[0] || post.video_url || '');
        const visual = document.getElementById('postMediaDrawerVisual');
        const head = document.getElementById('postMediaDrawerHead');
        const body = document.getElementById('postMediaDrawerBody');
        const stats = document.getElementById('postMediaDrawerStats');
        const actions = document.getElementById('postMediaDrawerActions');

        this.activeMediaPostId = postId;

        visual.innerHTML = mediaType === 'video'
            ? `<video controls autoplay preload="metadata" src="${normalizedSrc}"></video>`
            : `<img src="${normalizedSrc}" alt="Post media">`;

        head.innerHTML = `
            <div class="media-drawer-author">
                ${this.renderUserAvatar(post.user, 'media-drawer-avatar')}
                <div>
                    <div class="media-drawer-name">${this.escapeHtml(this.getFullName(post.user))}</div>
                    <div class="media-drawer-meta">${this.escapeHtml(post.user?.role?.role ? this.capitalize(post.user.role.role) : 'Personnel')} | ${this.escapeHtml(post.time_ago || this.getTimeAgo(new Date(post.created_at)))}</div>
                </div>
            </div>
            <button type="button" class="media-drawer-close" id="closeMediaDrawerButton"><i class="fa-solid fa-times"></i></button>
        `;

        const commentsHtml = (post.comments || []).length > 0
            ? `
                <div class="media-drawer-comments">
                    ${(post.comments || []).map((comment) => `
                        <div class="media-drawer-comment">
                            ${this.renderUserAvatar(comment.user, 'media-drawer-comment-avatar')}
                            <div class="media-drawer-comment-body">
                                <div class="media-drawer-comment-author">${this.escapeHtml(this.getFullName(comment.user))}</div>
                                <div class="media-drawer-comment-text">${this.escapeHtml(comment.content)}</div>
                                <div class="media-drawer-comment-time">${this.escapeHtml(comment.time_ago || 'just now')}</div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `
            : '<div class="media-drawer-empty">No comments yet.</div>';

        body.innerHTML = `
            ${post.content ? `<div class="media-drawer-content">${this.escapeHtml(post.content)}</div>` : ''}
            ${post.poll_data ? this.renderPoll(post.poll_data) : ''}
            ${commentsHtml}
        `;

        stats.innerHTML = `
            <span><i class="fa-regular fa-thumbs-up"></i> ${post.likes_count || 0}</span>
            <span><i class="fa-regular fa-comment"></i> ${post.comments_count || 0} comments</span>
        `;

        actions.innerHTML = `
            <button class="engagement-btn ${post.liked_by_user ? 'liked' : ''}" type="button" id="drawerLikeButton">
                <i class="fa-regular fa-thumbs-up"></i>
                Like
            </button>
            <button class="engagement-btn" type="button" id="drawerCommentFocusButton">
                <i class="fa-regular fa-comment"></i>
                Comment
            </button>
        `;

        document.getElementById('closeMediaDrawerButton')?.addEventListener('click', () => this.closeMediaDrawer());
        document.getElementById('drawerLikeButton')?.addEventListener('click', async () => {
            await this.likePost(postId);
            const refreshed = this.posts.find((item) => item.id === postId);
            if (refreshed) {
                this.openMediaDrawer(postId, mediaType, normalizedSrc);
            }
        });
        document.getElementById('drawerCommentFocusButton')?.addEventListener('click', () => {
            this.closeMediaDrawer();
            this.toggleCommentSection(postId);
            setTimeout(() => document.getElementById(`comment-input-${postId}`)?.focus(), 80);
        });

        drawer.classList.add('active');
    }

    closeMediaDrawer() {
        this.activeMediaPostId = null;
        document.getElementById('postMediaDrawer')?.classList.remove('active');
        document.getElementById('postMediaDrawerVisual').innerHTML = '';
        document.getElementById('postMediaDrawerHead').innerHTML = '';
        document.getElementById('postMediaDrawerBody').innerHTML = '';
        document.getElementById('postMediaDrawerStats').innerHTML = '';
        document.getElementById('postMediaDrawerActions').innerHTML = '';
    }

    renderPoll(pollData) {
        if (!pollData?.question || !Array.isArray(pollData.options) || pollData.options.length === 0) {
            return '';
        }

        const options = pollData.options.map((option) => `
            <div class="post-poll-option">${this.escapeHtml(option.text || option)}</div>
        `).join('');

        return `
            <div class="post-poll">
                <div class="post-poll-question">${this.escapeHtml(pollData.question)}</div>
                <div class="post-poll-options">${options}</div>
            </div>
        `;
    }

    openEditor(postId) {
        const post = this.posts.find((item) => item.id === postId);
        if (!post) return;

        this.editorPost = post;
        const body = document.getElementById('postEditorBody');
        const footer = document.getElementById('postEditorFooter');
        if (!body || !footer) return;

        const pollQuestion = post.poll_data?.question || '';
        const pollOptions = Array.isArray(post.poll_data?.options)
            ? post.poll_data.options.map((option) => option.text || option)
            : [];

        body.innerHTML = `
            <form id="postEditorForm" class="composer-controls">
                <textarea class="post-input" name="content" rows="5" maxlength="5000">${this.escapeHtml(post.content || '')}</textarea>
                <input class="post-input" name="poll_question" maxlength="255" placeholder="Poll question" value="${this.escapeHtml(pollQuestion)}">
                <div id="postEditorPollOptions">
                    ${pollOptions.map((option) => `
                        <div class="poll-option-row">
                            <input class="post-input" name="poll_options[]" maxlength="120" value="${this.escapeHtml(option)}">
                            <button class="btn btn-outline" type="button" data-remove-editor-option><i class="fa-solid fa-minus"></i></button>
                        </div>
                    `).join('')}
                </div>
                <button class="btn btn-outline" type="button" id="addEditorPollOption"><i class="fa-solid fa-plus"></i> Add Poll Option</button>
                <label class="attachment-btn image" for="editorImageUpload">
                    <i class="fa-regular fa-image"></i>
                    Replace Images
                    <input type="file" id="editorImageUpload" name="images[]" accept="image/*" multiple hidden>
                </label>
                <label class="attachment-btn video" for="editorVideoUpload">
                    <i class="fa-solid fa-video"></i>
                    Replace Video
                    <input type="file" id="editorVideoUpload" name="video" accept="video/*" hidden>
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:0.9rem;color:#475569;">
                    <input type="checkbox" name="remove_images" value="1">
                    Remove current images
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:0.9rem;color:#475569;">
                    <input type="checkbox" name="remove_video" value="1">
                    Remove current video
                </label>
            </form>
        `;

        footer.innerHTML = `
            <button type="button" class="btn btn-outline" id="cancelPostEditorButton">Cancel</button>
            <button type="button" class="btn btn-primary" id="savePostChangesButton"><i class="fa-regular fa-floppy-disk"></i> Save Changes</button>
        `;

        body.querySelectorAll('[data-remove-editor-option]').forEach((button) => {
            button.addEventListener('click', () => button.closest('.poll-option-row')?.remove());
        });
        document.getElementById('postEditorForm')?.addEventListener('submit', (event) => {
            event.preventDefault();
            const form = document.getElementById('postEditorForm');
            const pollOptionInputs = form.querySelectorAll('input[name="poll_options[]"]');
            if (pollOptionInputs.length) {
                pollOptionInputs.forEach((input) => {
                    if (!input.value.trim()) input.remove();
                });
            }
            this.updatePost(postId, form);
        });
        document.getElementById('addEditorPollOption')?.addEventListener('click', () => {
            const container = document.getElementById('postEditorPollOptions');
            const row = document.createElement('div');
            row.className = 'poll-option-row';
            row.innerHTML = `
                <input class="post-input" name="poll_options[]" maxlength="120" placeholder="Another option">
                <button class="btn btn-outline" type="button" data-remove-editor-option><i class="fa-solid fa-minus"></i></button>
            `;
            container.appendChild(row);
            row.querySelector('[data-remove-editor-option]')?.addEventListener('click', () => row.remove());
        });
        document.getElementById('cancelPostEditorButton')?.addEventListener('click', () => this.closeEditor());
        document.getElementById('savePostChangesButton')?.addEventListener('click', () => {
            const form = document.getElementById('postEditorForm');
            const pollOptionInputs = form.querySelectorAll('input[name="poll_options[]"]');
            if (pollOptionInputs.length) {
                pollOptionInputs.forEach((input) => {
                    if (!input.value.trim()) input.remove();
                });
            }
            this.updatePost(postId, form);
        });

        document.getElementById('postEditorModal')?.classList.add('active');
    }

    closeEditor() {
        document.getElementById('postEditorModal')?.classList.remove('active');
        document.getElementById('postEditorBody').innerHTML = '';
        document.getElementById('postEditorFooter').innerHTML = '';
        this.editorPost = null;
    }

    togglePostMenu(postId) {
        document.querySelectorAll('.post-menu-dropdown').forEach((menu) => {
            if (menu.id === `post-menu-${postId}`) {
                menu.classList.toggle('active');
            } else {
                menu.classList.remove('active');
            }
        });
    }

    closeAllMenus() {
        document.querySelectorAll('.post-menu-dropdown').forEach((menu) => menu.classList.remove('active'));
    }

    getEmptyStateHtml() {
        return `
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fa-regular fa-message"></i>
                </div>
                <h3>No announcements yet</h3>
                <p>Station updates will appear here.</p>
            </div>
        `;
    }

    toggleCommentSection(postId) {
        const section = document.getElementById(`comment-section-${postId}`);
        if (!section) return;
        const hidden = section.style.display === 'none';
        section.style.display = hidden ? 'block' : 'none';
    }

    handleInfiniteScroll() {
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 900) {
            this.loadPosts();
        }
    }

    showLoading(show) {
        const spinner = document.getElementById('loadingSpinner');
        if (spinner) {
            spinner.style.display = show ? 'block' : 'none';
        }
    }

    confirmAction({
        eyebrow = 'Confirm action',
        title = 'Are you sure?',
        message = 'Please confirm to continue.',
        confirmText = 'Confirm',
        cancelText = 'Cancel',
    } = {}) {
        const dialog = document.getElementById('announcementConfirm');
        const titleEl = document.getElementById('announcementConfirmTitle');
        const messageEl = document.getElementById('announcementConfirmMessage');
        const eyebrowEl = document.getElementById('announcementConfirmEyebrow');
        const confirmButton = dialog?.querySelector('[data-announcement-confirm-ok]');
        const cancelButton = dialog?.querySelector('[data-announcement-confirm-cancel]');

        if (!dialog || !titleEl || !messageEl || !confirmButton || !cancelButton) {
            return Promise.resolve(false);
        }

        titleEl.textContent = title;
        messageEl.textContent = message;
        if (eyebrowEl) eyebrowEl.textContent = eyebrow;
        confirmButton.textContent = confirmText;
        cancelButton.textContent = cancelText;

        return new Promise((resolve) => {
            const previousFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;

            const close = (value) => {
                dialog.hidden = true;
                dialog.setAttribute('aria-hidden', 'true');
                confirmButton.removeEventListener('click', onConfirm);
                cancelButton.removeEventListener('click', onCancel);
                dialog.removeEventListener('click', onBackdrop);
                document.removeEventListener('keydown', onKeydown);
                previousFocus?.focus?.({ preventScroll: true });
                resolve(value);
            };

            const onConfirm = () => close(true);
            const onCancel = () => close(false);
            const onBackdrop = (event) => {
                if (event.target === dialog) close(false);
            };
            const onKeydown = (event) => {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    close(false);
                }
            };

            confirmButton.addEventListener('click', onConfirm);
            cancelButton.addEventListener('click', onCancel);
            dialog.addEventListener('click', onBackdrop);
            document.addEventListener('keydown', onKeydown);

            dialog.hidden = false;
            dialog.setAttribute('aria-hidden', 'false');
            cancelButton.focus({ preventScroll: true });
        });
    }

    showNotification(message, type = 'info') {
        window.PyroNotify?.notify(message, type, type === 'error' ? 5200 : 3200);
    }

    updateComposerState() {
        const submitButton = document.getElementById('submitAnnouncementButton');
        const clearButton = document.getElementById('composerClearButton');
        const pollButton = document.getElementById('togglePollBuilder');
        const pollBuilder = document.getElementById('pollBuilder');
        const hasContent = this.hasComposerContent();
        const hasText = Boolean(document.getElementById('postContent')?.value.trim());
        const pollState = this.getPollState();
        const canSubmit = hasText || this.selectedImages.length > 0 || Boolean(this.selectedVideo) || pollState.valid;

        if (submitButton) {
            submitButton.disabled = this.isSubmitting || !canSubmit;
            submitButton.setAttribute('aria-disabled', submitButton.disabled ? 'true' : 'false');
        }

        if (clearButton) {
            clearButton.hidden = !hasContent;
        }

        pollButton?.classList.toggle('is-active', Boolean(pollBuilder?.classList.contains('active') && (pollState.hasAnyInput || pollState.valid)));
    }

    hasComposerContent() {
        const hasText = Boolean(document.getElementById('postContent')?.value.trim());
        const hasQuestion = Boolean(document.getElementById('pollQuestion')?.value.trim());
        const hasOption = Array.from(document.querySelectorAll('[data-poll-option]')).some((input) => input.value.trim());
        return hasText || hasQuestion || hasOption || this.selectedImages.length > 0 || Boolean(this.selectedVideo);
    }

    getPollState() {
        const builder = document.getElementById('pollBuilder');
        if (!builder?.classList.contains('active')) {
            return { valid: false, hasAnyInput: false };
        }

        const question = document.getElementById('pollQuestion')?.value.trim();
        const options = Array.from(document.querySelectorAll('[data-poll-option]'))
            .map((input) => input.value.trim())
            .filter(Boolean);
        const uniqueOptions = new Set(options.map((option) => option.toLowerCase()));

        return {
            valid: Boolean(question && options.length >= 2 && uniqueOptions.size === options.length),
            hasAnyInput: Boolean(question || options.length),
        };
    }

    setInlineError(message = '') {
        const error = document.getElementById('composerInlineError');
        const text = error?.querySelector('span');
        if (!error || !text) return;
        text.textContent = message;
        error.hidden = !message;
    }

    revokeImagePreviewUrls() {
        this.imagePreviewUrls.forEach((url) => URL.revokeObjectURL(url));
        this.imagePreviewUrls = [];
    }

    revokeVideoPreviewUrl() {
        if (this.videoPreviewUrl) {
            URL.revokeObjectURL(this.videoPreviewUrl);
            this.videoPreviewUrl = null;
        }
    }

    formatBytes(bytes = 0) {
        if (!bytes) return '0 KB';
        const units = ['B', 'KB', 'MB', 'GB'];
        const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
        return `${(bytes / Math.pow(1024, index)).toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
    }

    async parseJsonResponse(response, fallbackMessage = 'The server returned an invalid response.') {
        const text = await response.text();
        try {
            return text ? JSON.parse(text) : {};
        } catch (error) {
            const cleanText = text
                .replace(/<[^>]*>/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
            const lowerText = cleanText.toLowerCase();
            const uploadLimitHit = lowerText.includes('post content-length')
                || lowerText.includes('exceeds the limit')
                || lowerText.includes('upload_max_filesize')
                || lowerText.includes('post_max_size');

            throw new Error(uploadLimitHit
                ? `The video is larger than the server upload limit. Compress it or keep it under ${this.formatBytes(this.maxVideoBytes)}.`
                : (cleanText || fallbackMessage));
        }
    }

    getTimeAgo(date) {
        const seconds = Math.floor((new Date() - date) / 1000);
        const intervals = [
            { label: 'year', seconds: 31536000 },
            { label: 'month', seconds: 2592000 },
            { label: 'day', seconds: 86400 },
            { label: 'hour', seconds: 3600 },
            { label: 'minute', seconds: 60 },
        ];

        for (const interval of intervals) {
            const count = Math.floor(seconds / interval.seconds);
            if (count > 1) return `${count} ${interval.label}s ago`;
            if (count === 1) return `1 ${interval.label} ago`;
        }

        return 'just now';
    }

    getFullName(user) {
        if (!user) return 'Unknown User';
        const firstName = user.first_name || '';
        const lastName = user.last_name || '';
        const fallback = user.full_name || user.name || '';
        return `${firstName} ${lastName}`.trim() || fallback || 'Unknown User';
    }

    getInitials(user) {
        if (!user) return 'US';
        const first = (user.first_name || '').trim().charAt(0);
        const last = (user.last_name || '').trim().charAt(0);
        const fallback = (user.name || 'US').trim().slice(0, 2);
        return (first + last).toUpperCase() || fallback.toUpperCase();
    }

    renderUserAvatar(user, className) {
        const initials = this.escapeHtml(this.getInitials(user));
        const imageUrl = this.normalizeMediaUrl(user?.profile_image_url || '');

        if (!imageUrl) {
            return `<div class="${className}">${initials}</div>`;
        }

        return `<div class="${className}"><img src="${this.escapeHtml(imageUrl)}" alt="${this.escapeHtml(this.getFullName(user))}"></div>`;
    }

    capitalize(value) {
        return value ? value.charAt(0).toUpperCase() + value.slice(1) : '';
    }

    normalizeMediaUrl(url) {
        if (!url) return '';
        if (url.startsWith('http://') || url.startsWith('https://')) return url;
        if (url.startsWith('/storage/')) return url;
        if (url.startsWith('storage/')) return `/${url}`;
        if (!url.includes('/')) return `/storage/posts/${String(url).replace(/^\/+/, '')}`;
        return `/storage/${String(url).replace(/^\/+/, '')}`;
    }

    escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) return '';
        return String(unsafe)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
}

function initializeAnnouncementSystem() {
    if (window.announcementSystem?.destroy) {
        window.announcementSystem.destroy();
    }

    window.announcementSystem = new AnnouncementSystem();
}

window.initializeAnnouncementSystem = initializeAnnouncementSystem;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAnnouncementSystem);
} else {
    initializeAnnouncementSystem();
}
