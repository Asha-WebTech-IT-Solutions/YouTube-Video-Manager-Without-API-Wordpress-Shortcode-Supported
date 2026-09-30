<?php
/**
 * Mobility Electrified - YouTube Video Manager
 *
 * Admin:
 *   WordPress Admin > YouTube Videos
 *
 * Shortcode:
 *   [mobility_youtube_videos]
 *
 * Optional:
 *   [mobility_youtube_videos limit="6"]
 *   [mobility_youtube_videos columns="3"]
 *   [mobility_youtube_videos limit="6" columns="3"]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/* =========================================================
 * 1. REGISTER CUSTOM POST TYPE
 * ========================================================= */

function me_register_youtube_video_cpt() {

    $labels = array(
        'name'                  => 'YouTube Videos',
        'singular_name'         => 'YouTube Video',
        'menu_name'             => 'YouTube Videos',
        'name_admin_bar'        => 'YouTube Video',
        'add_new'               => 'Add New',
        'add_new_item'          => 'Add New YouTube Video',
        'new_item'              => 'New YouTube Video',
        'edit_item'             => 'Edit YouTube Video',
        'view_item'             => 'View YouTube Video',
        'all_items'             => 'All Videos',
        'search_items'          => 'Search Videos',
        'not_found'             => 'No videos found.',
        'not_found_in_trash'    => 'No videos found in Trash.',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_position'      => 25,
        'menu_icon'          => 'dashicons-video-alt3',
        'supports'           => array( 'title' ),
        'show_in_rest'       => false,
        'capability_type'    => 'post',
        'map_meta_cap'       => true,
    );

    register_post_type( 'me_youtube_video', $args );
}

add_action( 'init', 'me_register_youtube_video_cpt' );


/* =========================================================
 * 2. ADD META BOX
 * ========================================================= */

function me_add_youtube_video_meta_box() {

    add_meta_box(
        'me_youtube_video_details',
        'YouTube Video Details',
        'me_render_youtube_video_meta_box',
        'me_youtube_video',
        'normal',
        'high'
    );
}

add_action( 'add_meta_boxes', 'me_add_youtube_video_meta_box' );


/* =========================================================
 * 3. RENDER META BOX
 * ========================================================= */

function me_render_youtube_video_meta_box( $post ) {

    wp_nonce_field(
        'me_save_youtube_video',
        'me_youtube_video_nonce'
    );

    $video_url = get_post_meta(
        $post->ID,
        '_me_youtube_url',
        true
    );

    $description = get_post_meta(
        $post->ID,
        '_me_youtube_description',
        true
    );

    $active = get_post_meta(
        $post->ID,
        '_me_youtube_active',
        true
    );

    $sort_order = get_post_meta(
        $post->ID,
        '_me_youtube_sort_order',
        true
    );

    if ( $active === '' ) {
        $active = '1';
    }

    if ( $sort_order === '' ) {
        $sort_order = '0';
    }

    ?>

    <div style="max-width:900px;">

        <p>
            <label for="me_youtube_url">
                <strong>YouTube Video URL</strong>
            </label>
        </p>

        <input
            type="url"
            id="me_youtube_url"
            name="me_youtube_url"
            value="<?php echo esc_attr( $video_url ); ?>"
            placeholder="https://youtu.be/XXXXXXXXXXX"
            style="width:100%;max-width:700px;padding:10px;"
        />

        <p style="color:#666;">
            Supported formats:
            <br>
            https://youtu.be/VIDEO_ID
            <br>
            https://www.youtube.com/watch?v=VIDEO_ID
            <br>
            https://www.youtube.com/shorts/VIDEO_ID
            <br>
            https://www.youtube.com/embed/VIDEO_ID
        </p>


        <hr>


        <p>
            <label for="me_youtube_description">
                <strong>Description</strong>
            </label>
        </p>

        <textarea
            id="me_youtube_description"
            name="me_youtube_description"
            rows="5"
            style="width:100%;max-width:700px;padding:10px;"
            placeholder="Optional video description..."
        ><?php echo esc_textarea( $description ); ?></textarea>


        <hr>


        <table style="width:100%;max-width:700px;">

            <tr>

                <td style="width:180px;padding:10px 0;">
                    <strong>Status</strong>
                </td>

                <td>

                    <label>
                        <input
                            type="checkbox"
                            name="me_youtube_active"
                            value="1"
                            <?php checked( $active, '1' ); ?>
                        >

                        Active / Show on Website
                    </label>

                </td>

            </tr>


            <tr>

                <td style="padding:10px 0;">
                    <strong>Display Order</strong>
                </td>

                <td>

                    <input
                        type="number"
                        name="me_youtube_sort_order"
                        value="<?php echo esc_attr( $sort_order ); ?>"
                        min="0"
                        step="1"
                        style="width:120px;padding:8px;"
                    >

                    <p style="color:#666;margin-top:5px;">
                        Lower numbers appear first.
                    </p>

                </td>

            </tr>

        </table>


        <?php if ( ! empty( $video_url ) ) : ?>

            <hr>

            <h3>Video Preview</h3>

            <?php

            $video_id = me_extract_youtube_video_id( $video_url );

            if ( $video_id ) :

                ?>

                <div
                    style="
                        max-width:600px;
                        position:relative;
                        padding-bottom:56.25%;
                        height:0;
                        overflow:hidden;
                        background:#000;
                    "
                >

                    <iframe
                        src="https://www.youtube.com/embed/<?php echo esc_attr( $video_id ); ?>"
                        style="
                            position:absolute;
                            top:0;
                            left:0;
                            width:100%;
                            height:100%;
                            border:0;
                        "
                        loading="lazy"
                        allowfullscreen
                    ></iframe>

                </div>

            <?php else : ?>

                <p style="color:#d63638;">
                    Invalid YouTube URL. Please enter a valid YouTube video URL.
                </p>

            <?php endif; ?>

        <?php endif; ?>

    </div>

    <?php
}


/* =========================================================
 * 4. EXTRACT YOUTUBE VIDEO ID
 * ========================================================= */

function me_extract_youtube_video_id( $url ) {

    $url = trim( $url );

    if ( empty( $url ) ) {
        return false;
    }

    /*
     * youtu.be/VIDEO_ID
     */
    if (
        preg_match(
            '~(?:https?:\/\/)?(?:www\.)?youtu\.be\/([a-zA-Z0-9_-]{6,})~',
            $url,
            $matches
        )
    ) {
        return $matches[1];
    }


    /*
     * youtube.com/watch?v=VIDEO_ID
     */
    if (
        preg_match(
            '~(?:https?:\/\/)?(?:www\.)?youtube\.com\/watch\?[^#]*v=([a-zA-Z0-9_-]{6,})~',
            $url,
            $matches
        )
    ) {
        return $matches[1];
    }


    /*
     * youtube.com/shorts/VIDEO_ID
     */
    if (
        preg_match(
            '~(?:https?:\/\/)?(?:www\.)?youtube\.com\/shorts\/([a-zA-Z0-9_-]{6,})~',
            $url,
            $matches
        )
    ) {
        return $matches[1];
    }


    /*
     * youtube.com/embed/VIDEO_ID
     */
    if (
        preg_match(
            '~(?:https?:\/\/)?(?:www\.)?youtube\.com\/embed\/([a-zA-Z0-9_-]{6,})~',
            $url,
            $matches
        )
    ) {
        return $matches[1];
    }


    /*
     * youtube-nocookie.com/embed/VIDEO_ID
     */
    if (
        preg_match(
            '~(?:https?:\/\/)?(?:www\.)?youtube-nocookie\.com\/embed\/([a-zA-Z0-9_-]{6,})~',
            $url,
            $matches
        )
    ) {
        return $matches[1];
    }


    return false;
}


/* =========================================================
 * 5. SAVE VIDEO DETAILS
 * ========================================================= */

function me_save_youtube_video( $post_id ) {

    /*
     * Check nonce.
     */
    if (
        ! isset( $_POST['me_youtube_video_nonce'] )
        ||
        ! wp_verify_nonce(
            sanitize_text_field(
                wp_unslash(
                    $_POST['me_youtube_video_nonce']
                )
            ),
            'me_save_youtube_video'
        )
    ) {
        return;
    }


    /*
     * Autosave protection.
     */
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }


    /*
     * Revision protection.
     */
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }


    /*
     * Permission check.
     */
    if (
        ! current_user_can(
            'edit_post',
            $post_id
        )
    ) {
        return;
    }


    /*
     * Make sure correct post type.
     */
    if (
        get_post_type( $post_id )
        !==
        'me_youtube_video'
    ) {
        return;
    }


    /*
     * YouTube URL.
     */
    if ( isset( $_POST['me_youtube_url'] ) ) {

        $url = esc_url_raw(
            wp_unslash(
                $_POST['me_youtube_url']
            )
        );

        update_post_meta(
            $post_id,
            '_me_youtube_url',
            $url
        );
    }


    /*
     * Description.
     */
    if ( isset( $_POST['me_youtube_description'] ) ) {

        $description = sanitize_textarea_field(
            wp_unslash(
                $_POST['me_youtube_description']
            )
        );

        update_post_meta(
            $post_id,
            '_me_youtube_description',
            $description
        );
    }


    /*
     * Active status.
     */
    $active = isset(
        $_POST['me_youtube_active']
    ) ? '1' : '0';

    update_post_meta(
        $post_id,
        '_me_youtube_active',
        $active
    );


    /*
     * Sort order.
     */
    if ( isset( $_POST['me_youtube_sort_order'] ) ) {

        $sort_order = absint(
            $_POST['me_youtube_sort_order']
        );

        update_post_meta(
            $post_id,
            '_me_youtube_sort_order',
            $sort_order
        );
    }
}

add_action(
    'save_post_me_youtube_video',
    'me_save_youtube_video'
);


/* =========================================================
 * 6. ADMIN COLUMNS
 * ========================================================= */

function me_youtube_video_admin_columns( $columns ) {

    $new_columns = array();

    $new_columns['cb'] = isset( $columns['cb'] )
        ? $columns['cb']
        : '<input type="checkbox" />';

    $new_columns['title'] = 'Title';

    $new_columns['me_video_url'] = 'YouTube URL';

    $new_columns['me_video_status'] = 'Status';

    $new_columns['me_video_order'] = 'Order';

    $new_columns['date'] = 'Date';

    return $new_columns;
}

add_filter(
    'manage_me_youtube_video_posts_columns',
    'me_youtube_video_admin_columns'
);


/* =========================================================
 * 7. ADMIN COLUMN DATA
 * ========================================================= */

function me_youtube_video_admin_column_data(
    $column,
    $post_id
) {

    switch ( $column ) {

        case 'me_video_url':

            $url = get_post_meta(
                $post_id,
                '_me_youtube_url',
                true
            );

            if ( $url ) {

                echo '<a href="' .
                    esc_url( $url ) .
                    '" target="_blank" rel="noopener noreferrer">' .
                    esc_html( $url ) .
                    '</a>';

            } else {

                echo '—';
            }

            break;


        case 'me_video_status':

            $active = get_post_meta(
                $post_id,
                '_me_youtube_active',
                true
            );

            if ( $active === '1' ) {

                echo '<span style="color:#008a00;font-weight:600;">Active</span>';

            } else {

                echo '<span style="color:#999;">Inactive</span>';
            }

            break;


        case 'me_video_order':

            $order = get_post_meta(
                $post_id,
                '_me_youtube_sort_order',
                true
            );

            echo esc_html(
                $order !== ''
                    ? $order
                    : '0'
            );

            break;
    }
}

add_action(
    'manage_me_youtube_video_posts_custom_column',
    'me_youtube_video_admin_column_data',
    10,
    2
);


/* =========================================================
 * 8. FRONTEND SHORTCODE
 *
 * [mobility_youtube_videos]
 *
 * Optional:
 *
 * [mobility_youtube_videos limit="6"]
 *
 * [mobility_youtube_videos columns="3"]
 *
 * [mobility_youtube_videos limit="6" columns="3"]
 * ========================================================= */

function me_youtube_videos_shortcode( $atts ) {

    $atts = shortcode_atts(
        array(
            'limit'   => -1,
            'columns' => 2,
        ),
        $atts,
        'mobility_youtube_videos'
    );


    /*
     * Limit.
     */
    $limit = intval(
        $atts['limit']
    );

    if ( $limit === 0 ) {
        $limit = -1;
    }


    /*
     * Columns.
     */
    $columns = intval(
        $atts['columns']
    );

    if ( $columns < 1 ) {
        $columns = 1;
    }

    if ( $columns > 4 ) {
        $columns = 4;
    }


    /*
     * Query.
     */
    $videos = new WP_Query(
        array(
            'post_type'      => 'me_youtube_video',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,

            'meta_query'     => array(
                array(
                    'key'     => '_me_youtube_active',
                    'value'   => '1',
                    'compare' => '=',
                ),
            ),

            'meta_key'       => '_me_youtube_sort_order',
            'orderby'        => array(
                'meta_value_num' => 'ASC',
                'date'           => 'DESC',
            ),
        )
    );


    if ( ! $videos->have_posts() ) {

        return '';
    }


    /*
     * Prevent duplicate CSS.
     */
    static $css_added = false;


    ob_start();

    ?>

    <?php if ( ! $css_added ) : ?>

        <style>

            .me-youtube-video-wrapper {
                width: 100%;
                max-width: 100%;
                margin: 0 auto;
            }

            .me-youtube-video-grid {
                display: grid;
                grid-template-columns: repeat(
                    var(--me-video-columns),
                    minmax(0, 1fr)
                );
                gap: 30px;
                width: 100%;
            }

            .me-youtube-video-card {
                width: 100%;
                min-width: 0;
                overflow: hidden;
            }

            .me-youtube-video-frame {
                position: relative;
                width: 100%;
                aspect-ratio: 16 / 9;
                overflow: hidden;
                background: #000;
                border-radius: 10px;
            }

            .me-youtube-video-frame iframe {
                position: absolute;
                inset: 0;
                width: 100%;
                height: 100%;
                border: 0;
                display: block;
            }

            .me-youtube-video-title {
                margin: 14px 0 0;
                padding: 0;
                font-size: 20px;
                line-height: 1.35;
                font-weight: 600;
            }

            .me-youtube-video-description {
                margin: 8px 0 0;
                padding: 0;
                font-size: 15px;
                line-height: 1.6;
                color: #666;
            }


            /*
             * Large tablet / small desktop.
             */
            @media (max-width: 1024px) {

                .me-youtube-video-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 24px;
                }

            }


            /*
             * Mobile.
             */
            @media (max-width: 767px) {

                .me-youtube-video-grid {
                    grid-template-columns: 1fr;
                    gap: 25px;
                }

                .me-youtube-video-title {
                    font-size: 18px;
                }

                .me-youtube-video-description {
                    font-size: 14px;
                }

                .me-youtube-video-frame {
                    border-radius: 8px;
                }

            }


            /*
             * Very small phones.
             */
            @media (max-width: 480px) {

                .me-youtube-video-grid {
                    gap: 20px;
                }

            }

        </style>

        <?php

        $css_added = true;

    endif;


    ?>

    <div
        class="me-youtube-video-wrapper"
        style="--me-video-columns: <?php echo esc_attr( $columns ); ?>;"
    >

        <div class="me-youtube-video-grid">

            <?php while ( $videos->have_posts() ) : ?>

                <?php

                $videos->the_post();

                $post_id = get_the_ID();

                $video_url = get_post_meta(
                    $post_id,
                    '_me_youtube_url',
                    true
                );

                $description = get_post_meta(
                    $post_id,
                    '_me_youtube_description',
                    true
                );

                $video_id = me_extract_youtube_video_id(
                    $video_url
                );


                /*
                 * Skip invalid videos.
                 */
                if ( ! $video_id ) {
                    continue;
                }

                ?>

                <article
                    class="me-youtube-video-card"
                >

                    <div
                        class="me-youtube-video-frame"
                    >

                        <iframe
                            src="https://www.youtube.com/embed/<?php echo esc_attr( $video_id ); ?>"
                            title="<?php echo esc_attr( get_the_title() ); ?>"
                            loading="lazy"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen>
                        </iframe>

                    </div>


                    <?php if ( get_the_title() ) : ?>

                        <h3 class="me-youtube-video-title">

                            <?php
                            echo esc_html(
                                get_the_title()
                            );
                            ?>

                        </h3>

                    <?php endif; ?>


                    <?php if ( $description ) : ?>

                        <div class="me-youtube-video-description">

                            <?php
                            echo nl2br(
                                esc_html(
                                    $description
                                )
                            );
                            ?>

                        </div>

                    <?php endif; ?>

                </article>

            <?php endwhile; ?>

        </div>

    </div>

    <?php

    wp_reset_postdata();

    return ob_get_clean();
}

add_shortcode(
    'mobility_youtube_videos',
    'me_youtube_videos_shortcode'
);
