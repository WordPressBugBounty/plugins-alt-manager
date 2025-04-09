jQuery(document).ready(function ($) {
    let offset = 0;
    const limit = 10; // Number of images to process per batch
    let total = 0;

    $('#alm-ai-generate-btn').on('click', function () {
        offset = 0;
        total = 0;

        // Reset progress bar and status
        $('#ai-bar').css('width', '0%');
        $('#ai-status').text('0 / 0');
        $(this).prop('disabled', true);

        // Start processing batches
        processBatch();
    });

    function processBatch() {
        $.ajax({
            url: ajaxurl, // WordPress AJAX URL
            method: 'POST',
            data: {
                action: 'alm_generate_ai_images',
                offset: offset,
                limit: limit,
            },
            success: function (response) {
                if (response.success) {
                    const processed = response.data.processed;
                    const remaining = response.data.remaining;
                    const totalImages = response.data.total;

                    offset = response.data.offset;
                    total = totalImages;

                    // Update progress bar and status
                    const progress = Math.round(((total - remaining) / total) * 100);
                    $('#ai-bar').css('width', progress + '%');
                    $('#ai-status').text((total - remaining) + ' / ' + total);

                    // Log the current state
                    console.log('Processed:', processed, 'Remaining:', remaining, 'Offset:', offset, 'Total:', total);

                    // If there are remaining images, process the next batch
                    if (remaining > 0) {
                        processBatch();
                    } else {
                        // All images processed
                        $('#alm-ai-generate-btn').prop('disabled', false);
                        alert('AI alt/title generation is complete!');
                    }
                } else {
                    // Stop processing and alert the error message
                    $('#alm-ai-generate-btn').prop('disabled', false);
                    console.log('Error:', response.data.message); // Log the error message
                    alert('Error: ' + response.data.message);
                }
            },
            error: function () {
                // Handle AJAX request failure
                $('#alm-ai-generate-btn').prop('disabled', false);
                console.log('AJAX request failed'); // Log the AJAX failure
                alert('AJAX request failed.');
            },
        });
    }
});