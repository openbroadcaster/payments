OBModules.Payments = new function () {
  this.init = function () {
    OB.Callbacks.add('ready', 0, OBModules.Payments.initMenu);
  }

  this.initMenu = function () {
    OB.UI.addSubMenuItem('account', 'Payments', 'payments', OBModules.Payments.open, 5);
  }

  this.open = function () {
    OB.UI.replaceMain('modules/payments/payments.html');

    OBModules.Payments.ledgerOverview();

    if (OB.Settings.permissions.includes('payments_module')) {
      $('.payments_admin').show();
      $('#payments_ledger_id').val(OB.Account.user_id);
      OBModules.Payments.showUserInfo(OB.Account.user_id, '#payments_ledger_selected', '#payments_message');
    }
  }

  /******************************
  * PERSONAL LEDGER FUNCTIONALITY
  ******************************/

  this.ledgerOverview = function (user_id = null) {
    post = {};
    if (user_id != null) post.user_id = user_id;

    if (user_id == 'all') {
      $('.payments_field_user').show();
    } else {
      $('.payments_field_user').hide();
    }

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
        $html.append($('<td/>').text(transaction.created));
        $html.append($('<td/>').html(transaction.comment));

        var amount_string = (transaction.amount < 0 ? '-' : '') + '$' + Math.abs(transaction.amount).toFixed(2);
        var balance_string = (balance < 0 ? '-' : '') + '$' + Math.abs(balance).toFixed(2);

        $html.append($('<td/>').text(amount_string));
        $html.append($('<td/>').text(balance_string));

        if (user_id == 'all') {
          $html.append($('<td/>').text(transaction.username).attr('data-id', transaction.user_id));
        }

        if (OB.Settings.permissions.includes('payments_module')) {
          $edit = '<button class="edit" onclick="OBModules.Payments.transactionEdit(' + transaction.id + ')">Edit</button>';
          $delete = '<button class="delete" onclick="OBModules.Payments.transactionDelete(' + transaction.id + ')">Delete</button>';
          $html.append($('<td/>').html($edit + $delete));
        }

        $('#payments_ledger_table tbody').append($html);
      });

      /* Reverse rows so we the most recent transactions show up first. We need
      to start with the oldest transactions to calculate the balance over time. */
      var $html = $('#payments_ledger_table tbody');
      $html.html($('tr', $html).get().reverse());

      $('#payments_ledger_balance').html('$' + parseFloat(balance).toFixed(2));
    });
  }

  this.ledgerSwitch = function () {
    var user_id = $('#payments_ledger_user ob-user:first').attr('data-id');

    OBModules.Payments.ledgerOverview(user_id);
    OBModules.Payments.showUserInfo(user_id, '#payments_ledger_selected', '#payments_message');
    $('#payments_ledger_id').val(user_id);

  }

  /**************************
  * TRANSACTION FUNCTIONALITY
  **************************/

  this.transactionNew = function () {
    OB.UI.openModalWindow('modules/payments/payments_new.html');

    var user_id = $('#payments_ledger_id').val();
    $('#payments_new_date').datepicker({ dateFormat: "yy-mm-dd" });
    OBModules.Payments.showUserInfo(user_id, '#payments_new_user', '#payments_new_message');
    $('#payments_new_user_id').val(user_id);
  }

  this.transactionEdit = function (transaction_id) {
    OBModules.Payments.transactionNew();

    $('#payments_new_header').html('Edit Transaction');
    $('#payments_new_add_button').html('Edit Transaction');
    $('#payments_new_transaction_id').val(transaction_id);

    OB.API.post('payments', 'transaction_get', {id: transaction_id}, function (response) {
      var msg_result = (response.status) ? 'success' : 'error';
      if (!response.status) {
        OB.UI.closeModalWindow();
        $('#payments_message').obWidget(msg_result, response.msg);
        return false;
      }

      $('#payments_new_type').val((response.data.amount < 0) ? 'payment' : 'compensation');
      $('#payments_new_amount').val(Math.abs(response.data.amount).toFixed(2));
      $('#payments_new_date').val(response.data.created);
      $('#payments_new_comment').val(response.data.comment);
    });
  }

  this.transactionAdd = function () {
    var type = ($('#payments_new_transaction_id').val() == '') ? 'add' : 'edit';
    var post = {};
    if (type == 'add') {
      post.user_id = $('#payments_new_user_id').val();
    } else {
      post.transaction_id = $('#payments_new_transaction_id').val();
    }
    post.type    = $('#payments_new_type').val();
    post.amount  = $('#payments_new_amount').val();
    post.created = $('#payments_new_date').val();
    post.comment = $('#payments_new_comment').val();

    OB.API.post('payments', 'transaction_' + type, post, function (response) {
      var msg_result = (response.status) ? 'success' : 'error';
      if (!response.status) {
        $('#payments_new_message').obWidget(msg_result, response.msg);
        return false;
      }

      OB.UI.closeModalWindow();
      OBModules.Payments.ledgerOverview($('#payments_ledger_id').val());
      $('#payments_message').obWidget(msg_result, response.msg);
    })
  }

  this.transactionDelete = function (transaction_id) {
    OB.UI.confirm({
      text: "Are you sure you want to delete this transaction?",
      okay_class: "delete",
      callback: function () {
        OBModules.Payments.transactionDeleteConfirm(transaction_id);
      }
    });
  }

  this.transactionDeleteConfirm = function (transaction_id) {
    OB.API.post('payments', 'transaction_delete', {id: transaction_id}, function (response) {
      var msg_result = (response.status) ? 'success' : 'error';
      if (!response.status) {
        $('#payments_message').obWidget(msg_result, response.msg);
        return false;
      }

      OBModules.Payments.ledgerOverview($('#payments_ledger_id').val());
      $('#payments_message').obWidget(msg_result, response.msg);
    });
  }

  /******************
  * UTILITY FUNCTIONS
  ******************/

  this.showUserInfo = function (user_id, elem, msg_widget) {
    if (user_id == 'all') {
      //T All Users
      $(elem).text(OB.t('All Users'));
      return false;
    }

    OB.API.post('payments', 'get_user_data', {user_id: user_id}, function (response) {
      var msg_result = (response.status) ? 'success' : 'error';
      if (!response.status) {
        $(msg_widget).obWidget(msg_result, response.msg);
        return false;
      }

      $(elem).text(response.data.name + ' <' + response.data.email + '>');
    });
  }
}
