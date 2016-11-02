<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  include(DIR_FS_ADMIN . 'includes/languages/' . $language . '/modules/boxes/braintree.php');
  include(DIR_FS_CATALOG . 'includes/apps/braintree/admin/functions/boxes.php');
?>
<!-- braintree //-->
          <tr>
            <td>
<?php
  $heading = array();
  $contents = array();

  $heading[] = array('text'  => MODULES_ADMIN_MENU_BRAINTREE_HEADING,
                     'link'  => tep_href_link('braintree.php', 'selected_box=braintree'));

  if ($selected_box == 'braintree') {
    $bt_menu = array();

    foreach ( app_braintree_get_admin_box_links() as $bt ) {
      $bt_menu[] = '<a href="' . $bt['link'] . '" class="menuBoxContentLink">' . $bt['title'] . '</a>';
    }

    $contents[] = array('text'  => implode('<br>', $bt_menu));
  }

  $box = new box;
  echo $box->menuBox($heading, $contents);
?>
            </td>
          </tr>
<!-- braintree_eof //-->
