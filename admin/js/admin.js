jQuery(document).ready(function ($) {
    // Color picker
    $( '.pvp-color-field' ).wpColorPicker( {
    alpha: true,
    change: function ( event, ui ) {
        if ( ui.color ) {
            var color = ui.color.toCSS(); // toCSS() returns rgba() format
            var $input = $( event.target );
            if ( $input.val() !== color ) {
                $input.val( color );
            }
        }
    }
} );
    // Media uploader - handle both existing and dynamically added buttons
    $(document).on('click', '.pvp-upload-btn', function (e) {
        e.preventDefault();
        var button = $(this);
        var input = button.prev('.pvp-image-url');
        var frame = wp.media({
            title: 'Select Image',
            button: { text: 'Use this image' },
            multiple: false
        });
        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            input.val(attachment.url);
        });
        frame.open();
    });

    $('input[name="pvp_video_logo_circle"]').on('change', function() {
        $('input[name="pvp_video_logo_radius"]').prop('disabled', this.checked);
    });


    // Add time range row
    $( document ).on( 'click', '#pvp-add-time-range', function () {
        var container = $( '#pvp-time-ranges-container' );
        var index     = container.find( '.pvp-time-range-row' ).length;

        var html = '<div class="pvp-time-range-row" style="display: flex; gap: 20px; flex-wrap: wrap; align-items: center; margin-top: 10px; padding: 10px; border: 1px solid #eee; border-radius: 4px;">' +
            '<div>' +
                '<label><strong>Show at</strong></label>' +
                '<div style="display: flex; gap: 5px; align-items: center; margin-top: 4px;">' +
                    '<input type="number" name="pvp_time_ranges[' + index + '][start_h]" value="0" min="0" max="23" style="width:55px;" />' +
                    ' : ' +
                    '<input type="number" name="pvp_time_ranges[' + index + '][start_m]" value="0" min="0" max="59" style="width:55px;" />' +
                    ' : ' +
                    '<input type="number" name="pvp_time_ranges[' + index + '][start_s]" value="0" min="0" max="59" style="width:55px;" />' +
                '</div>' +
            '</div>' +
            '<div>' +
                '<label><strong>Hide at</strong></label>' +
                '<div style="display: flex; gap: 5px; align-items: center; margin-top: 4px;">' +
                    '<input type="number" name="pvp_time_ranges[' + index + '][end_h]" value="0" min="0" max="23" style="width:55px;" />' +
                    ' : ' +
                    '<input type="number" name="pvp_time_ranges[' + index + '][end_m]" value="0" min="0" max="59" style="width:55px;" />' +
                    ' : ' +
                    '<input type="number" name="pvp_time_ranges[' + index + '][end_s]" value="0" min="0" max="59" style="width:55px;" />' +
                '</div>' +
            '</div>' +
            '<div style="align-self: flex-end;">' +
                '<button type="button" class="button pvp-delete-time-range" style="background: #dc3232; color: #fff; border-color: #dc3232;">Delete</button>' +
            '</div>' +
        '</div>';

        container.append( html );
    } );

    // Delete time range row
    $( document ).on( 'click', '.pvp-delete-time-range', function () {
        $( this ).closest( '.pvp-time-range-row' ).remove();
    } );


    // Add default time range row
$( document ).on( 'click', '#pvp-add-default-time-range', function () {
    var container = $( '#pvp-default-time-ranges-container' );
    var index     = container.find( '.pvp-default-time-range-row' ).length;
    var html = '<div class="pvp-default-time-range-row" style="display: flex; gap: 20px; flex-wrap: wrap; align-items: center; margin-bottom: 10px; padding: 10px; border: 1px solid #eee; border-radius: 4px;">' +
        '<div><label><strong>Show at</strong></label><div style="display: flex; gap: 5px; align-items: center; margin-top: 4px;">' +
        '<input type="number" name="pvp_settings[overlay_time_ranges][' + index + '][start_h]" value="0" min="0" max="23" style="width:55px;" />:' +
        '<input type="number" name="pvp_settings[overlay_time_ranges][' + index + '][start_m]" value="0" min="0" max="59" style="width:55px;" />:' +
        '<input type="number" name="pvp_settings[overlay_time_ranges][' + index + '][start_s]" value="0" min="0" max="59" style="width:55px;" /></div></div>' +
        '<div><label><strong>Hide at</strong></label><div style="display: flex; gap: 5px; align-items: center; margin-top: 4px;">' +
        '<input type="number" name="pvp_settings[overlay_time_ranges][' + index + '][end_h]" value="0" min="0" max="23" style="width:55px;" />:' +
        '<input type="number" name="pvp_settings[overlay_time_ranges][' + index + '][end_m]" value="0" min="0" max="59" style="width:55px;" />:' +
        '<input type="number" name="pvp_settings[overlay_time_ranges][' + index + '][end_s]" value="0" min="0" max="59" style="width:55px;" /></div></div>' +
        '<div style="align-self: flex-end;"><button type="button" class="button pvp-delete-default-time-range" style="background: #dc3232; color: #fff; border-color: #dc3232;">Delete</button></div>' +
        '</div>';
    container.append( html );
} );

$( document ).on( 'click', '.pvp-delete-default-time-range', function () {
    if ( $( '#pvp-default-time-ranges-container .pvp-default-time-range-row' ).length > 1 ) {
        $( this ).closest( '.pvp-default-time-range-row' ).remove();
    } else {
        alert( 'At least one time range is required.' );
    }
} );

    
        
});


