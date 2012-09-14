/**
 * Organizing helper scripts
 * 
 * @module VocePostMeta
 * 
 */
window.VocePostMeta = {
    
    /**
     *
     * @method construct
     * @constructor
     */
    construct : function(){
        jQuery(document).ready(function($){
            $('.datepicker').each(function(){
                VocePostMeta.DatePicker.init(this);
            });
        });
        
    },
    
    /**
     * For media picker meta field
     * @submodule Media
     */
    Media : {
        
        /**
         * Display the selected media in the post meta box
         * 
         * @method setThumbnailHTML
         * @param string html
         * @param integer id
         * @param string post_type
         */
        setThumbnailHTML: function(html, id, post_type){
            jQuery('#set-'+ post_type +'-'+ id +'-thumbnail').html(unescape(html));
            jQuery('#remove-'+ post_type +'-'+ id +'-thumbnail').show();
        },
        
        /**
         * Populate the selected media ID in the hidden meta field
         *
         * @method setThumbnailID
         * @param integer thumb_id
         * @param integer id
         */
        setThumbnailID: function(thumb_id, id){
            var field = jQuery('input#asset_link.hidden');
            if ( field.size() > 0 ) {
                jQuery(field).val(thumb_id);
            }
        },
        
        /**
         * Unset the value in the hidden field
         * Remove the displayed image in the post meta box
         *
         * @method remove
         * @param integer id
         * @param post_type
         */
        remove: function(id, post_type){
            var field = jQuery('input#' + id + '.hidden');
            if ( field.size() > 0 ) {
                jQuery(field).val('');
            }
            jQuery("#set-" + post_type + "-" + id + "-thumbnail").html("Add media");
            jQuery("#remove-" + post_type + "-" + id + "-thumbnail").hide();
        },
        
        /**
         * Signal the selected media contents to the parent window (from TB)
         *
         * @method setAsThumbnail
         * @param integer thumb_id
         * @param integer id
         * @param string post_type
         * @param string img_html
         */
        setAsThumbnail: function(thumb_id, id, post_type, img_html){
            var win = window.dialogArguments || opener || parent || top;
            win.tb_remove();
            win.VocePostMeta.Media.setThumbnailID(thumb_id, id);
            win.VocePostMeta.Media.setThumbnailHTML(escape(img_html), id, post_type);
        }
        
    },
    
    /**
     * Helpers for the date picker post meta field
     *
     * @submodule DatePicker
     */
    DatePicker : {
        
        /**
         *
         * @method init
         * @param object el HTML element to use
         */
        init: function(el){
            this.bind(el);
            this.populateDisplayDate(el);
        },
        
        /**
         * Parse the date into a timestamp
         *
         * @method unixDate
         * @param object inst Instance from DatePicker plugin
         */
        unixDate: function(inst){
            var date = new Date( inst.selectedYear, inst.selectedMonth, inst.selectedDay );
            return Math.round( date.getTime()/1000 );
        },
        
        /**
         * 
         * @method bind
         * @param object el HTML element to trigger popup
         */
        bind: function(el){
            jQuery(el).datepicker({
                dateFormat: 'yy/mm/dd',
                changeMonth: true,
                changeYear: true,
                onSelect: function(dateText, inst) {
                    var inputID = jQuery(this).attr('id').replace("-formatted", "");
                    var formatted = VocePostMeta.DatePicker.unixDate(inst);
                    jQuery("#"+inputID).val(formatted);
                }
            });
        },
        
        /**
         * Just prepend a zero to date for formatting purposes
         * 
         * @method padDate
         * @param integer date
         */
        padDate : function(date){
            if(parseInt(date) < 10){
                date = "0" + date;
            }
            return date;
        },
        
        /**
         * 
         * @method populateDisplayDate
         * @param object el
         */
        populateDisplayDate: function(el){
            var inputID = jQuery(el).attr('id').replace("-formatted", "");
            var timestamp = parseInt(jQuery('#'+inputID).val());
            var dateObject = new Date(timestamp * 1000)
            var formatted = dateObject.getFullYear() + "/" + (this.padDate(dateObject.getMonth() + 1)) + "/" + (this.padDate(dateObject.getDate()));
            jQuery(el).val(formatted);
        }
    }
        
    
}

VocePostMeta.construct();
