jQuery(document).ready(function($) {
    // Edit modal
    $(document).on('click', '.edit-button', function() {
        var userId = $(this).data('user-id');
        $.ajax({
            url: campuslifeAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'campuslife_admin_edit',
                user_id: userId,
                nonce: campuslifeAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('body').append('<div class="modal-overlay"><div class="modal">' + response.data + '</div></div>');
                    $('.modal .close-modal').on('click', function() {
                        $('.modal-overlay').remove();
                    });
                    $('.modal form').on('submit', function(e) {
                        e.preventDefault();
                        var formData = new FormData(this);
                        formData.append('action', 'campuslife_admin_update');
                        formData.append('nonce', campuslifeAjax.nonce);
                        $.ajax({
                            url: campuslifeAjax.ajax_url,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                if (response.success) {
                                    alert(response.data);
                                    $('.modal-overlay').remove();
                                    location.reload(); // Refresh to update table
                                } else {
                                    alert(response.data);
                                }
                            },
                            error: function() {
                                alert('Error updating ID. Please try again.');
                            }
                        });
                    });
                }
            },
            error: function() {
                alert('Error loading edit form. Please try again.');
            }
        });
    });

    // Delete confirmation
    $(document).on('click', '.delete-button', function() {
        var userId = $(this).data('user-id');
        if (confirm('¿Estás seguro de que quieres eliminar este ID? Esta acción no se puede deshacer.')) {
            $.ajax({
                url: campuslifeAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'campuslife_admin_delete',
                    user_id: userId,
                    nonce: campuslifeAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data);
                        $('#campuslife-id-table tbody tr[data-user-id="' + userId + '"]').remove();
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('Error deleting ID. Please try again.');
                }
            });
        }
    });
});