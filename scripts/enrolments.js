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
                        // TODO - fail
                        return;
                    }

                    if (data.result == "error") {
                        // TODO - fail
                    } else {
                        // TODO - succeed!
                        // infobox.setHTML("Panopto seems to be a bit busy right now! Try again later.");
                    }
                },

                failure : function (x,o) {
                    if (o.statusText == "timeout") {
                        // TODO - fail
                    } else {
                        // TODO - fail
                    }
                }
            }
        });
    }
}
