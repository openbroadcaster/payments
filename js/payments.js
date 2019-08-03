OBModules.Payments = new function () {
  this.init = function () {
    OB.Callbacks.add('ready', 0, OBModules.Payments.initMenu);
  }

  this.initMenu = function () {
    OB.UI.addSubMenuItem('admin', 'Payments', 'payments', OBModules.Payments.open, 150, 'payments_module');
  }

  this.open = function () {
    OB.UI.replaceMain('modules/payments/payments.html');

    OBModules.Payments.ledgerOverview();
  }

  /******************************
  * PERSONAL LEDGER FUNCTIONALITY
  ******************************/

  this.ledgerOverview = function () {
    OB.API.post('payments', 'ledger_overview', {}, function (response) {
      var msg_result = (response.status) ? 'success' : 'error';
      if (!response.status) {
        $('#payments_message').obWidget(msg_result, response.msg);
        return false;
      }

      var total = 0.00;
      $.each(response.data, function (i, transaction) {
        total  = total + transaction.amount;
      });
      $('#payments_ledger_total').html('$' + total);
    });
  }
}
