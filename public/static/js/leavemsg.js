function subForm(ismobile) {
    var content = $('#txtHelpContent').text();
    if (ismobile == 1) {
        content = $('#txtHelpContent').val();
    }
    $.post({
        url: '/leavemsg',
        data: {content: content},
        success(res) {
            if (res.err == 0) {
                $('#txtHelpContent').text(''); //提交成功后清空留言
                ShowDialog(res.msg);
            } else {
                ShowDialog(res.msg);
            }
        }, error(jqXHR, textStatus, errorThrown) {
            ShowDialog(jqXHR.responseText);
        }
    })

}