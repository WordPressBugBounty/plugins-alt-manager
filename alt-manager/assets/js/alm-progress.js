jQuery(document).ready(function ($) {
    let offset = 0;
    const limit = 10; // Number of images to process per batch
    let total = 0;
    let cumulativeProcessed = 0;

    $('#alm-ai-generate-btn').on('click', function () {

        offset = 0;
        total = 0;
        cumulativeProcessed = 0;

        // Reset progress bar and status
        $('#ai-bar').css('width', '0%');
        $('#ai-status').text('0 / 0');
        $(this).prop('disabled', true);

        // Start processing batches
        processBatch();
    });

    function processBatch() {
        $.ajax({
            url: almProgress.ajaxurl, // Plugin-localized WordPress AJAX URL
            method: 'POST',
            data: {
                action: 'alm_generate_ai_images',
                offset: offset,
                limit: limit,
            },
            success: function (response) {
                if (response.success) {
                    const processed = parseInt(response.data.processed, 10) || 0;
                    const remaining = parseInt(response.data.remaining, 10) || 0;
                    const totalImages = parseInt(response.data.total, 10) || 0;

                    offset = parseInt(response.data.offset, 10) || offset;
                    total = totalImages;

                    // Accumulate processed count across batches
                    cumulativeProcessed += processed;

                    // Update progress bar and status
                    var progress = 0;
                    if (total > 0) {
                        progress = Math.round((cumulativeProcessed / total) * 100);
                    } else if (remaining === 0) {
                        progress = 100;
                    }

                    // Ensure final state shows 100% and total/total
                    if (remaining === 0) {
                        $('#ai-bar').css('width', '100%');
                        $('#ai-status').text(total + ' / ' + total);
                    } else {
                        $('#ai-bar').css('width', progress + '%');
                        $('#ai-status').text(cumulativeProcessed + ' / ' + total);
                    }

                    // Log the current state
                    console.log('Batch processed:', processed, 'Cumulative:', cumulativeProcessed, 'Remaining:', remaining, 'Offset:', offset, 'Total:', total, 'Progress:', progress);

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
