<?php
use OSC\OM\HTML;
use OSC\OM\OSCOM;
use OSC\OM\Registry;

$OSCOM_Braintree = Registry::get('Braintree');

$Qtokens = $OSCOM_Braintree->db->get('customers_braintree_tokens', [
    'id',
    'card_type',
    'number_filtered',
    'expiry_date'
], [
    'customers_id' => $_SESSION['customer_id']
], 'date_added');
?>

<div class="page-header">
  <h1><?= $OSCOM_Braintree->getDef('module_content_account_heading_title'); ?></h1>
</div>

<?php
if ($messageStack->size('cards') > 0) {
    echo $messageStack->output('cards');
}
?>

<div class="contentContainer">
  <div class="contentText">
    <?= $OSCOM_Braintree->getDef('module_content_account_text_description'); ?>

<?php
if ($Qtokens->fetch() !== false) {
    do {
?>

    <div class="row">
      <div class="col-xs-12">
        <span style="float: right;"><?= HTML::button(SMALL_IMAGE_BUTTON_DELETE, 'fa fa-trash', OSCOM::link('index.php', 'account&stored-cards&action=delete&id=' . $Qtokens->valueInt('id') . '&formid=' . md5($_SESSION['sessiontoken']), 'SSL')); ?></span>
        <p><strong><?= $Qtokens->valueProtected('card_type'); ?></strong>&nbsp;&nbsp;****<?= $Qtokens->valueProtected('number_filtered') . '&nbsp;&nbsp;' . HTML::outputProtected(substr($Qtokens->value('expiry_date'), 0, 2) . '/' . substr($Qtokens->value('expiry_date'), 2)); ?></p>
      </div>
    </div>

<?php
    } while ($Qtokens->fetch());
  } else {
?>

    <div style="background-color: #FEEFB3; border: 1px solid #9F6000; margin: 10px 0px; padding: 5px 10px; border-radius: 10px;">
      <?= $OSCOM_Braintree->getDef('module_content_account_no_cards'); ?>
    </div>

<?php
  }
?>

  </div>

  <div class="buttonSet">
    <?= HTML::button(IMAGE_BUTTON_BACK, 'fa fa-angle-left', OSCOM::link('account.php', '', 'SSL')); ?>
  </div>
</div>
