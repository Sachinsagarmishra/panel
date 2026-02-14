<?php
/**
 * Render pagination UI
 * @param int $total_items Total number of items
 * @param int $items_per_page Number of items per page
 * @param int $current_page Current page number
 */
function renderPagination($total_items, $items_per_page, $current_page)
{
    if ($total_items <= 0)
        return;

    $total_pages = ceil($total_items / $items_per_page);
    $start_item = $total_items > 0 ? (($current_page - 1) * $items_per_page) + 1 : 0;
    $end_item = min($current_page * $items_per_page, $total_items);

    // Get current URL and query parameters
    $queryParams = $_GET;
    unset($queryParams['page']);

    function getPageUrl($p, $queryParams)
    {
        $queryParams['page'] = $p;
        return '?' . http_build_query($queryParams);
    }

    ?>
    <div class="pagination-container">
        <div class="results-info">
            Results:
            <?php echo $start_item; ?> -
            <?php echo $end_item; ?> of
            <?php echo $total_items; ?>
        </div>

        <div class="pagination-controls">
            <!-- Previous -->
            <?php if ($current_page > 1): ?>
                <a href="<?php echo getPageUrl($current_page - 1, $queryParams); ?>" class="page-btn">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php else: ?>
                <span class="page-btn disabled">
                    <i class="fas fa-chevron-left"></i>
                </span>
                <?php if (isset($_GET['page']) && $_GET['page'] > $total_pages && $total_pages > 0): ?>
                    <script>window.location.href = "<?php echo getPageUrl(1, $queryParams); ?>";</script>
                <?php endif; ?>
            <?php endif; ?>

            <?php
            $range = 1; // Number of pages either side of current
            for ($i = 1; $i <= $total_pages; $i++) {
                if ($i == 1 || $i == $total_pages || ($i >= $current_page - $range && $i <= $current_page + $range)) {
                    ?>
                    <a href="<?php echo getPageUrl($i, $queryParams); ?>"
                        class="page-btn <?php echo $i == $current_page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php
                } elseif ($i == $current_page - $range - 1 || $i == $current_page + $range + 1) {
                    echo '<span class="ellipsis">...</span>';
                    // Skip ahead/behind
                    if ($i < $current_page)
                        $i = $current_page - $range - 1;
                    else
                        $i = $total_pages - 1;
                }
            }
            ?>

            <!-- Next -->
            <?php if ($current_page < $total_pages): ?>
                <a href="<?php echo getPageUrl($current_page + 1, $queryParams); ?>" class="page-btn">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="page-btn disabled">
                    <i class="fas fa-chevron-right"></i>
                </span>
            <?php endif; ?>
        </div>

        <div class="rows-per-page">
            <select class="rows-select" onchange="changeLimit(this.value)">
                <?php foreach ([10, 25, 50, 100] as $limit): ?>
                    <option value="<?php echo $limit; ?>" <?php echo $items_per_page == $limit ? 'selected' : ''; ?>>
                        <?php echo $limit; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <script>
        function changeLimit(limit) {
            const url = new URL(window.location.href);
            url.searchParams.set('limit', limit);
            url.searchParams.set('page', 1);
            window.location.href = url.toString();
        }
    </script>
    <?php
}
?>