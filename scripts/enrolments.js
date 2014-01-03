/**
 * Calls a proxy script to fix missing enrolments
 */
M.local_enrolment = {
    Y : null,
    transaction : [],
    init : function(Y) {
        var infobox = Y.one(".box.enrolmentbox");
        
        Y.io(M.cfg.wwwroot + "/local/connect/ajax-enrolment.php", {
            timeout: 32000,
            method: "GET",
            on: {
                success : function (x,o) {
                    // Process the JSON data returned from the server
                    try {
                        data = Y.JSON.parse(o.responseText);
                    }
                    catch (e) {
                        infobox.setHTML("An error occurred, please contact helpdesk. (Code: ejs10012)");
                        return;
                    }

                    if (data.result == "error") {
                        infobox.setHTML("An error occurred, please contact helpdesk. (Code: ejs10013)");
                    } else {
                        infobox.setHTML(data.result);
                    }
                },

                failure : function (x,o) {
                    if (o.statusText == "timeout") {
                        infobox.setHTML("An error occurred, please contact helpdesk. (Code: ejs10014)");
                    } else {
                        infobox.setHTML("An error occurred, please contact helpdesk. (Code: ejs10015)");
                    }
                }
            }
        });
    }
}
