<?php
/**
 * @file
 * Contains theme override functions and preprocess functions for Deco theme.
 */

/**
 * Implements hook_preprocess_html().
 */
function deco_preprocess_html(&$variables) {
  // Block admin page.
  if (arg(2) == 'block' && arg(3) == FALSE) {
    _deco_alert_layout($variables);
    $variables['classes_array'][] = 'block-admin';
  }
  else {
    // If the right sidebar is empty and the secondary right sidebar has content
    // we move the secondary right sidebar content over to the right sidebar.
    if (! empty($variables['page']['sidebar_right_sec']) && empty($variables['page']['sidebar_second'])) {
      $variables['page']['sidebar_second'] = $variables['page']['sidebar_right_sec'];
      $variables['page']['sidebar_right_sec'] = '';
    }

    // Add a 'sidebar-triple' class to the body for pages with three columns.
    if (! empty($variables['page']['sidebar_second']) && ! empty($variables['page']['sidebar_right_sec']) && ! empty($variables['page']['sidebar_first'])) {
      $variables['classes_array'][] = 'sidebar-triple';
    }
    // Add a 'sidebar-double' class to the body for pages with a column on the
    // left and a column on the right.
    elseif (! empty($variables['page']['sidebar_first']) && ! empty($variables['page']['sidebar_second'])) {
      $variables['classes_array'][] = 'sidebar-double';
    }
    // Add a 'sidebar-right-double' class to the body for pages with two right
    // columns.
    elseif (! empty($variables['page']['sidebar_second']) && ! empty($variables['page']['sidebar_right_sec'])) {
      $variables['classes_array'][] = 'sidebar-right-double';
    }
    // Add a 'sidebar-left' class to the body for pages with a single column
    // where the column is present on the left side.
    elseif (! empty($variables['page']['sidebar_first'])) {
      $variables['classes_array'][] = 'sidebar-left';
    }
    // Add a 'sidebar-right' class to the body for pages with a single column.
    // The column is present on the right side in either the sidebar_second
    // region or the sidebar_right_sec region.
    elseif (!empty($variables['page']['sidebar_second']) || !empty($variables['page']['sidebar_right_sec'])) {
      $variables['classes_array'][] = 'sidebar-right';
    }

    // Add a 'rightbar' class to the body for pages with a single right column.
    // The right column is present in the sidebar_second region.
    if (! empty($variables['page']['sidebar_second'])) {
      $variables['classes_array'][] = 'rightbar';
    }
  }
}

/**
 * Implements hook_preprocess_page().
 */
function deco_preprocess_page(&$vars) {
  $vars['sidebar_triple'] = FALSE;
  if (!empty($vars['page']['sidebar_second']) && !empty($vars['page']['sidebar_right_sec']) && !empty($vars['page']['sidebar_first'])) {
    $vars['classes_array'][] .= ' sidebar-triple';
    $vars['sidebar_triple'] = TRUE;
  }
  if (!empty($vars['page']['sidebar_right_sec']) && empty($vars['page']['sidebar_second'])) {
    $vars['page']['sidebar_second'] = $vars['page']['sidebar_right_sec'];
    $vars['page']['sidebar_right_sec'] = '';
  }

  // set variables for the logo and slogan
  $site_fields = array();
  if ($vars['site_name']) {
    $site_fields[] = check_plain($vars['site_name']);
  }
  if ($vars['site_slogan']) {
    $site_fields[] = '- ' . check_plain($vars['site_slogan']);
  }

  $vars['site_title'] = implode(' ', $site_fields);

  if (isset($site_fields[0])) {
    $site_fields[0] = '<span class="site-name">' . $site_fields[0] . '</span>';
  }
  if (isset($site_fields[1])) {
    $site_fields[1] = '<span class="site-slogan">' . $site_fields[1] . '</span>';
  }
  $vars['site_title_html'] = implode(' ', $site_fields);

  $main_menu_tree = menu_tree(variable_get('menu_main_links_source', 'main-menu'));
  $vars['primary_menu'] = str_replace('class="menu"', 'class="links primary-links"', render($main_menu_tree));
  $secondary_menu_tree = menu_tree(variable_get('menu_secondary_links_source', 'secondary-menu'));
  $vars['secondary_menu'] = str_replace('class="menu"', 'class="links secondary-links"', render($secondary_menu_tree));
}

/**
 * Implements hook_preprocess_block().
 */
function deco_preprocess_block(&$variables) {
  // Add a CSS class 'title' to the title attributes.
  $variables['title_attributes_array']['class'] = 'title';
  // Add a CSS class 'block-title' to blocks with a title.
  if (! empty($variables['block']->subject)) {
    $variables['classes_array'][] = 'block-title';
  }
  // Add a CSS class 'odd' or 'even' to the block.
  $variables['classes_array'][] = $variables['zebra'];
}

/**
 * Alerts the user when the layout is changed based on the used regions.
 *
 * @param $regions
 *   An associative array containing the regions.
 */
function _deco_alert_layout($regions) {
  if (user_access('administer blocks')) {
    // remove the block indicators first
    $sidebars = array(
      'sidebar_right_sec' => $regions['page']['content']['system_main']['block_regions']['#value']['sidebar_right_sec'],
      'sidebar_second' => $regions['page']['content']['system_main']['block_regions']['#value']['sidebar_second'],
      'sidebar_first' => $regions['page']['content']['system_main']['block_regions']['#value']['sidebar_first']
    );
    foreach ($sidebars as $k => $v) {
      $sidebars[$k] = preg_replace('/(\<div class="block-region"\>)(.*)(\<\/div\>)/', '', $v);
    }

    // warn the user that the secondary right sidebar will look like a regular right sidebar
    if ($sidebars['sidebar_right_sec'] && empty($sidebars['sidebar_right'])) {
      drupal_set_message(t('Warning: if you add blocks to the <em>secondary right sidebar</em> and leave the <em>right sidebar</em> empty, the <em>secondary right
			sidebar</em> will be rendered as a regular <em>right sidebar</em>.'));
    }
    // warn the user that the three sidebars will look like three equal columns
    elseif ($sidebars['sidebar_right'] && $sidebars['sidebar_right_sec'] && $sidebars['sidebar_first']) {
      drupal_set_message(t('Warning: if you add blocks to all three sidebars they will be rendered as three equal columns above the content.'));
    }
  }
}

/**
 * Generates HTML for the content area.
 *
 * Prevents duplication in page.tpl.php.
 *
 * @param Array $tabs
 *   Array of tabs linking to any sub-pages beneath the current page.
 * @param String $title
 *   The page title.
 * @param String $messages
 *   HTML for status and error messages.
 * @param String $classes
 *   CSS classes.
 *
 * @return String
 *   HTML for the main content area.
 */
function deco_render_content($tabs, $title, $messages, $classes) {
  $output = ! empty($title) ? '<h2 class="content-title">' . $title . '</h2>' : '';
  $primary_tabs = menu_primary_local_tasks();
  $output .= $primary_tabs ? deco_menu_local_tasks('<ul class="tabs primary">' . drupal_render($primary_tabs) . '</ul>') : '';
  $secondary_tabs = menu_secondary_local_tasks();
  $output .= $secondary_tabs ? deco_menu_secondary_local_tasks('<ul class="tabs secondary">' . drupal_render($secondary_tabs) . '</ul>') : '';
  $output .= $messages ? $messages : '';
  return $output;
}

/**
 * Returns HTML for a fieldset form element and its children.
 *
 * Adds HTML hooks for advanced styling.
 *
 * @param $variables
 *   An associative array containing:
 *     - element  An associative array containing the properties of the element.
 *       Properties used: #attributes, #children, #collapsed, #collapsible,
 *       #description, #id, #title, #value.
 *
 * @return String
 *   HTML for a fieldset form element and its children.
 */
function deco_fieldset($variables) {
  $element = $variables['element'];
  element_set_attributes($element, array('id'));
  _form_set_class($element, array('form-wrapper'));

  $output = '<fieldset' . drupal_attributes($element['#attributes']) . '>';
  if (!empty($element['#title'])) {
    // Always wrap fieldset legends in a SPAN for CSS positioning.
    $output .= '<legend><span class="fieldset-legend">' . $element['#title'] . '</span></legend>';
  }
  $output .= '<div class="fieldset-wrapper"><div class="top"><div class="bottom"><div class="bottom-ornament">';
  if (!empty($element['#description'])) {
    $output .= '<div class="fieldset-description">' . $element['#description'] . '</div>';
  }
  $output .= $element['#children'];
  if (isset($element['#value'])) {
    $output .= $element['#value'];
  }
  $output .= '</div></div></div></div>';
  $output .= "</fieldset>\n";

  return $output;
}

/**
 * Returns HTML for primary and secondary local tasks.
 *
 * @see theme_menu_local_tasks().
 *
 * @ingroup themeable
 */
function deco_menu_local_tasks($tasks = '') {
  if (!empty($tasks)) {
    return '<div class="content-bar clear-block"><div class="left">' . $tasks . '</div></div>';
  }
  return '';
}

/**
 * Returns HTML for secondary local tasks.
 *
 * @see theme_menu_local_tasks().
 *
 * @ingroup themeable
 */
function deco_menu_secondary_local_tasks($tasks = '') {
  $output = '';
  if (!empty($tasks)) {
    $output = "\n<div class=\"content-bar-indented\"><div class=\"content-bar clear-block\"><div class=\"left\">\n" . $tasks . "\n</div></div></div>\n";
  }

  return $output;
}

/**
 * Returns HTML for a breadcrumb trail.
 *
 * @param Array $variables
 *   An associative array containing:
 *     -breadcrumb: An array containing the breadcrumb links.
 *
 * @return String
 *   HTML representing a breadcrumb trail.
 *
 * @see theme_breadcrumb().
 *
 * @ingroup themable.
 */
function deco_breadcrumb($variables) {
  $breadcrumb = $variables['breadcrumb'];
  $output = "";

  if (!empty($breadcrumb)) {
    // Provide a navigational heading to give context for breadcrumb links to
    // screen-reader users. Make the heading invisible with .element-invisible.

    $output .= '<div class="breadcrumb">' . implode(' » ', $breadcrumb) . '</div>';
    return $output;
  }
}

/**
 * Returns HTML for a query pager.
 *
 * Adds HTML hooks for making the pager appear in a horizontal bar.
 *
 * @param Array $variables
 *   An associative array containing:
 *     - tags: An array of labels for the controls in the pager.
 *     - element: An optional integer to distinguish between multiple pagers on
 *       one page.
 *     - parameters: An associative array of query string parameters to append
 *       to the pager links.
 *     - quantity: The number of pages in the list.
 *
 * @return String
 *   HTML for a query pager.
 *
 * @see theme_pager().
 *
 * @group themable.
 */
function deco_pager($variables) {
  $output = theme_pager($variables);
  if (!empty($variables)) {
    $output = '<div class="content-bar"><div class="left">' . $output . '</div></div>';
  }
  return $output;
}

/**
 * Implements theme_field__field_type().
 *
 * Adds HTML hooks for making the terms appear in a horizontal bar.
 */
function deco_field__taxonomy_term_reference($variables) {
  $output = '';
  // Render the items.
  $output .= '<div class= terms>';
  $output .= ( $variables['element']['#label_display'] == 'inline') ? '<ul class="links inline">' : '<ul class="links inline">';
  foreach ($variables['items'] as $delta => $item) {
    $output .= '<li class="taxonomy-term-' . $delta . '"' . $variables['item_attributes'][$delta] . '>' . drupal_render($item) . '</li>';
  }
  $output .= '</ul>';
  // Render the top-level DIV.
  $output = '<div class="' . $variables['classes'] . (!in_array('clearfix', $variables['classes_array']) ? ' clearfix' : '') . '">' . $output . '</div></div>';
  return $output;
}
