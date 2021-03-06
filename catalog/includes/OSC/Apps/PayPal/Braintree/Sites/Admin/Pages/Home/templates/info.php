<?php
use OSC\OM\HTML;
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

<div class="row">
  <div class="col-sm-6">
    <div class="panel panel-info">
      <div class="panel-heading">
        <?= $OSCOM_Braintree->getDef('online_documentation_title'); ?>
      </div>

      <div class="panel-body">
        <?=
            $OSCOM_Braintree->getDef('online_documentation_body', [
                'button_online_documentation' => HTML::button($OSCOM_Braintree->getDef('button_online_documentation'), null, 'https://library.oscommerce.com/Package&braintree&oscom24', ['newwindow' => true], 'btn-info')
            ]);
        ?>
      </div>
    </div>
  </div>

  <div class="col-sm-6">
    <div class="panel panel-warning">
      <div class="panel-heading">
        <?= $OSCOM_Braintree->getDef('online_forum_title'); ?>
      </div>

      <div class="panel-body">
        <?=
            $OSCOM_Braintree->getDef('online_forum_body', [
                'button_online_forum' => HTML::button($OSCOM_Braintree->getDef('button_online_forum'), null, 'http://forums.oscommerce.com/forum/109-braintree/', ['newwindow' => true], 'btn-warning')
            ]);
        ?>
      </div>
    </div>
  </div>
</div>
