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

    if (!function_exists('getPageUrl')) {
        function getPageUrl($p, $queryParams)
        {
            $queryParams['page'] = $p;
            return '?' . http_build_query($queryParams);
        }
    }

    ?>
    <style>
        .pagination-container {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            padding: 1.25rem 1.5rem !important;
            background: #ffffff !important;
            border-top: 1px solid #edf2f7 !important;
            border-radius: 0 0 16px 16px !important;
            flex-wrap: wrap !important;
            gap: 1rem !important;
        }

        .results-info {
            font-size: 0.875rem !important;
            font-weight: 500 !important;
            color: #64748b !important;
        }

        .pagination-controls {
            display: flex !important;
            align-items: center !important;
            gap: 0.375rem !important;
        }

        .page-btn {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 36px !important;
            height: 36px !important;
            padding: 0 0.75rem !important;
            border-radius: 8px !important;
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            color: #1e293b !important;
            font-weight: 600 !important;
            font-size: 0.875rem !important;
            text-decoration: none !important;
            transition: all 0.2s ease !important;
            cursor: pointer !important;
        }

        .page-btn:hover:not(.disabled) {
            background: #f8fafc !important;
            border-color: #cbd5e1 !important;
            transform: translateY(-1px) !important;
        }

        .page-btn.active {
            background: #171717 !important;
            border-color: #171717 !important;
            color: #ffffff !important;
        }

        .page-btn.disabled {
            color: #cbd5e1 !important;
            cursor: not-allowed !important;
            background: #f8fafc !important;
            border-color: #f1f5f9 !important;
        }

        .pagination-row-selector {
            display: flex !important;
            align-items: center !important;
            gap: 0.75rem !important;
        }

        .rows-select {
            padding: 0.4rem 2rem 0.4rem 0.75rem !important;
            border-radius: 8px !important;
            border: 1px solid #e2e8f0 !important;
            background: #ffffff !important;
            font-weight: 600 !important;
            font-size: 0.875rem !important;
            color: #1e293b !important;
            outline: none !important;
            cursor: pointer !important;
            appearance: none !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right 0.6rem center !important;
            background-size: 1rem !important;
        }

        .rows-select:hover {
            border-color: #cbd5e1 !important;
        }

        @media (max-width: 768px) {
            .pagination-container {
                flex-direction: column !important;
                gap: 1.25rem !important;
                padding: 1.5rem !important;
            }

            .results-info {
                order: 1 !important;
            }

            .pagination-controls {
                order: 2 !important;
            }

            .pagination-row-selector {
                order: 3 !important;
                width: 100% !important;
                justify-content: center !important;
            }
        }
    </style>

    <div class="pagination-container">
        <div class="results-info">
            Showing <?php echo $start_item; ?> to <?php echo $end_item; ?> of <?php echo $total_items; ?> results
        </div>

        <div class="pagination-controls">
            <!-- Previous -->
            <?php if ($current_page > 1): ?>
                <a href="<?php echo getPageUrl($current_page - 1, $queryParams); ?>" class="page-btn" title="Previous Page">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php else: ?>
                <span class="page-btn disabled" title="Previous Page">
                    <i class="fas fa-chevron-left"></i>
                </span>
            <?php endif; ?>

            <?php
            $range = 1;
            for ($i = 1; $i <= $total_pages; $i++) {
                if ($i == 1 || $i == $total_pages || ($i >= $current_page - $range && $i <= $current_page + $range)) {
                    ?>
                    <a href="<?php echo getPageUrl($i, $queryParams); ?>"
                        class="page-btn <?php echo $i == $current_page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php
                } elseif ($i == $current_page - $range - 1 || $i == $current_page + $range + 1) {
                    echo '<span class="ellipsis" style="padding: 0 0.25rem; color: #94a3b8;">...</span>';
                    if ($i < $current_page)
                        $i = $current_page - $range - 1;
                    else
                        $i = $total_pages - 1;
                }
            }
            ?>

            <!-- Next -->
            <?php if ($current_page < $total_pages): ?>
                <a href="<?php echo getPageUrl($current_page + 1, $queryParams); ?>" class="page-btn" title="Next Page">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="page-btn disabled" title="Next Page">
                    <i class="fas fa-chevron-right"></i>
                </span>
            <?php endif; ?>
        </div>

        <div class="pagination-row-selector">
            <span style="font-size: 0.875rem; color: #64748b; font-weight: 500;">Rows:</span>
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