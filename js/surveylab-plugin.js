(function() {
    tinymce.create('tinymce.plugins.SurveyLab', {
        /**
         * Initializes the plugin, this will be executed after the plugin has been created.
         * This call is done before the editor instance has finished it's initialization so use the onInit event
         * of the editor instance to intercept that event.
         *
         * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
         * @param {string} url Absolute URL to where the plugin is located.
         */
        init : function(ed, url) {
            ed.addButton('surveylab_add_survey', {
                title : 'Add SurveyLab Survey',
                cmd : 'surveylab_add_survey',
                image : url + '/surveylab_logo.png'
            });

            ed.addCommand('surveylab_add_survey', function() {
                ed.windowManager.open({
                    title: 'SurveyLab Survey/Question Embedding',
                    body: [
                        {type: 'textbox', name: 'survey_id', label: 'Which is the ID of the Survey?'}
                    ],
                    onsubmit: function(e) {
                        // Insert content when the window form is submitted
                        var survey_id = e.data.survey_id;
                        if (survey_id !== null && survey_id.length > 0 && !isNaN(survey_id) && parseInt(survey_id) > 0) {
                            var shortcode = '[surveylab id="' + parseInt(survey_id) + '"]';
                            ed.insertContent(shortcode);
                        } else {
                            alert("You should insert a valid Survey ID");
                        }
                    }
                });
            });
        },
 
        /**
         * Creates control instances based in the incomming name. This method is normally not
         * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
         * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
         * method can be used to create those.
         *
         * @param {String} n Name of the control to create.
         * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
         * @return {tinymce.ui.Control} New control instance or null if no control was created.
         */
        createControl : function(n, cm) {
            return null;
        },
 
        /**
         * Returns information about the plugin as a name/value array.
         * The current keys are longname, author, authorurl, infourl and version.
         *
         * @return {Object} Name/value array containing information about the plugin.
         */
        getInfo : function() {
            return {
                longname : 'SurveyLab Plugin',
                author : 'SurveyLab',
                authorurl : 'https://surveylab.me',
                infourl : 'https://surveylab.me',
                version : "1.3"
            };
        }
    });
 
    // Register plugin
    tinymce.PluginManager.add( 'surveylab', tinymce.plugins.SurveyLab );
})();