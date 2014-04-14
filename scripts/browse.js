$(function() {
    $('#connect_browser').jstree({
        "core" : {
            "animation" : 1,
            "check_callback" : true,
            "themes" : { "stripes" : true },
            'data' : {
                'url' : function (node) {
                    return M.cfg.wwwroot + "/local/connect/ajax/tree_data.php";
                },
                'data' : function (node) {
                    return { 'id' : node.id };
                },
                'dataType' : 'json'
            }
        },
        "search" : {
            "show_only_matches" : true,
            "fuzzy" : false,
            "ajax" : {
                'url' : M.cfg.wwwroot + "/local/connect/ajax/tree_search.php",
                'dataType' : 'json'
            }
        },
        "plugins" : [
            "search"
        ]
    });

    $('#connect_browser').on("changed.jstree", function (e, data) {
        if (data.node.a_attr.href != "#") {
            window.location = data.node.a_attr.href;
        }
    });

    var to = false;
    $('#cb_search').keyup(function () {
        if (to) {
            clearTimeout(to);
        }
        to = setTimeout(function () {
            var v = $('#cb_search').val();
            $('#connect_browser').jstree(true).search(v);
        }, 250);
    });
});