<?php
use OSC\OM\HTML;
use OSC\OM\OSCOM;
use OSC\OM\Registry;

$OSCOM_Braintree = Registry::get('Braintree');

if (!class_exists('currencies')) {
    require(OSCOM::getConfig('dir_root', 'Shop') . 'includes/classes/currencies.php');
}

$ma_data = [];

if (!empty(OSCOM_APP_PAYPAL_BRAINTREE_CURRENCIES_MA)) {
    foreach (explode(';', OSCOM_APP_PAYPAL_BRAINTREE_CURRENCIES_MA) as $ma) {
        list($a, $currency) = explode(':', $ma);

        $ma_data[$currency] = $a;
    }
}

$sandbox_ma_data = [];

if (!empty(OSCOM_APP_PAYPAL_BRAINTREE_SANDBOX_CURRENCIES_MA)) {
    foreach (explode(';', OSCOM_APP_PAYPAL_BRAINTREE_SANDBOX_CURRENCIES_MA) as $ma) {
        list($a, $currency) = explode(':', $ma);

        $sandbox_ma_data[$currency] = $a;
    }
}

$currencies = new currencies();
?>

<div class="row" style="padding-bottom: 30px;">
  <div class="col-sm-6">
    <a href="<?= $OSCOM_Braintree->link(); ?>"><img src="<?= OSCOM::link('Shop/public/Apps/PayPal/Braintree/images/braintree.png', '', false); ?>" /></a>
  </div>

  <div class="col-sm-6 text-right text-muted">
    <?= $OSCOM_Braintree->getTitle() . ' v' . $OSCOM_Braintree->getVersion() . ' <a href="' . $OSCOM_Braintree->link('Info') . '">' . $OSCOM_Braintree->getDef('app_link_info') . '</a> <a href="' . $OSCOM_Braintree->link('Privacy') . '">' . $OSCOM_Braintree->getDef('app_link_privacy') . '</a>'; ?>
  </div>
</div>

<h1><a href="<?= $OSCOM_Braintree->link('Credentials'); ?>"><?= $OSCOM_Braintree->getDef('page_title'); ?></a></h1>

<form name="braintreeCredentials" action="<?= $OSCOM_Braintree->link('Credentials&Process'); ?>" method="post">

<ul class="nav nav-tabs" role="tablist">
  <li role="presentation" class="active"><a href="#live" aria-controls="live" role="tab" data-toggle="tab"><?= $OSCOM_Braintree->getDef('section_live'); ?></a></li>
  <li role="presentation"><a href="#sandbox" aria-controls="sandbox" role="tab" data-toggle="tab"><?= $OSCOM_Braintree->getDef('section_sandbox'); ?></a></li>
</ul>

<div class="tab-content">
  <div role="tabpanel" class="tab-pane active" id="live">
    <div class="panel panel-info oscom-panel">
      <div class="panel-heading">
        <h3 class="panel-title"><?= $OSCOM_Braintree->getDef('heading_live'); ?></h3>
      </div>

      <div class="panel-body">
        <div class="container-fluid">
          <div class="row">
            <h4><?php echo $OSCOM_Braintree->getDef('merchant_id'); ?></h4>

            <div>
              <?= HTML::inputField('oscom_app_paypal_braintree_merchant_id', OSCOM_APP_PAYPAL_BRAINTREE_MERCHANT_ID); ?>
            </div>
          </div>

          <div class="row">
            <h4><?php echo $OSCOM_Braintree->getDef('public_key'); ?></h4>

            <div>
              <?= HTML::inputField('oscom_app_paypal_braintree_public_key', OSCOM_APP_PAYPAL_BRAINTREE_PUBLIC_KEY); ?>
            </div>
          </div>

          <div class="row">
            <h4><?php echo $OSCOM_Braintree->getDef('private_key'); ?></h4>

            <div>
              <?= HTML::inputField('oscom_app_paypal_braintree_private_key', OSCOM_APP_PAYPAL_BRAINTREE_PRIVATE_KEY); ?>
            </div>
          </div>
        </div>
      </div>

      <div class="panel-heading">
        <h3 class="panel-title"><?= $OSCOM_Braintree->getDef('heading_merchant_currency_accounts'); ?></h3>
      </div>

      <div class="panel-body">
        <div class="container-fluid">

<?php
foreach (array_keys($currencies->currencies) as $c) {
?>

          <div class="row">
            <h4><?php echo $c . ($c == DEFAULT_CURRENCY ? ' <small>(default)</small>' : ''); ?></h4>

            <div>
              <?= HTML::inputField('currency_ma[' . $c . ']', (isset($ma_data[$c]) ? $ma_data[$c] : '')); ?>
            </div>
          </div>

<?php
}

echo HTML::hiddenField('oscom_app_paypal_braintree_currencies_ma', OSCOM_APP_PAYPAL_BRAINTREE_CURRENCIES_MA);
?>

        </div>
      </div>
    </div>
  </div>

  <div role="tabpanel" class="tab-pane" id="sandbox">
    <div class="panel panel-warning oscom-panel">
      <div class="panel-heading">
        <h3 class="panel-title"><?= $OSCOM_Braintree->getDef('heading_sandbox'); ?></h3>
      </div>

      <div class="panel-body">
        <div class="container-fluid">
          <div class="row">
            <h4><?php echo $OSCOM_Braintree->getDef('merchant_id'); ?></h4>

            <div>
              <?= HTML::inputField('oscom_app_paypal_braintree_sandbox_merchant_id', OSCOM_APP_PAYPAL_BRAINTREE_SANDBOX_MERCHANT_ID); ?>
            </div>
          </div>

          <div class="row">
            <h4><?php echo $OSCOM_Braintree->getDef('public_key'); ?></h4>

            <div>
              <?= HTML::inputField('oscom_app_paypal_braintree_sandbox_public_key', OSCOM_APP_PAYPAL_BRAINTREE_SANDBOX_PUBLIC_KEY); ?>
            </div>
          </div>

          <div class="row">
            <h4><?php echo $OSCOM_Braintree->getDef('private_key'); ?></h4>

            <div>
              <?= HTML::inputField('oscom_app_paypal_braintree_sandbox_private_key', OSCOM_APP_PAYPAL_BRAINTREE_SANDBOX_PRIVATE_KEY); ?>
            </div>
          </div>
        </div>
      </div>

      <div class="panel-heading">
        <h3 class="panel-title"><?= $OSCOM_Braintree->getDef('heading_merchant_currency_accounts'); ?></h3>
      </div>

      <div class="panel-body">
        <div class="container-fluid">

<?php
foreach (array_keys($currencies->currencies) as $c) {
?>

          <div class="row">
            <h4><?php echo $c . ($c == DEFAULT_CURRENCY ? ' <small>(default)</small>' : ''); ?></h4>

            <div>
              <?= HTML::inputField('sandbox_currency_ma[' . $c . ']', (isset($sandbox_ma_data[$c]) ? $sandbox_ma_data[$c] : '')); ?>
            </div>
          </div>

<?php
}

echo HTML::hiddenField('oscom_app_paypal_braintree_sandbox_currencies_ma', OSCOM_APP_PAYPAL_BRAINTREE_SANDBOX_CURRENCIES_MA);
?>

        </div>
      </div>
    </div>
  </div>
</div>

<p><?= HTML::button($OSCOM_Braintree->getDef('button_save'), null, null, null, 'btn-success'); ?></p>

</form>

<script>
$(function() {
  $('form[name="braintreeCredentials"]').submit(function() {
    var ma_string = '';
    var ma_sandbox_string = '';

// live
    $('form[name="braintreeCredentials"] input[name^="currency_ma["]').each(function() {
      if ($(this).val().length > 0) {
        ma_string += $(this).val() + ':' + $(this).attr('name').slice(12, -1) + ';';
      }
    });

    if (ma_string.length > 0) {
      ma_string = ma_string.slice(0, -1);
    }

    $('form[name="braintreeCredentials"] input[name="oscom_app_paypal_braintree_currencies_ma"]').val(ma_string);

// sandbox
    $('form[name="braintreeCredentials"] input[name^="sandbox_currency_ma["]').each(function() {
      if ($(this).val().length > 0) {
        ma_sandbox_string += $(this).val() + ':' + $(this).attr('name').slice(20, -1) + ';';
      }
    });

    if (ma_sandbox_string.length > 0) {
      ma_sandbox_string = ma_sandbox_string.slice(0, -1);
    }

    $('form[name="braintreeCredentials"] input[name="oscom_app_paypal_braintree_sandbox_currencies_ma"]').val(ma_sandbox_string);
  })
});
</script>
