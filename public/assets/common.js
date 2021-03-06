
$(function(){
  $("#galaxy").val(42);
});

function set_up_form_test(test, endpoint, callback, skipauth=false) {
  $(function(){
    $("#run").click(function(){
      $("#run").removeClass('green').addClass('disabled');
      $.post('/server-tests/micropub', {
        test: test,
        endpoint: endpoint,
        skipauth: skipauth ? 1 : 0,
        access_token: $("#access-token-input").val(),
        method: 'post',
        body: $('#postbody').text().replace(/\n/g,'')
      }, function(data) {
        $("#response").text(data.debug);
        $("#response-section").removeClass('hidden');
        $("#run").addClass('green').removeClass('disabled');
        callback(data);
      })
    });
  });
}

function set_up_multipart_test(test, endpoint, media_endpoint, params, files, callback) {
  $(function(){
    $("#run").click(function(){
      $("#run").removeClass('green').addClass('disabled');
      $.post('/server-tests/micropub', {
        test: test,
        endpoint: endpoint,
        url: media_endpoint,
        method: 'multipart',
        params: params,
        files: files
      }, function(data) {
        $("#response").text(data.debug);
        $("#response-section").removeClass('hidden');
        $("#run").addClass('green').removeClass('disabled');
        callback(data);
      })
    });
  });
}

function set_up_json_test(test, endpoint, callback) {
  $(function(){
    $("#run").click(function(){
      $("#run").removeClass('green').addClass('disabled');
      $.post('/server-tests/micropub', {
        test: test,
        endpoint: endpoint,
        method: 'postjson',
        body: $('#postbody').text().replace(/\n/g,'')
      }, function(data) {
        $("#response").text(data.debug);
        $("#response-section").removeClass('hidden');
        $("#run").addClass('green').removeClass('disabled');
        callback(data);
      })
    });
  });
}

function set_up_update_test(test, endpoint, callback) {
  $("#run-update").click(function(){
    $("#run-update").removeClass('green').addClass('disabled');
    $.post('/server-tests/micropub', {
      test: test,
      endpoint: endpoint,
      method: 'postjson',
      body: $('#updatebody').text().replace(/\n/g,'')
    }, function(data) {
      $("#update-response").text(data.debug);
      $("#update-response-section").removeClass('hidden');
      $("#run-update").addClass('green').removeClass('disabled');
      callback(data);
    });
  });
}

function set_up_delete_test(test, method, endpoint, callback) {
  $("#run-delete").click(function(){
    $("#run-delete").removeClass('green').addClass('disabled');
    $.post('/server-tests/micropub', {
      test: test,
      endpoint: endpoint,
      method: method,
      body: $('#deletebody').text().replace(/\n/g,'')
    }, function(data) {
      $("#delete-response").text(data.debug);
      $("#delete-response-section").removeClass('hidden');
      $("#run-delete").addClass('green').removeClass('disabled');
      callback(data);
    });
  });
}

function set_up_undelete_test(test, method, endpoint, callback) {
  $("#run-undelete").click(function(){
    $("#run-undelete").removeClass('green').addClass('disabled');
    $.post('/server-tests/micropub', {
      test: test,
      endpoint: endpoint,
      method: method,
      body: $('#undeletebody').text().replace(/\n/g,'')
    }, function(data) {
      $("#undelete-response").text(data.debug);
      $("#undelete-response-section").removeClass('hidden');
      $("#run-undelete").addClass('green').removeClass('disabled');
      callback(data);
    });
  });
}

function set_up_query_test(test, endpoint, callback) {
  $(function(){
    $("#run-query").click(function(){
      $("#run-query").removeClass('green').addClass('disabled');
      $.post('/server-tests/micropub', {
        test: test,
        endpoint: endpoint,
        method: 'get',
        url: $("#query_url").text()
      }, function(data) {
        $("#query-response").text(data.debug);
        $("#query-response-section").removeClass('hidden');
        $("#run-query").addClass('green').removeClass('disabled');
        callback(data);
      })
    });
  });
}

function store_result(test, endpoint, passed) {
  $.post('/server-tests/store-result', {
    endpoint: endpoint,
    test: test,
    passed: passed
  });
}

function store_server_feature(endpoint_id, feature_num, implements, test) {
  $.post('/implementation-report/store-result', {
    type: 'server',
    id: endpoint_id,
    feature_num: feature_num,
    implements: implements,
    source_test: test
  });
}

function set_result_icon(sel, passed) {
  switch(passed) {
    case 1:
      $(sel).addClass('green').removeClass('red').html('&#x2714;');
      break;
    case -1:
      $(sel).removeClass('green').addClass('red').html('&#x2716;');
      break;
    case 0:
      $(sel).removeClass('green').removeClass('red').html('&nbsp;');
      break;
  }
}
