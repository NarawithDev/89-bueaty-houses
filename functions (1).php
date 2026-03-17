<?php
/**
 * Astra Child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Astra Child
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_ASTRA_CHILD_VERSION', '1.0.0' );

/**
 * Enqueue styles
 */
function child_enqueue_styles() {

	wp_enqueue_style( 'astra-child-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_ASTRA_CHILD_VERSION, 'all' );

}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );

 //customize login page
 function custom_login_logo() {
    echo '
    <style type="text/css">
        h1 a { 
            background-image: url(/wp-content/uploads/2026/01/Logo-site-header.webp) !important; 
            background-size: contain !important;
            width: 100% !important;
            height: 100px !important;
        }
        #backtoblog {
            display:none;
        }
body.login.js.login-action-login.wp-core-ui  {
			background-image: radial-gradient(at center center, ##F1F1F1 0%, #ffffff 100%);
		}
		#login #nav a {
		color:#FFF;
		}
        .wp-core-ui .button-primary {
            background:#E68585;
            border-color: #E68585;
        }
        .wp-core-ui .button-primary:hover {
            background:#000;
            border-color: #000;
        }
    </style>
    ';
}
add_action( 'login_head', 'custom_login_logo' );

/**
 * Shortcode: [prod_count taxonomy="category-products" term="slug"]
 * - นับจำนวนโพสต์ post_type=products ที่อยู่ใน taxonomy+term ที่ระบุ
 */
add_shortcode('prod_count', function ($atts) {

  $atts = shortcode_atts([
    'brand'     => '',               // term slug เช่น yaman
    'category'  => '',               // alias เผื่ออยากใช้ชื่อเดิม
    'taxonomy'  => 'brand-products', // taxonomy slug ของ Brand Products
    'post_type' => 'products',       // CPT slug ของคุณ
  ], $atts, 'prod_count');

  $tax       = sanitize_key($atts['taxonomy']);
  $post_type = sanitize_key($atts['post_type']);

  // ให้ใช้ brand ก่อน ถ้าไม่ใส่ brand ให้ไปอ่าน category แทน
  $term_slug = $atts['brand'] !== '' ? $atts['brand'] : $atts['category'];
  $term_slug = sanitize_title($term_slug);

  if (empty($tax) || empty($term_slug) || empty($post_type)) {
    return '0';
  }

  if ( ! taxonomy_exists($tax) || ! post_type_exists($post_type) ) {
    return '0';
  }

  $term = get_term_by('slug', $term_slug, $tax);
  if ( ! $term || is_wp_error($term) ) {
    return '0';
  }

  $q = new WP_Query([
    'post_type'           => $post_type,
    'post_status'         => 'publish',
    'fields'              => 'ids',
    'posts_per_page'      => 1,       // เอาแค่ count
    'no_found_rows'       => false,   // ต้อง false เพื่อให้ found_posts ใช้ได้
    'ignore_sticky_posts' => true,
    'tax_query'           => [
      [
        'taxonomy' => $tax,
        'field'    => 'term_id',
        'terms'    => [(int) $term->term_id],
      ],
    ],
  ]);

  return (string) (int) $q->found_posts;
});

/**
 * Shortcode: [brand_image]
 * - ดึง ACF image field "image_brand" จาก term ใน taxonomy "brand-products"
 * - ใช้ใน post loop (เช่น UABB Posts) ได้
 *
 * Usage:
 * [brand_image size="large" linked="yes"]
 */
add_shortcode('brand_image', function($atts){

  $atts = shortcode_atts([
    'taxonomy' => 'brand-products',
    'field'    => 'image_brand',
    'size'     => 'thumbnail',
    'linked'   => 'yes',   // yes|no (ลิงก์ไปหน้า term)
    'post_id'  => 0,       // ปกติไม่ต้องใส่
  ], $atts, 'brand_image');

  $post_id  = (int) $atts['post_id'];
  if (!$post_id) $post_id = get_the_ID();
  if (!$post_id) return '';

  $tax   = sanitize_key($atts['taxonomy']);
  $field = sanitize_key($atts['field']);
  $size  = sanitize_key($atts['size']);
  $linked = ($atts['linked'] === 'yes');

  // หา term ของโพสต์นี้ใน brand-products
  $terms = get_the_terms($post_id, $tax);
  if (empty($terms) || is_wp_error($terms)) return '';

  $term = $terms[0]; // เอา term แรก (ถ้ามีหลายแบรนด์ บอกได้ เดี๋ยวปรับให้เลือกได้)

  // ดึงค่า ACF จาก term (ACF ต้องใช้ key แบบ taxonomy_termID)
  if (!function_exists('get_field')) return '';
  $acf_key = $tax . '_' . $term->term_id;

  $img = get_field($field, $acf_key);
  if (empty($img)) return '';

  // รองรับทั้ง return เป็น ID / Array / URL
  $img_html = '';
  if (is_array($img)) {
    if (!empty($img['ID'])) {
      $img_html = wp_get_attachment_image((int)$img['ID'], $size);
    } elseif (!empty($img['url'])) {
      $img_html = '<img src="'.esc_url($img['url']).'" alt="'.esc_attr($term->name).'">';
    }
  } elseif (is_numeric($img)) {
    $img_html = wp_get_attachment_image((int)$img, $size);
  } else {
    $img_html = '<img src="'.esc_url($img).'" alt="'.esc_attr($term->name).'">';
  }

  if (!$img_html) return '';

  // ลิงก์ไปหน้า term archive
  if ($linked) {
    $term_link = get_term_link($term);
    if (!is_wp_error($term_link)) {
      $img_html = '<a href="'.esc_url($term_link).'">'.$img_html.'</a>';
    }
  }

  return $img_html;
});

/**
 * Enqueue CSS/JS สำหรับ product tabs
 */
add_action('wp_enqueue_scripts', function () {

  // ✅ หน้า single ของ CPT: products
  if (is_singular('products')) {

    // แนะนำ: ถ้า style ของแท็บอยู่ในไฟล์แยก (เช่น product-tabs.css)
    // ให้เปลี่ยน path ตามจริงของคุณ
    wp_enqueue_style(
      'product-tabs',
      get_stylesheet_directory_uri() . '/style.css',
      [],
      '1.0.1'
    );

    wp_enqueue_script(
      'product-tabs',
      get_stylesheet_directory_uri() . '/script.js',
      [],
      '1.0.1',
      true
    );
  }
});


/**
 * Shortcode: [product_tabs]
 * ACF fields required:
 * - spec_rows (Repeater) -> spec_label, spec_value
 * - manual_url (URL)
 * - manual_subtitle (Text) optional
 * - warranty_content (WYSIWYG/Textarea) optional
 * - comparison_table_link (URL) optional
 *
 * Comparison tab condition:
 * - Brand Products (taxonomy: brand-products) term slug = panasonic
 * - Category Products (taxonomy: category-products) term slug IN (body, face, hair)
 *
 * If comparison_table_link is empty:
 * - Tab still shows
 * - Panel shows "ไม่มีตารางเปรียบเทียบ"
 * - Button is disabled (optional behavior included)
 */
add_shortcode('product_tabs', function ($atts) {

  if (!function_exists('get_field')) return '';

  $atts = shortcode_atts([
    'id'                     => 0,
    'open_manual_new_tab'    => 'yes', // yes|no (applies to manual + comparison)
    'hide_warranty_if_empty' => 'no',  // yes|no
  ], $atts, 'product_tabs');

  $post_id = (int) $atts['id'];
  if (!$post_id) $post_id = get_the_ID();
  if (!$post_id) return '';

  $spec_rows       = get_field('spec_rows', $post_id);
  $manual_url      = trim((string) get_field('manual_url', $post_id));
  $manual_subtitle = trim((string) get_field('manual_subtitle', $post_id));
  $warranty        = (string) get_field('warranty_content', $post_id);

  // Comparison link (ACF URL) - optional
  $comparison_url  = trim((string) get_field('comparison_table_link', $post_id));
  $has_comparison_link = !empty($comparison_url);

  $has_specs    = is_array($spec_rows) && !empty($spec_rows);
  $has_manual   = !empty($manual_url);
  $has_warranty = strlen(trim(strip_tags($warranty))) > 0;

  // Warranty tab: show/hide
  $show_warranty_tab = !($atts['hide_warranty_if_empty'] === 'yes' && !$has_warranty);

  // ===== Comparison tab condition (taxonomy keys confirmed) =====
  $brand_tax = 'brand-products';
  $cat_tax   = 'category-products';

  $is_panasonic   = has_term('panasonic', $brand_tax, $post_id);
  $in_allowed_cat = has_term(['body', 'face', 'hair'], $cat_tax, $post_id);

  // IMPORTANT: show comparison tab even if link is empty
  $show_comparison_tab = ($is_panasonic && $in_allowed_cat);

  $uid = 'ptabs_' . $post_id . '_' . wp_rand(100, 999);
  $open_new_tab = ($atts['open_manual_new_tab'] === 'yes') ? '1' : '0';

  ob_start(); ?>
  <div
    class="product-tabs"
    id="<?php echo esc_attr($uid); ?>"
    data-open-manual-new-tab="<?php echo esc_attr($open_new_tab); ?>"
  >
    <div class="product-tabs__nav" role="tablist" aria-label="Product Tabs">
      <button type="button" class="product-tabs__tab is-active" data-tab="specs" role="tab" aria-selected="true">
        คุณสมบัติสินค้า
      </button>

      <button
        type="button"
        class="product-tabs__tab <?php echo $has_manual ? '' : 'is-disabled'; ?>"
        data-tab="manual"
        data-manual-url="<?php echo esc_url($manual_url); ?>"
        role="tab"
        aria-selected="false"
        <?php echo $has_manual ? '' : 'disabled'; ?>
      >
        คู่มือการใช้งานสินค้า
      </button>

      <?php if ($show_comparison_tab): ?>
        <button
          type="button"
          class="product-tabs__tab <?php echo $has_comparison_link ? '' : 'is-disabled'; ?>"
          data-tab="compare"
          data-compare-url="<?php echo esc_url($comparison_url); ?>"
          role="tab"
          aria-selected="false"
          <?php echo $has_comparison_link ? '' : 'disabled'; ?>
        >
          ตารางเปรียบเทียบ
        </button>
      <?php endif; ?>

      <?php if ($show_warranty_tab): ?>
        <button
          type="button"
          class="product-tabs__tab <?php echo $has_warranty ? '' : 'is-disabled'; ?>"
          data-tab="warranty"
          role="tab"
          aria-selected="false"
          <?php echo $has_warranty ? '' : 'disabled'; ?>
        >
          การรับประกันสินค้า
        </button>
      <?php endif; ?>
    </div>

    <div class="product-tabs__panels">
      <!-- Specs -->
      <div class="product-tabs__panel is-active" data-panel="specs" role="tabpanel">
        <?php if ($has_specs): ?>
          <table class="product-tabs__table">
            <thead>
              <tr>
                <th>รายการ</th>
                <th>รายละเอียด</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($spec_rows as $row):
                $label = $row['spec_label'] ?? '';
                $value = $row['spec_value'] ?? '';
              ?>
                <tr>
                  <td class="product-tabs__col-label"><?php echo esc_html($label); ?></td>
                  <td class="product-tabs__col-value"><?php echo wp_kses_post(nl2br($value)); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="product-tabs__empty">ยังไม่มีข้อมูลคุณสมบัติสินค้า</div>
        <?php endif; ?>
      </div>

      <!-- Manual -->
      <div class="product-tabs__panel" data-panel="manual" role="tabpanel">
        <?php if ($has_manual): ?>
          <div class="product-manual">
            <div class="product-manual__title">คู่มือการใช้งานสินค้า</div>
            <div class="product-manual__product"><?php echo esc_html(get_the_title($post_id)); ?></div>

            <div class="product-manual__subtitle">
              <?php
                echo $manual_subtitle
                  ? esc_html($manual_subtitle)
                  : 'ดูวิธีการใช้งานสินค้าอย่างละเอียดจากเว็บไซต์ผู้ผลิต';
              ?>
            </div>

            <a class="product-manual__btn"
              href="<?php echo esc_url($manual_url); ?>"
              <?php echo ($atts['open_manual_new_tab'] === 'yes') ? 'target="_blank" rel="noopener"' : ''; ?>
            >
              ดูคู่มือการใช้งาน
              <img class="product-manual__arrow-icon"
                  src="/wp-content/uploads/2026/02/Arrow-1.webp"
                  alt=""
                  width="16"
                  height="16"
                  loading="lazy"
                  decoding="async">
            </a>
          </div>
        <?php else: ?>
          <div class="product-tabs__empty">ยังไม่มีลิงก์คู่มือ</div>
        <?php endif; ?>
      </div>

      <!-- Comparison (Link or Empty Message) -->
      <?php if ($show_comparison_tab): ?>
        <div class="product-tabs__panel" data-panel="compare" role="tabpanel">
          <?php if ($has_comparison_link): ?>
            <div class="product-manual">
              <div class="product-manual__title">ตารางเปรียบเทียบ</div>
              <div class="product-manual__product"><?php echo esc_html(get_the_title($post_id)); ?></div>

              <div class="product-manual__subtitle">
                ดูรายละเอียดการเปรียบเทียบเพิ่มเติมจากหน้าเปรียบเทียบ
              </div>

              <a class="product-manual__btn"
                href="<?php echo esc_url($comparison_url); ?>"
                <?php echo ($atts['open_manual_new_tab'] === 'yes') ? 'target="_blank" rel="noopener"' : ''; ?>
              >
                ดูตารางเปรียบเทียบ
                <img class="product-manual__arrow-icon"
                    src="/wp-content/uploads/2026/02/Arrow-1.webp"
                    alt=""
                    width="16"
                    height="16"
                    loading="lazy"
                    decoding="async">
              </a>
            </div>
          <?php else: ?>
            <div class="product-tabs__empty">ไม่มีตารางเปรียบเทียบ</div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- Warranty -->
      <?php if ($show_warranty_tab): ?>
        <div class="product-tabs__panel" data-panel="warranty" role="tabpanel">
          <?php if ($has_warranty): ?>
            <div class="product-tabs__content"><?php echo wp_kses_post(wpautop($warranty)); ?></div>
          <?php else: ?>
            <div class="product-tabs__empty">ยังไม่มีข้อมูลการรับประกัน</div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <?php
  return ob_get_clean();
});

// [product_brand_logo size="medium"]
add_shortcode('product_brand_logo', function($atts){

  if (!function_exists('get_field')) return '';

  $atts = shortcode_atts([
    'taxonomy' => 'brand-products',
    'field'    => 'image_brand',
    'size'     => 'medium',
  ], $atts);

  $post_id = get_the_ID();
  if (!$post_id) return '';

  $terms = get_the_terms($post_id, $atts['taxonomy']);
  if (empty($terms) || is_wp_error($terms)) return '';

  $term = $terms[0];
  $acf_key = $atts['taxonomy'].'_'.$term->term_id;
  $img = get_field($atts['field'], $acf_key);

  if (!$img) return '';

  if (is_array($img) && !empty($img['ID'])) {
    return wp_get_attachment_image($img['ID'], $atts['size']);
  }

  if (is_numeric($img)) {
    return wp_get_attachment_image((int)$img, $atts['size']);
  }

  return '<img src="'.esc_url($img).'" alt="'.esc_attr($term->name).'">';
});

/**
 * [product_category_chip_icon]
 * - CPT: products
 * - Taxonomy: category-products
 * - Map icon by term slug (hardcoded URLs)
 *
 * Usage:
 * [product_category_chip_icon]
 */
add_shortcode('product_category_chip_icon', function($atts){

  $atts = shortcode_atts([
    'taxonomy' => 'category-products',
  ], $atts, 'product_category_chip_icon');

  $post_id = get_the_ID();
  if (!$post_id) return '';

  $terms = get_the_terms($post_id, $atts['taxonomy']);
  if (empty($terms) || is_wp_error($terms)) return '';

  $term = $terms[0];
  $slug = $term->slug;

  $icon_map = [
    'face'   => '/wp-content/uploads/2026/01/Face-Icon.webp',
    'body'   => '/wp-content/uploads/2026/01/Body-Icon.webp',
    'beauty' => '/wp-content/uploads/2026/01/Beauty-ICON.webp',
    'men'    => '/wp-content/uploads/2026/01/Men-ICON.webp',
    'hair'   => '/wp-content/uploads/2026/01/Hair-ICON.webp',
  ];

  $icon_url = $icon_map[$slug] ?? '';
  $name = esc_html($term->name);

  $inner = '';
  if ($icon_url) {
    $inner .= '<img class="product-cat-chip__icon" src="'.esc_url($icon_url).'" alt="'.esc_attr($term->name).'" width="18" height="18" loading="lazy" decoding="async" />';
  }
  $inner .= '<span class="product-cat-chip__text">'.$name.'</span>';

  return '<span class="product-cat-chip">'.$inner.'</span>';
});


/**
 * Products Filter Grid + Load More (AJAX)
 * CPT: products
 * Taxonomy: category-products
 *
 * Shortcode:
 * [products_filter_grid]
 */

add_action('wp_enqueue_scripts', function () {

  // โหลดไฟล์หลักของคุณ
  wp_enqueue_script(
    'theme-script',
    get_stylesheet_directory_uri() . '/script.js',
    ['jquery'],
    '1.0.4',
    true
  );

  // localize ให้ script.js ใช้ AJAX ได้
  wp_localize_script('theme-script', 'PFG', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce'    => wp_create_nonce('pfg_nonce'),
  ]);
}, 20);


/** icon map by slug */
if (!function_exists('pfg_category_icon_map')) {
  function pfg_category_icon_map() {
    return [
      'face'   => '/wp-content/uploads/2026/01/Face-Icon.webp',
      'body'   => '/wp-content/uploads/2026/01/Body-Icon.webp',
      'beauty' => '/wp-content/uploads/2026/01/Beauty-ICON.webp',
      'men'    => '/wp-content/uploads/2026/01/Men-ICON.webp',
      'hair'   => '/wp-content/uploads/2026/01/Hair-ICON.webp',
    ];
  }
}

/** count helper (รองรับ 2 arguments ตามที่เรียกใช้) */
if (!function_exists('pfg_get_count')) {
  function pfg_get_count($term_slug = 'all', $taxonomy = 'category-products') {

    $taxonomy  = sanitize_key($taxonomy);
    $term_slug = sanitize_title($term_slug);

    $args = [
      'post_type'      => 'products',
      'post_status'    => 'publish',
      'posts_per_page' => 1,
      'fields'         => 'ids',
      'no_found_rows'  => false,
    ];

    if ($term_slug && $term_slug !== 'all') {
      $args['tax_query'] = [[
        'taxonomy' => $taxonomy,
        'field'    => 'slug',
        'terms'    => $term_slug,
      ]];
    }

    $q = new WP_Query($args);
    return (int) $q->found_posts;
  }
}

/** render one card */
if (!function_exists('pfg_render_card')) {
  function pfg_render_card($post_id) {

    $title = get_the_title($post_id);
    $link  = get_permalink($post_id);

    // Featured image แบบ contain (ไม่ crop)
    $img_id  = get_post_thumbnail_id($post_id);
    $img_url = $img_id ? wp_get_attachment_image_url($img_id, 'large') : '';

    $img_html = $img_url
      ? '<img class="pfg-card__img" src="'.esc_url($img_url).'" alt="'.esc_attr($title).'" loading="lazy" decoding="async">'
      : '<div class="pfg-card__img pfg-card__img--empty"></div>';

    // Brand logo จาก ACF term field: image_brand (taxonomy: brand-products)
    $brand_html = '';
    $brand_tax  = 'brand-products';

    $brand_terms = get_the_terms($post_id, $brand_tax);
    if (!empty($brand_terms) && !is_wp_error($brand_terms) && function_exists('get_field')) {
      $brand   = $brand_terms[0];
      $acf_key = $brand_tax . '_' . $brand->term_id;

      $img = get_field('image_brand', $acf_key);

      if ($img) {
        if (is_array($img) && !empty($img['ID'])) {
          $brand_html = wp_get_attachment_image((int)$img['ID'], 'full', false, [
            'class'    => 'pfg-card__brand-img',
            'loading'  => 'lazy',
            'decoding' => 'async',
          ]);
        } elseif (is_numeric($img)) {
          $brand_html = wp_get_attachment_image((int)$img, 'full', false, [
            'class'    => 'pfg-card__brand-img',
            'loading'  => 'lazy',
            'decoding' => 'async',
          ]);
        } else {
          $brand_html = '<img class="pfg-card__brand-img" src="'.esc_url($img).'" alt="'.esc_attr($brand->name).'" loading="lazy" decoding="async">';
        }
      } else {
        $brand_html = '<div class="pfg-card__brand-text">'.esc_html($brand->name).'</div>';
      }
    }

    // Description from post_content
    $content_raw      = (string) get_post_field('post_content', $post_id);
    $content_rendered = apply_filters('the_content', $content_raw);
    $content_rendered = strip_shortcodes($content_rendered);
    $content_text     = wp_strip_all_tags($content_rendered);
    $content_text     = preg_replace('/\s+/', ' ', $content_text);
    $content_text     = trim($content_text);

    $desc = $content_text;
    if (mb_strlen($desc) > 140) {
      $desc = mb_substr($desc, 0, 140) . '...';
    }

    $desc_html = $desc ? '<div class="pfg-card__desc">'.esc_html($desc).'</div>' : '';

    return '
    <article class="pfg-card">
      <a class="pfg-card__media" href="'.esc_url($link).'">'.$img_html.'</a>
      '.($brand_html ? '<div class="pfg-card__brand">'.$brand_html.'</div>' : '').'
      <h3 class="pfg-card__title"><a href="'.esc_url($link).'">'.esc_html($title).'</a></h3>
      '.$desc_html.'
      <div class="pfg-card__more"><a href="'.esc_url($link).'">ดูเพิ่มเติม</a></div>
    </article>';
  }
}

function pfg_get_count($term = 'all', $taxonomy = 'category-products', $brand = '') {
  $args = [
    'post_type'      => 'products',
    'post_status'    => 'publish',
    'posts_per_page' => 1,
    'fields'         => 'ids',
    'tax_query'      => [
      'relation' => 'AND',
    ],
  ];

  if ($term !== 'all') {
    $args['tax_query'][] = [
      'taxonomy' => $taxonomy,
      'field'    => 'slug',
      'terms'    => $term,
    ];
  }

  if (!empty($brand)) {
    $args['tax_query'][] = [
      'taxonomy' => 'brand',
      'field'    => 'slug',
      'terms'    => $brand,
    ];
  }

  if (count($args['tax_query']) === 1) {
    unset($args['tax_query']);
  }

  $q = new WP_Query($args);
  return (int) $q->found_posts;
}

add_shortcode('products_filter_grid', function($atts){

  $atts = shortcode_atts([
    'taxonomy' => 'category-products',
    'per_page' => 8,
    'default'  => 'all',
    'show_all' => 1,
    'brand'    => '',
  ], $atts, 'products_filter_grid');

  $taxonomy = sanitize_key($atts['taxonomy']);
  $per_page = max(1, (int) $atts['per_page']);
  $default  = sanitize_title($atts['default']);
  $brand    = sanitize_title($atts['brand']);

  $terms = get_terms([
    'taxonomy'   => $taxonomy,
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
  ]);

  $current_label = 'ดูสินค้าทั้งหมด';
  $current_count = pfg_get_count('all', $taxonomy, $brand);

  if ($default !== 'all') {
    $t = get_term_by('slug', $default, $taxonomy);
    if ($t && !is_wp_error($t)) {
      $current_label = $t->name;
      $current_count = pfg_get_count($default, $taxonomy, $brand);
    } else {
      $default = 'all';
    }
  }

  $q_args = [
    'post_type'      => 'products',
    'post_status'    => 'publish',
    'posts_per_page' => $per_page,
    'tax_query'      => [
      'relation' => 'AND',
    ],
  ];

  if ($default !== 'all') {
    $q_args['tax_query'][] = [
      'taxonomy' => $taxonomy,
      'field'    => 'slug',
      'terms'    => $default,
    ];
  }

  if ($brand !== '') {
    $q_args['tax_query'][] = [
      'taxonomy' => 'brand',
      'field'    => 'slug',
      'terms'    => $brand,
    ];
  }

  if (count($q_args['tax_query']) === 1) {
    unset($q_args['tax_query']);
  }

  $q = new WP_Query($q_args);

  $uid      = 'pfg_' . wp_rand(1000,9999);
  $icon_map = pfg_category_icon_map();

  ob_start(); ?>
  <div class="pfg" id="<?php echo esc_attr($uid); ?>"
       data-taxonomy="<?php echo esc_attr($taxonomy); ?>"
       data-per-page="<?php echo esc_attr($per_page); ?>"
       data-current="<?php echo esc_attr($default); ?>"
       data-brand="<?php echo esc_attr($brand); ?>">

    <div class="pfg-filters" role="tablist" aria-label="Category Filters">
      <?php if ((int)$atts['show_all'] === 1): ?>
        <button type="button"
          class="pfg-filter <?php echo $default==='all'?'is-active':''; ?>"
          data-term="all"
          data-label="สินค้าทั้งหมด">
          ดูสินค้าทั้งหมด
        </button>
      <?php endif; ?>

      <?php if (!empty($terms) && !is_wp_error($terms)): ?>
        <?php foreach ($terms as $term):
          $icon = $icon_map[$term->slug] ?? '';
        ?>
          <button type="button"
            class="pfg-filter <?php echo $default===$term->slug?'is-active':''; ?>"
            data-term="<?php echo esc_attr($term->slug); ?>"
            data-label="<?php echo esc_attr($term->name); ?>">
            <?php if ($icon): ?>
              <img class="pfg-filter__icon" src="<?php echo esc_url($icon); ?>" alt="" width="16" height="16" loading="lazy" decoding="async">
            <?php endif; ?>
            <span class="pfg-filter__text"><?php echo esc_html($term->name); ?></span>
          </button>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="pfg-header">
      <div class="pfg-head-left">
        <span class="pfg-title">หมวดหมู่:</span>
        <span class="pfg-current"><?php echo esc_html($current_label); ?></span>
      </div>

      <div class="pfg-head-right">
        <span class="pfg-count-num"><?php echo (int)$current_count; ?></span> รายการ
      </div>
    </div>

    <div class="pfg-grid">
      <?php
      if ($q->have_posts()) {
        while ($q->have_posts()) {
          $q->the_post();
          echo pfg_render_card(get_the_ID());
        }
      } else {
        echo '<div class="pfg-empty">ยังไม่มีสินค้าในหมวดหมู่นี้</div>';
      }
      wp_reset_postdata();
      ?>
    </div>

    <?php
      $shown    = (int) $q->post_count;
      $has_more = $shown < (int) $current_count;
    ?>
    <div class="pfg-actions">
      <button type="button" class="pfg-more" <?php echo $has_more ? '' : 'style="display:none"'; ?>>
        ดูเพิ่มเติม
      </button>
    </div>

  </div>
  <?php
  return ob_get_clean();
});

/** AJAX */
add_action('wp_ajax_pfg_fetch', 'pfg_fetch_products');
add_action('wp_ajax_nopriv_pfg_fetch', 'pfg_fetch_products');

function pfg_fetch_products() {

  check_ajax_referer('pfg_nonce', 'nonce');

  $taxonomy = sanitize_key($_POST['taxonomy'] ?? 'category-products');
  $term     = sanitize_text_field(wp_unslash($_POST['term'] ?? 'all'));
  $term     = sanitize_title($term);
  $brand    = sanitize_title(trim(wp_unslash($_POST['brand'] ?? '')));

  $page     = max(1, (int)($_POST['page'] ?? 1));
  $per_page = max(1, (int)($_POST['per_page'] ?? 8));
  $offset   = ($page - 1) * $per_page;

  if ( ! taxonomy_exists($taxonomy) ) {
    wp_send_json_success([
      'items_html' => '',
      'label'      => '',
      'total'      => 0,
      'shown'      => 0,
      'has_more'   => false,
      'page'       => $page,
    ]);
  }

  $label = 'สินค้าทั้งหมด';

  if ($term && $term !== 'all') {
    $t = get_term_by('slug', $term, $taxonomy);
    if (!$t || is_wp_error($t)) {
      wp_send_json_success([
        'items_html' => '',
        'label'      => '',
        'total'      => 0,
        'shown'      => 0,
        'has_more'   => false,
        'page'       => $page,
      ]);
    }
    $label = $t->name;
  }

  $args = [
    'post_type'           => 'products',
    'post_status'         => 'publish',
    'posts_per_page'      => $per_page,
    'offset'              => $offset,
    'no_found_rows'       => false,
    'ignore_sticky_posts' => true,
    'tax_query'           => [
      'relation' => 'AND',
    ],
  ];

  if ($term && $term !== 'all') {
    $args['tax_query'][] = [
      'taxonomy'         => $taxonomy,
      'field'            => 'slug',
      'terms'            => $term,
      'include_children' => false,
      'operator'         => 'IN',
    ];
  }

  if ($brand !== '') {
    $args['tax_query'][] = [
      'taxonomy' => 'brand',
      'field'    => 'slug',
      'terms'    => $brand,
    ];
  }

  if (count($args['tax_query']) === 1) {
    unset($args['tax_query']);
  }

  $q = new WP_Query($args);

  $items_html = '';
  if ($q->have_posts()) {
    while ($q->have_posts()) {
      $q->the_post();
      $items_html .= pfg_render_card(get_the_ID());
    }
    wp_reset_postdata();
  }

  $total = (int) $q->found_posts;
  $shown = min($offset + (int)$q->post_count, $total);

  wp_send_json_success([
    'items_html' => $items_html,
    'label'      => $label,
    'total'      => $total,
    'shown'      => $shown,
    'has_more'   => $shown < $total,
    'page'       => $page,
  ]);
}