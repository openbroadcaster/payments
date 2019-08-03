OBModules.Payments = new function () {
  this.init = function () {
    OB.Callbacks.add('ready', 0, OBModules.Payments.initMenu);
  }

  this.initMenu = function () {
    OB.UI.addSubMenuItem('admin', 'Payments', 'payments', OBModules.Payments.open, 150);
  }

  this.open = function () {
    OB.UI.replaceMain('modules/payments/payments.html');

    OBModules.Payments.ledgerOverview();

    if (OB.Settings.permissions.includes('payments_module')) {
      $('.payments_admin').show();
    }
  }

  /******************************
  * PERSONAL LEDGER FUNCTIONALITY
  ******************************/

  this.ledgerOverview = function (user_id = null) {
    post = {};
    if (user_id != null) post.user_id = user_id;

    OB.API.post('payments', 'ledger_overview', post, function (response) {
      var msg_result = (response.status) ? 'success' : 'error';
      if (!response.status) {
        $('#payments_message').obWidget(msg_result, response.msg);
        return false;
      }

      $('#payments_ledger_table tbody').empty();
      $('#payments_ledger_balance').html('$0.00');

      var balance = 0.00;
      $.each(response.data, function (i, transaction) {
        balance = balance + parseFloat(transaction.amount);

        $html = $('<tr/>');
        $html.append($('<td/>').text(transaction.id));
        $html.append($('<td/>').text(format_timestamp(transaction.created)));
        $html.append($('<td/>').text(transaction.amount));
        $html.append($('<td/>').text(transaction.comment));

        $('#payments_ledger_table tbody').append($html);
      });

      $('#payments_ledger_balance').html('$' + balance);
    });
  }

  this.ledgerSwitch = function () {
    OBModules.Payments.ledgerOverview($('#payments_ledger_user ob-user:first').attr('data-id'));
    $('#payments_ledger_user').val([]);
  }
}
