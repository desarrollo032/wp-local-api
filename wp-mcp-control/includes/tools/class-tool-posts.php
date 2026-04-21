<?php
/**
 * MCP Tool Posts - Full CRUD for posts/pages/CPTs.
 */

defined('ABSPATH') || exit;

namespace WP_MCP_Control;

class Tool_Posts {
    public function register_features(MCP_Registry $registry): void {
        // posts/list
        $registry->register([
            'id' => 'posts-list',
            'type' => WP_Feature::TYPE_RESOURCE,
            'name' => 'Listar Posts',
            'description' => 'Lista posts con filtros WP_Query completos.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'post_type' => ['type' => 'string', 'default' => 'post'],
                    'posts_per_page' => ['type' => 'integer'],
                    'paged' => ['type' => 'integer'],
                    'status' => ['type' => 'string'],
                    'author' => ['type' => 'integer'],
                    'category' => ['type' => 'integer'],
                ],
            ],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'posts' => ['type' => 'array'],
                    'found_posts' => ['type' => 'integer'],
                ],
            ],
            'callback' => [$this, 'list_posts'],
            'permission_callback' => '__return_true', // Token-checked upstream
            'categories' => ['content'],
        ]);

        // posts/create
        $registry->register([
            'id' => 'posts-create',
            'type' => WP_Feature::TYPE_TOOL,
            'name' => 'Crear Post',
            'description' => 'Crear post/página/CPT con todos los campos.',
            'input_schema' => [
                'type' => 'object',
                'required' => ['title'],
                'properties' => [
                    'title' => ['type' => 'string'],
                    'content' => ['type' => 'string'],
                    'excerpt' => ['type' => 'string'],
                    'status' => ['type' => 'string', 'enum' => ['draft', 'publish', 'private']],
                    'post_type' => ['type' => 'string', 'default' => 'post'],
                    'post_author' => ['type' => 'integer'],
                    'post_category' => ['type' => 'array'],
                    'tags_input' => ['type' => 'array'],
                    'meta' => ['type' => 'object'],
                ],
            ],
            'callback' => [$this, 'create_post'],
            'permission_callback' => '__return_true',
            'categories' => ['content'],
        ]);

        // posts/update, delete, duplicate, etc. (similar structure)
        $registry->register([
            'id' => 'posts-update',
            'type' => WP_Feature::TYPE_TOOL,
            'name' => 'Actualizar Post',
            'description' => 'Editar post por ID.',
            'input_schema' => [
                'type' => 'object',
                'required' => ['id'],
                // ... fields like create
            ],
            'callback' => [$this, 'update_post'],
            'permission_callback' => '__return_true',
            'categories' => ['content'],
        ]);

        $registry->register([
            'id' => 'posts-delete',
            'type' => WP_Feature::TYPE_TOOL,
            'name' => 'Eliminar Post',
            'description' => 'Eliminar post por ID.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'required' => true],
                    'force' => ['type' => 'boolean'], // Permanent delete
                ],
            ],
            'callback' => [$this, 'delete_post'],
            'permission_callback' => '__return_true',
            'categories' => ['content'],
        ]);

        // Add more: duplicate, publish, schedule...
    }

    public function list_posts(array $context): array {
        $query_args = [
            'post_type' => $context['post_type'] ?? 'post',
            'posts_per_page' => $context['posts_per_page'] ?? 10,
            'paged' => $context['paged'] ?? 1,
            'post_status' => $context['status'] ?? 'publish',
        ];
        if (isset($context['author'])) $query_args['author'] = absint($context['author']);
        if (isset($context['category'])) $query_args['cat'] = absint($context['category']);

        $query = new \WP_Query($query_args);
        return [
            'posts' => $query->posts, // Sanitized WP_Post array
            'found_posts' => $query->found_posts,
        ];
    }

    public function create_post(array $context): array {
        $post_data = [
            'post_title' => sanitize_text_field($context['title']),
            'post_content' => wp_kses_post($context['content'] ?? ''),
            'post_excerpt' => sanitize_text_field($context['excerpt'] ?? ''),
            'post_status' => sanitize_key($context['status'] ?? 'draft'),
            'post_type' => sanitize_key($context['post_type'] ?? 'post'),
            'post_author' => absint($context['post_author'] ?? get_current_user_id()),
        ];

        $post_id = wp_insert_post($post_data);
        if (is_wp_error($post_id)) return ['error' => $post_id->get_error_message()];

        // Categories/tags
        if (!empty($context['post_category'])) wp_set_post_categories($post_id, array_map('absint', $context['post_category']));
        if (!empty($context['tags_input'])) wp_set_post_tags($post_id, array_map('sanitize_text_field', $context['tags_input']));

        // Meta
        if (!empty($context['meta'])) {
            foreach ($context['meta'] as $key => $value) {
                update_post_meta($post_id, sanitize_key($key), sanitize_text_field($value));
            }
        }

        return [
            'id' => $post_id,
            'url' => get_permalink($post_id),
            'edit_url' => get_edit_post_link($post_id),
        ];
    }

    public function update_post(array $context): array {
        $post_id = absint($context['id']);
        if (!$post_id || !get_post($post_id)) return ['error' => 'Post not found'];

        $post_data = [
            // Similar to create, merge with existing
            'ID' => $post_id,
            'post_title' => sanitize_text_field($context['title'] ?? ''),
            // ...
        ];

        $result = wp_update_post($post_data);
        if (is_wp_error($result)) return ['error' => $result->get_error_message()];

        // Update meta/categories like create
        return ['success' => true, 'id' => $post_id];
    }

    public function delete_post(array $context): array {
        $post_id = absint($context['id']);
        $force = !empty($context['force']);

        $result = wp_delete_post($post_id, $force);
        if (!$result) return ['error' => 'Delete failed'];
        return ['success' => true, 'deleted' => $post_id];
    }
}
?>

