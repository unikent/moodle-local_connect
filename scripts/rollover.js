/**
 * Calls a proxy script to populate rollover autocompletes
 */
M.local_rollover = {
    Y : null,
    transaction : [],
    init : function(Y) {
        Y.one('#rollover_target').plug(Y.Plugin.AutoComplete, {
          source: M.cfg.wwwroot + '/local/connect/ajax/course_search.php?name={query}'
        });
    }
}
