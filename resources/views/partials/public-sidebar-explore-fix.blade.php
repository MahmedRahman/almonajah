<style>
    /* استكشاف: إظهار اسم التصنيف كاملاً بدل القص */
    .sidebar-menu .sidebar-item:has(.sidebar-category-count) {
        align-items: flex-start;
    }

    .sidebar-menu .sidebar-item:has(.sidebar-category-count) .sidebar-category-image,
    .sidebar-menu .sidebar-item:has(.sidebar-category-count) > i {
        margin-top: 0.1rem;
    }

    .sidebar-menu .sidebar-item:has(.sidebar-category-count) .sidebar-item-text {
        flex: 1;
        min-width: 0;
        white-space: normal;
        overflow: visible;
        text-overflow: unset;
        line-height: 1.4;
        word-break: break-word;
    }

    .sidebar-menu .sidebar-item:has(.sidebar-category-count) .sidebar-category-count {
        flex-shrink: 0;
        line-height: 1.4;
        margin-top: 0.1rem;
    }
</style>
