function set_up_form_test(test, endpoint, callback) {
  $(function(){
    $("#run").click(function(){
      $("#run").removeClass('green').addClass('disabled');
      $.post('/server-tests/micropub', {
        test: test,
        endpoint: endpoint,
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

function store_result(test, endpoint, passed) {
  $.post('/server-tests/store-result', {
    endpoint: endpoint,
    test: test,
    passed: passed
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
