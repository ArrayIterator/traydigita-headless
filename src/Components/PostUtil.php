<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components;

use TrayDigita\WP\Headless\Resource\Utils\Filter;
use WP_Post;
use WP_Post_Type;
use function apply_filters;
use function get_permalink;
use function get_post_format;
use function get_post_meta;
use function get_post_type_object;
use function in_array;
use function is_string;

class PostUtil
{
    /**
     * @param mixed|WP_Post|int $post
     * @param bool $preventZero
     * @return WP_Post|null
     */
    public function resolve(mixed $post, bool $preventZero = true): ?WP_Post
    {
        return Filter::post($post, $preventZero);
    }

    public function permalink(mixed $post): ?string
    {
        $post = $this->resolve($post);
        if (!$post) {
            return null;
        }
        $permalink = get_permalink($post);
        return is_string($permalink) ? $permalink : null;
    }

    public function meta(mixed $post, string $key, $default = null)
    {
        $post = $this->resolve($post);
        if (!$post) {
            return $default;
        }
        return get_post_meta($post->ID, $key, true) ?: $default;
    }

    public function postTypeObject(mixed $post): ?WP_Post_Type
    {
        $post = $this->resolve($post);
        if (!$post) {
            return null;
        }
        return get_post_type_object($post->post_type) ?: null;
    }

    public function postFormat(mixed $post): ?string
    {
        $post = $this->resolve($post);
        $post_format = $post ? get_post_format($post) : null;
        return is_string($post_format) ? $post_format : null;
    }

    public function postType(mixed $post): ?string
    {
        return $this->postTypeObject($post)?->name;
    }

    public function postTypeIs(mixed $post, string $name): bool
    {
        return $this->postTypeObject($post)?->name === $name;
    }

    public function isPage(mixed $post): bool
    {
        return $this->postTypeIs($post, 'page');
    }

    public function isAttachment(mixed $post): bool
    {
        return $this->postTypeIs($post, 'attachment');
    }

    public function isPost(mixed $post): bool
    {
        return $this->postTypeIs($post, 'post');
    }

    public function isSingle(mixed $post): bool
    {
        $post_type_obj = $this->postTypeObject($post);
        if (!$post_type_obj) {
            return false;
        }
        return $post_type_obj->publicly_queryable && $post->post_type !== 'page' && $post->post_type !== 'attachment';
    }

    public function isSingular(mixed $post): bool
    {
        $post_type = $this->postTypeObject($post);
        if (!$post_type) {
            return false;
        }
        return $post_type->publicly_queryable === true
            || $post_type->name === 'page'
            || $post_type->name === 'post'
            || $post_type->name === 'attachment';
    }

    public function isCustomPostType(mixed $post): bool
    {
        $post_type_obj = $this->postTypeObject($post);
        if (!$post_type_obj) {
            return false;
        }
        return !$post_type_obj->_builtin && !in_array($post->post_type, ['post', 'page', 'attachment']);
    }

    public function isPublished(mixed $post): bool
    {
        return $this->resolve($post)?->post_status === 'publish';
    }

    public function isDraft(mixed $post): bool
    {
        return $this->resolve($post)?->post_status === 'draft';
    }

    public function isRevision(mixed $post): bool
    {
        return $this->postTypeIs($post, 'revision');
    }

    public function singlePostTitle(mixed $post, string $prefix = '', string $suffix = ''): ?string
    {
        $post = $this->resolve($post);
        if (!$post) {
            return null;
        }
        $content = $prefix;
        $title = apply_filters('single_post_title', $post->post_title, $post);
        $content .= !is_string($title) ? $post->post_title : $title;
        $content .= $suffix;
        return $content;
    }
}
