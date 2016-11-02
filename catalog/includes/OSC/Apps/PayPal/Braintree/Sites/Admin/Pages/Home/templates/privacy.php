<?php
use OSC\OM\OSCOM;
use OSC\OM\Registry;

$OSCOM_Braintree = Registry::get('Braintree');
?>

<div class="row" style="padding-bottom: 30px;">
  <div class="col-sm-6">
    <a href="<?= $OSCOM_Braintree->link(); ?>"><img src="<?= OSCOM::link('Shop/public/Apps/PayPal/Braintree/images/braintree.png', '', false); ?>" /></a>
  </div>

  <div class="col-sm-6 text-right text-muted">
    <?= $OSCOM_Braintree->getTitle() . ' v' . $OSCOM_Braintree->getVersion() . ' <a href="' . $OSCOM_Braintree->link('Info') . '">' . $OSCOM_Braintree->getDef('app_link_info') . '</a> <a href="' . $OSCOM_Braintree->link('Privacy') . '">' . $OSCOM_Braintree->getDef('app_link_privacy') . '</a>'; ?>
  </div>
</div>

<h1><a href="<?= $OSCOM_Braintree->link('Info'); ?>"><?= $OSCOM_Braintree->getDef('page_title'); ?></a></h1>

<?= $OSCOM_Braintree->getDef('privacy_body'); ?>
