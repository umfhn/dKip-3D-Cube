<?php
/**
 * Plugin Name: DGP 3D Cube 360° (Stabilitäts-Set PRP v0.1)
 * Description: Wartungs-/Stabilitäts-Release-Linie des interaktiven 3D-Würfels zur Darstellung von Bildern und Inhalten. Fokus: robuste 360°-Loop-Navigation, klar definierte Pfeil- und Tastatursteuerung, Doppelpfeil ohne Dead-States, deterministisches Modal/Vollbild-Verhalten und gehärtete Sanitizing-/Security-Pfade. Diese Linie dient der QA/PRP-Verifikation und kann sich von früheren 5.1.15-Builds unterscheiden.
 * Version:     5.1.15-prp.0
 * Author:      dKip
 * Text Domain: dgp-hube
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'DGP_HUBE_VERSION' ) ) {
    define( 'DGP_HUBE_VERSION', '5.1.15-prp.0' );
}

add_action('init', function () {
    $ver  = DGP_HUBE_VERSION;
    $base = plugin_dir_url(__FILE__) . 'build/';

    // Styles direkt enqueuen, um sicherzustellen, dass sie immer geladen werden.
    wp_enqueue_style( 'dgp-hube-style',  $base . 'style.css',  [], $ver );

    wp_register_script(
        'dgp-hube-block',
        $base . 'index.js',
        [ 'wp-blocks','wp-element','wp-i18n','wp-block-editor','wp-editor','wp-components',
          'wp-data','wp-core-data','wp-media-utils' ],
        $ver,
        true
    );

    wp_register_script( 'dgp-hube-view', $base . 'view.js', [], $ver, true );

    register_block_type( __DIR__, [
      'render_callback' => 'dgp_hube_render_cube'
    ]);
});

// Editor-spezifische Assets nur im Editor laden.
add_action('enqueue_block_editor_assets', function() {
    $ver  = DGP_HUBE_VERSION;
    $base = plugin_dir_url(__FILE__) . 'build/';
    // Das editor.css wird hier explizit für den Editor geladen.
    wp_enqueue_style( 'dgp-hube-editor', $base . 'editor.css', ['wp-edit-blocks'], $ver );
});


function dgp_hube_render_cube( $attrs, $content ) {
  $wrapper_attributes = get_block_wrapper_attributes([
    'style'    => dgp_hube_get_inline_styles($attrs),
    'tabindex' => '0',
    'role'     => 'group',
  ]);
  $images = isset($attrs['images']) && is_array($attrs['images']) ? $attrs['images'] : [];
  $images = array_slice($images, 0, 6);
  $uid = uniqid('dgp', false);

  wp_enqueue_style('dgp-hube-style');
  wp_enqueue_script('dgp-hube-view');

  // Prepare data attributes
  $wrap_mode_attr = isset($attrs['wrapMode']) ? strtolower((string)$attrs['wrapMode']) : 'off';
  $allowed_wrap_modes = ['off', 'ring', 'orbit', 'hybrid'];
  if (!in_array($wrap_mode_attr, $allowed_wrap_modes, true)) {
    $wrap_mode_attr = 'off';
  }
  $vertical_swipe_enabled = !array_key_exists('verticalSwipe', $attrs) || !empty($attrs['verticalSwipe']);
  $vertical_toggle_enabled = !array_key_exists('verticalToggle', $attrs) || !empty($attrs['verticalToggle']);
  $arrow_mode_attr = isset($attrs['arrowMode']) ? strtolower((string)$attrs['arrowMode']) : 'orthogonal';
  if ($arrow_mode_attr !== 'linear6') {
    $arrow_mode_attr = 'orthogonal';
  }

  // Alle Daten-Attribute zentral gesammelt (CSP-/Defer-freundlich, keine Inline-Skripte nötig).
  $data_attrs = [ // Default values can be set in block.json for clarity
    'data-aspect'            => '3:4',
    'data-show-nav'          => !empty($attrs['showNavigation']) ? '1' : '0',
    'data-nav-label'         => esc_attr($attrs['navLabel'] ?? 'Leistung'),
    'data-cube-scale'        => esc_attr($attrs['cubeScale'] ?? '0.88'),
    'data-show-action'       => ($attrs['showActionBtn'] ?? true) ? '1' : '0',
    'data-show-audio'        => !empty($attrs['showAudioBtn']) ? '1' : '0',
    'data-show-zoom'         => ($attrs['showZoomBtn'] ?? true) ? '1' : '0',
    'data-action-label'      => esc_attr($attrs['actionBtnLabel'] ?? 'Mehr Info'),
    'data-audio-label'       => esc_attr($attrs['audioBtnLabel'] ?? 'Audio'),
    'data-zoom-label'        => esc_attr($attrs['zoomBtnLabel'] ?? 'Vergrößern'),
    'data-audio-url'         => esc_url($attrs['audioUrl'] ?? ''),
    'data-audio-mode'        => esc_attr($attrs['audioMode'] ?? 'auto'),
    'data-audio-autoplay'    => !empty($attrs['audioAutoplayOnChange']) ? '1' : '0',
    'data-fullscreen-mode'   => !empty($attrs['fullscreenMode']) ? '1' : '0',
    'data-show-fullscreen'   => ($attrs['showFullscreenBtn'] ?? true) ? '1' : '0',
    'data-fullscreen-label'  => esc_attr($attrs['fullscreenBtnLabel'] ?? 'Vollbild'),
    'data-ctrl-style'        => esc_attr($attrs['ctrlStyle'] ?? 'round'),
    'data-intro'             => ($attrs['introAnim'] ?? true) ? '1' : '0',
    'data-intro-tilt'        => esc_attr($attrs['introTilt'] ?? '12'),
    'data-intro-dur'         => esc_attr($attrs['introDuration'] ?? '800'),
    'data-shadows'           => ($attrs['shadowsEnabled'] ?? true) ? '1' : '0',
    'data-shadow-preset'     => esc_attr($attrs['shadowPreset'] ?? 'soft'),
    'data-modal-anim'        => ($attrs['modalAnim'] ?? true) ? '1' : '0',
    'data-modal-position'    => esc_attr($attrs['modalPosition'] ?? 'top-center'),
    'data-snap-style'        => esc_attr($attrs['snapStyle'] ?? 'direct'),
    'data-content-sync'      => esc_attr($attrs['contentSyncMode'] ?? 'direct'),
    'data-axis-freeze'       => array_key_exists('axisFreeze', $attrs) ? (($attrs['axisFreeze']) ? '1' : '0') : '1',
    'data-wrap-mode'         => esc_attr($wrap_mode_attr),
    'data-vertical-swipe'    => $vertical_swipe_enabled ? '1' : '0',
    'data-vertical-toggle'   => $vertical_toggle_enabled ? '1' : '0',
    'data-arrow-mode'        => esc_attr($arrow_mode_attr),
    'data-debug'             => is_admin() ? '1' : '0',
    'data-uid'               => $uid,
  ];

  $attr_string = array_reduce(array_keys($data_attrs), function ($carry, $key) use ($data_attrs) {
    return $carry . sprintf(' %s="%s"', $key, $data_attrs[$key]);
  }, '');

  ob_start();
  ?>
  <div <?php echo $wrapper_attributes; ?> <?php echo $attr_string; ?>>
    <div class="dgp-cube-canvas"><div class="dgp-cube-scene"><div class="dgp-cube"></div></div></div>
    <div class="dgp-cube-nav" aria-hidden="<?php echo !empty($attrs['showNavigation']) ? 'false':'true'; ?>"></div>
    <div class="dgp-cube-a11y" aria-live="polite"></div>
    <script type="application/json" class="dgp-images"><?php echo wp_json_encode($images, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
    <div class="dgp-modal-pool" hidden>
      <?php foreach ($images as $idx => $img):
        $content_html = '';
        $scoped_css   = '';
        $action = $img['action'] ?? '';
        $should_render_inline = in_array($action, ['inline', 'modal', 'shortcode'], true);
        $scope_id = dgp_hube_normalize_scope_id('dgp-hube-' . $uid . '-' . $idx);
        $inline_css_input = isset($img['inlineCss']) ? (string) $img['inlineCss'] : '';

        if ($should_render_inline) {
            $mode = $img['inlineMode'] ?? '';
            $raw  = $img['inlineContent'] ?? '';

            // Legacy fallback: support older modal/shortcode data fields.
            if ($raw === '' && !empty($img['modalHtml'])) {
                $raw = $img['modalHtml'];
                $mode = $mode ?: 'html';
            }
            if ($raw === '' && !empty($img['shortcode'])) {
                $raw = $img['shortcode'];
                $mode = 'shortcode';
            }
            if ($mode === '' || !in_array($mode, ['text', 'html', 'shortcode', 'video', 'pdf', 'image', 'raw_html'], true)) {
                $mode = 'text';
            }

            // Shortcodes in user-provided HTML can be a security risk.
            // Only process shortcodes if the user has the 'unfiltered_html' capability.
            if ($mode === 'shortcode') {
                if (current_user_can('unfiltered_html')) {
                    $content_html = do_shortcode($raw);
                } else {
                    $content_html = wpautop(esc_html($raw));
                }
            } elseif ($mode === 'html' || $mode === 'raw_html') {
                $sanitized = dgp_hube_sanitize_modal_html($raw, $scope_id, $inline_css_input);
                if (is_array($sanitized)) {
                  $content_html = $sanitized['html'];
                  $scoped_css   = $sanitized['css'];
                } else {
                  // Legacy return value (string) support
                  $content_html = $sanitized;
                }
            } elseif ($mode === 'video') {
                $video_url = esc_url($raw);
                $embed_url = '';
                if (preg_match('/(youtube\.com|youtu\.be)\/(watch\?v=|embed\/|)([a-zA-Z0-9_-]{11})/', $video_url, $matches)) {
                    $embed_url = 'https://www.youtube.com/embed/' . $matches[3];
                } elseif (preg_match('/vimeo\.com\/([0-9]+)/', $video_url, $matches)) {
                    $embed_url = 'https://player.vimeo.com/video/' . $matches[1];
                }
                if ($embed_url) {
                    $content_html = '<div class="dgp-responsive-embed"><iframe src="' . $embed_url . '" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe></div>';
                } else {
                    // Fallback für lokale Videodateien
                    $content_html = '<div class="dgp-media-centered"><video src="' . $video_url . '" controls style="max-width:100%;height:auto;"></video></div>';
                }
            } elseif ($mode === 'pdf' || $mode === 'image') {
                if ($mode === 'pdf') {
                    $content_html = '<div class="dgp-media-centered" style="height: 70vh;"><embed src="' . esc_url($raw) . '" type="application/pdf" width="100%" height="100%"></div>';
                } else {
                    $content_html = '<div class="dgp-media-centered"><img src="' . esc_url($raw) . '" alt="' . esc_attr($img['label'] ?? 'Vorschaubild') . '" style="max-width:100%;height:auto;border-radius: 6px;"></div>';
                }
            } else { // 'text' mode
                $content_html = wpautop(esc_html($raw));
            }
        }
      ?>
        <div id="dgp-inline-<?php echo esc_attr($uid); ?>-<?php echo esc_attr($idx); ?>" class="dgp-inline-frag">
          <div class="dgp-modal-content-inner" data-inline-scope="<?php echo esc_attr($scope_id); ?>">
            <?php if ($scoped_css !== '') : ?>
              <div class="dgp-inline-css" data-inline-scope="<?php echo esc_attr($scope_id); ?>" hidden><?php echo esc_html($scoped_css); ?></div>
            <?php endif; ?>
            <div class="dgp-inline-scope" data-inline-scope="<?php echo esc_attr($scope_id); ?>"><?php echo $content_html; ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php
  return ob_get_clean();
}

function dgp_hube_get_inline_styles( $attrs ) {
  $styles = '';
  $map = [
    'surfaceColor' => '--dgp-surface',
    'textColor' => '--dgp-text',
    'canvasRadius' => '--dgp-radius-canvas',
    'navRadius' => '--dgp-radius-nav',
    'pillRadius' => '--dgp-radius-pill',
    'radiusLightbox' => '--dgp-radius-lightbox',
    'borderWidthCanvas' => '--dgp-border-w-canvas',
    'borderWidthArrow' => '--dgp-border-w-arrow',
    'borderWidthCtrl' => '--dgp-border-w-ctrl',
    'titleSize' => '--dgp-title-size',
    'titleTransform' => '--dgp-title-transform',
    'btnSize' => '--dgp-btn-size',
    'fontFamily' => '--dgp-font-family',
    'modalMaxW' => '--dgp-modal-maxw',
    'modalPadding' => '--dgp-modal-padding',
    'modalRadius' => '--dgp-modal-radius',
    'modalBgColor' => '--dgp-modal-bg',
    'modalTextColor' => '--dgp-modal-text',
    'modalLinkColor' => '--dgp-modal-link',
    // Pfeile
    'lightboxBgColor' => '--dgp-backdrop',
    'arrowBgColor' => '--dgp-arrow-bg',
    'arrowBgHoverColor' => '--dgp-arrow-bg-hover',
    'arrowBorderColor' => '--dgp-arrow-border',
    'arrowIconColor' => '--dgp-arrow-icon',
    // Dots
    'dotBorderColor' => '--dgp-dot-border',
    'dotActiveBgColor' => '--dgp-dot-active-bg',
    'dotFocusRingColor' => '--dgp-dot-ring',
    // Steuer-Buttons
    'ctrlBgColor' => '--dgp-ctrl-bg',
    'ctrlBgHoverColor' => '--dgp-ctrl-bg-hover',
    'ctrlBorderColor' => '--dgp-ctrl-border',
    'ctrlTextColor' => '--dgp-ctrl-text',
    'ctrlHotColor' => '--dgp-ctrl-hot',
  ];

  foreach ($map as $attr_key => $var_name) {
    if (isset($attrs[$attr_key])) {
      $value = $attrs[$attr_key];
      // Sanitize value to prevent CSS injection
      $value = preg_replace('/[^\w\.\-%#\s,()\/]/', '', $value);
      if (empty($value) && $value !== '0') continue;

      $unit = (strpos($attr_key, 'Radius') !== false || strpos($attr_key, 'Width') !== false || strpos($attr_key, 'Padding') !== false || strpos($attr_key, 'MaxW') !== false) ? 'px' : (strpos($attr_key, 'Size') !== false ? 'rem' : '');
      $styles .= "{$var_name}: {$value}{$unit};";
    }
  }
  return $styles;
}

/**
 * Sanitize rich HTML content destined for the inline modal.
 *
 * @param string $raw      Raw user-provided HTML.
 * @param string $scope_id Scope identifier used to namespace CSS.
 * @param string $extra_css Optional raw CSS from dedicated field (no <style> tag required).
 * @return array {
 *   @type string $html Sanitized HTML markup ready for output.
 *   @type string $css  Scoped CSS rules to apply.
 * }
 */
function dgp_hube_sanitize_modal_html( $raw, $scope_id = '', $extra_css = '' ) {
  $html = trim( (string) $raw );

  if ( $html === '' ) {
    return [
      'html' => '',
      'css'  => '',
    ];
  }

  $scope_id = dgp_hube_normalize_scope_id( $scope_id );

  // Extract inline styles for separate processing.
  $style_blocks = [];
  if ( preg_match_all( '/<style\b[^>]*>(.*?)<\/style>/is', $html, $style_matches ) ) {
    foreach ( $style_matches[1] as $css_block ) {
      $style_blocks[] = $css_block;
    }
  }

  if ( $extra_css !== '' ) {
    $extra_css = trim( (string) $extra_css );
    if ( $extra_css !== '' ) {
      // Strip a ggf. vorhandenen <style>-Wrapper aus dem Eingabefeld.
      $extra_css = preg_replace( '/<\/?style[^>]*>/i', '', $extra_css );
      $extra_css = trim( $extra_css );
      if ( $extra_css !== '' ) {
        $style_blocks[] = $extra_css;
      }
    }
  }

  // Strip DOCTYPE and html/head wrappers if present to avoid modal duplication.
  $html = preg_replace( '/^\s*<!DOCTYPE[^>]*>/i', '', $html );

  if ( stripos( $html, '<body' ) !== false && preg_match( '/<body[^>]*>(.*?)<\/body>/is', $html, $matches ) ) {
    $html = $matches[1];
  } else {
    // Remove wrapping html/head tags if supplied without body.
    $html = preg_replace( '/<\/?(?:html|head)[^>]*>/i', '', $html );
  }

  // Remove original <style> tags from markup content.
  $html = preg_replace( '/<style\b[^>]*>.*?<\/style>/is', '', $html );
  $html = trim( $html );

  $allowed = dgp_hube_get_modal_allowed_tags();

  $scoped_css = '';

  if ( ! empty( $style_blocks ) ) {
    $scoped_css = dgp_hube_scope_modal_css( implode( "\n", $style_blocks ), $scope_id );
  }

  $sanitized_html = wp_kses( $html, $allowed );

  return [
    'html' => $sanitized_html,
    'css'  => $scoped_css,
  ];
}

/**
 * Build the allow-list for modal HTML, extending the default post context
 * to include style + SVG related tags required by the builder UI.
 *
 * @return array
 */
function dgp_hube_get_modal_allowed_tags() {
  $allowed = wp_kses_allowed_html( 'post' );

  // SVG support (icons inside the card).
  $svg_common_attrs = [
    'class'            => true,
    'xmlns'            => true,
    'fill'             => true,
    'stroke'           => true,
    'stroke-width'     => true,
    'stroke-linecap'   => true,
    'stroke-linejoin'  => true,
    'stroke-dasharray' => true,
    'stroke-dashoffset'=> true,
    'viewBox'          => true,
    'width'            => true,
    'height'           => true,
    'aria-hidden'      => true,
    'focusable'        => true,
    'role'             => true,
    'preserveAspectRatio' => true,
  ];

  $allowed['svg'] = array_merge(
    $svg_common_attrs,
    [
      'version'          => true,
      'xmlns:xlink'      => true,
    ]
  );

  $allowed['path'] = [
    'd'               => true,
    'fill'            => true,
    'stroke'          => true,
    'stroke-width'    => true,
    'stroke-linecap'  => true,
    'stroke-linejoin' => true,
    'transform'       => true,
  ];

  $allowed['g'] = [
    'fill'            => true,
    'stroke'          => true,
    'stroke-width'    => true,
    'stroke-linecap'  => true,
    'stroke-linejoin' => true,
    'transform'       => true,
    'class'           => true,
  ];

  $allowed['circle'] = [
    'cx'              => true,
    'cy'              => true,
    'r'               => true,
    'fill'            => true,
    'stroke'          => true,
    'stroke-width'    => true,
  ];

  $allowed['rect'] = [
    'x'               => true,
    'y'               => true,
    'width'           => true,
    'height'          => true,
    'rx'              => true,
    'ry'              => true,
    'fill'            => true,
    'stroke'          => true,
    'stroke-width'    => true,
  ];

  $allowed['line'] = [
    'x1'              => true,
    'y1'              => true,
    'x2'              => true,
    'y2'              => true,
    'stroke'          => true,
    'stroke-width'    => true,
    'stroke-linecap'  => true,
  ];

  $allowed['polyline'] = [
    'points'          => true,
    'fill'            => true,
    'stroke'          => true,
    'stroke-width'    => true,
    'stroke-linecap'  => true,
    'stroke-linejoin' => true,
  ];

  $allowed['polygon'] = [
    'points'          => true,
    'fill'            => true,
    'stroke'          => true,
    'stroke-width'    => true,
    'stroke-linecap'  => true,
    'stroke-linejoin' => true,
  ];

  if ( ! isset( $allowed['span'] ) ) {
    $allowed['span'] = [];
  }
  if ( ! isset( $allowed['div'] ) ) {
    $allowed['div'] = [];
  }
  $allowed['div']['hidden'] = true;
  if ( ! isset( $allowed['a'] ) ) {
    $allowed['a'] = [];
  }

  $allowed['style'] = [
    'type'             => true,
    'media'            => true,
    'data-inline-scope'=> true,
  ];

  $allowed['span']['style'] = true;
  $allowed['div']['style']  = true;
  $allowed['a']['rel']      = true;
  $allowed['a']['target']   = true;

  return $allowed;
}

/**
 * Apply a simple scope to user-supplied CSS so it only affects the modal content.
 *
 * @param string $css_raw  CSS string extracted from <style> blocks.
 * @param string $scope_id Normalized scope identifier.
 * @return string Scoped CSS rules.
 */
function dgp_hube_scope_modal_css( $css_raw, $scope_id ) {
  $css = trim( (string) $css_raw );
  if ( $css === '' ) {
    return '';
  }

  $scope_id = dgp_hube_normalize_scope_id( $scope_id );
  $scope_selector = $scope_id ? '[data-inline-scope="' . $scope_id . '"]' : '.dgp-inline-scope';

  // Strip HTML tags, comments, and @import rules for safety.
  $css = strip_tags( $css );
  $css = preg_replace( '/\/\*.*?\*\//s', '', $css );
  $css = preg_replace( '/@import[^;]*;?/i', '', $css );

  // Prefix selectors with the modal scope.
  $css = preg_replace_callback(
    '/(^|[{}])\s*([^{}@]+)\s*{/', // Ignore at-rules such as @media/@keyframes at this stage.
    function ( $matches ) use ( $scope_selector ) {
      $prefix = $matches[1];
      $selectors = explode( ',', $matches[2] );
      $scoped = [];

      foreach ( $selectors as $selector ) {
        $sel = trim( $selector );
        if ( $sel === '' ) {
          continue;
        }

        // Skip keyframe percentage selectors.
        if ( preg_match( '/^(?:from|to|\d+(?:\.\d+)?%)$/i', $sel ) ) {
          $scoped[] = $sel;
          continue;
        }

        // Replace global anchors with scoped selector.
        $sel = preg_replace( '/^\s*(?:html|body)\b/i', $scope_selector, $sel );
        $sel = preg_replace( '/(^|\s):root\b/i', '$1' . $scope_selector, $sel );

        if ( strpos( $sel, $scope_selector ) === false ) {
          $sel = $scope_selector . ' ' . $sel;
        }

        $scoped[] = $sel;
      }

      if ( empty( $scoped ) ) {
        return $matches[0];
      }

      return $prefix . ' ' . implode( ', ', $scoped ) . ' {';
    },
    $css
  );

  return trim( $css );
}

/**
 * Normalize scope identifier used in data attributes for inline modal content.
 *
 * @param string $scope_id Raw scope identifier.
 * @return string Normalized identifier containing only safe characters.
 */
function dgp_hube_normalize_scope_id( $scope_id ) {
  $scope_id = (string) $scope_id;
  $scope_id = preg_replace( '/[^a-zA-Z0-9_-]/', '-', $scope_id );
  return $scope_id;
}
