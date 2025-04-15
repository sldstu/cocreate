<?php
/**
 * Common footer file to be included in all pages
 */
?>
    <script>
        // Common AJAX setup
        $.ajaxSetup({
            cache: false,
            error: function(xhr, status, error) {
                console.error("AJAX Error: " + status + " - " + error);
                alert("An error occurred. Please try again later.");
            }
        });
    </script>
    <?php if (isset($additionalFooterContent)) echo $additionalFooterContent; ?>
</body>
</html>
