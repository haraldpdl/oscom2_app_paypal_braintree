<?php
use OSC\OM\HTML;
use OSC\OM\OSCOM;
use OSC\OM\Registry;

$OSCOM_Braintree = Registry::get('Braintree');
$OSCOM_Braintree_Config = Registry::get('BraintreeAdminConfigBT');
?>

<p><a href="<?= $OSCOM_Braintree->link(); ?>"><img src="<?= OSCOM::link('Shop/public/Apps/PayPal/Braintree/images/braintree.png', '', 'AUTO', false); ?>" width="200" /></a></p>

<h1><a href="<?= $OSCOM_Braintree->link('Configuration'); ?>"><?= $OSCOM_Braintree->getDef('page_title'); ?></a></h1>

<form name="braintreeConfigure" action="<?= $OSCOM_Braintree->link('Configuration&Process'); ?>" method="post">

<div class="panel panel-info oscom-panel">
  <div class="panel-heading">
    <h3 class="panel-title"><?= $OSCOM_Braintree->getDef('page_title'); ?></h3>
  </div>

  <div class="panel-body">
    <div class="container-fluid">

<?php
foreach ($OSCOM_Braintree_Config->getInputParameters() as $cfg) {
    echo $cfg;
}
?>

    </div>
  </div>
</div>

<p><?= HTML::button($OSCOM_Braintree->getDef('button_save'), null, null, null, 'btn-success'); ?></p>

</form>
