<?php
/*
Plugin Name: para-short
Description:
Version: 1.0
Author:
Author URI:
*/

function para_short_register_type()
{
    $labels = array(
        "name" => __("Paragraphs", "para-short"),
        "singular_name" => __("Paragraph", "para-short"),
    );

    $args = array(
        "label" => __("Paragraphs", "para-short"),
        "labels" => $labels,
        "description" => "",
        "public" => true,
        "publicly_queryable" => true,
        "show_ui" => true,
        "show_in_rest" => false,
        "rest_base" => "",
        "has_archive" => false,
        "show_in_menu" => true,
        "show_in_nav_menus" => true,
        "exclude_from_search" => false,
        "capability_type" => "post",
        "map_meta_cap" => true,
        "hierarchical" => false,
        "rewrite" => array("slug" => "para_short", "with_front" => true),
        "query_var" => true,
        "supports" => array("title", "editor"),
    );

    register_post_type("para_short", $args);
}

add_action('init', 'para_short_register_type');

function para_short_get_paragraph($atts)
{
    if ($atts['id'] != '') {
        global $wpdb;
        $result = $wpdb->get_results($wpdb->prepare("SELECT post_content FROM $wpdb->posts WHERE post_type = 'para_short' AND post_status = 'publish' AND post_title = '%s' LIMIT 1", $wpdb->esc_like($atts['id'])));
        if (isset($result[0]->post_content))
            return "<p>" . $result[0]->post_content . "</p>";
    }
    return "";
}

add_shortcode('paragraph', 'para_short_get_paragraph');

function para_short_save_post($post_id)
{
    $post = get_post($post_id);
    if (wp_is_post_revision($post_id) || ($post->post_type != 'post' AND $post->post_type != 'page'))
        return;
    preg_match_all('~<p.*id="(.*)">(.*)<\/p>~iUs', $post->post_content, $matches);
    if (isset($matches[1]) || count($matches[1])) {
        for ($i = 0; $i < count($matches[1]); $i++) {
            $id = $matches[1][$i];
            $text = "";
            if (isset($matches[2][$i]))
                $text = $matches[2][$i];
            para_short_update_para_short($id, $text);
        }
    }
}

add_action('save_post', 'para_short_save_post');

function para_short_update_para_short($id, $text)
{
    global $wpdb;
    $result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_type = 'para_short' AND post_title = '%s' LIMIT 1", $wpdb->esc_like($id)));
    $post = $result[0];
    if ($post->ID > 0) {
        wp_update_post(array(
            'ID' => $post->ID,
            'post_content' => $text
        ));
    } else {
        wp_insert_post(array(
            'post_type' => 'para_short',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_title' => $id,
            'post_content' => $text
        ));
    }
}
